#!/usr/bin/env python3
"""
Load CLICKA questions from a JSON seed file into SQLite (database/clicka.db).

Usage:
  python3 scripts/generator.py --seed scripts/seeds/historia.json
  python3 scripts/generator.py --mode replace --seed scripts/seeds/adivinanzas.json

append (default): skip rows that already exist exactly as in the JSON.
replace: deletes ALL preguntas with fuente='python', then inserts ONLY this file's rows
         (run every seed JSON after replace if you need a full DB rebuild).

Comments in English per project convention.
"""

import argparse
import json
import os
import sqlite3
import sys

BASE_PATH = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB_PATH = os.path.join(BASE_PATH, "database", "clicka.db")


def resolve_seed_path(user_path: str) -> str:
    if os.path.isabs(user_path) and os.path.isfile(user_path):
        return user_path
    for base in (BASE_PATH, os.getcwd()):
        cand = os.path.join(base, user_path)
        if os.path.isfile(cand):
            return cand
    return os.path.join(BASE_PATH, user_path)


def resolve_tema_id(cur: sqlite3.Cursor, nombre: str) -> int:
    cur.execute(
        "SELECT id FROM temas WHERE nombre = ? COLLATE NOCASE LIMIT 1",
        (nombre.strip(),),
    )
    row = cur.fetchone()
    if not row:
        raise ValueError(f"Tema not found in DB: {nombre!r}")
    return int(row[0])


def load_rows(cur: sqlite3.Cursor, path: str):
    with open(path, encoding="utf-8") as f:
        data = json.load(f)

    tema_nombre = str(data.get("tema_nombre", "")).strip()
    if not tema_nombre:
        raise ValueError("JSON missing tema_nombre")

    fuente_global = str(data.get("fuente") or "python").strip() or "python"
    items = data.get("preguntas")
    if not isinstance(items, list) or not items:
        raise ValueError("JSON missing non-empty preguntas array")

    tema_id = resolve_tema_id(cur, tema_nombre)
    rows = []

    for i, q in enumerate(items, start=1):
        if not isinstance(q, dict):
            raise ValueError(f"preguntas[{i}] must be an object")
        for key in ("pista1", "pista2", "pista3", "respuesta"):
            if key not in q or not str(q[key]).strip():
                raise ValueError(f"preguntas[{i}] missing or empty {key}")

        extra_raw = q.get("pista_extra")
        extra = None if extra_raw is None or str(extra_raw).strip() == "" else str(extra_raw).strip()
        fuente = str(q.get("fuente") or fuente_global).strip() or fuente_global

        rows.append(
            (
                tema_id,
                str(q["pista1"]).strip(),
                str(q["pista2"]).strip(),
                str(q["pista3"]).strip(),
                extra,
                str(q["respuesta"]).strip(),
                fuente,
            )
        )

    return rows


def row_exists(cur, tema_id, p1, p2, p3, extra, respuesta):
    cur.execute(
        """
        SELECT 1 FROM preguntas
        WHERE tema_id = ?
          AND pista1 = ? AND pista2 = ? AND pista3 = ?
          AND COALESCE(pista_extra, '') = COALESCE(?, '')
          AND lower(trim(respuesta)) = lower(trim(?))
        LIMIT 1
        """,
        (tema_id, p1, p2, p3, extra or "", respuesta),
    )
    return cur.fetchone() is not None


def main():
    parser = argparse.ArgumentParser(description="Seed CLICKA preguntas from JSON into SQLite.")
    parser.add_argument("--seed", required=True, help="Path to seed JSON (e.g. scripts/seeds/ciencia.json)")
    parser.add_argument(
        "--mode",
        choices=("append", "replace"),
        default="append",
        help="append: skip duplicates (default). replace: wipe all fuente=python then insert.",
    )
    args = parser.parse_args()

    if not os.path.isfile(DB_PATH):
        print(f"Error: database not found at {DB_PATH}", file=sys.stderr)
        sys.exit(1)

    path = resolve_seed_path(args.seed)
    if not os.path.isfile(path):
        print(f"Error: seed file not found: {args.seed}", file=sys.stderr)
        sys.exit(1)

    conn = None
    try:
        conn = sqlite3.connect(DB_PATH, timeout=30.0)
        conn.execute("PRAGMA foreign_keys = ON")
        conn.execute("PRAGMA journal_mode = WAL")
        cur = conn.cursor()

        rows = load_rows(cur, path)
        print(f"Loaded {len(rows)} question(s) from {path}")

        conn.execute("BEGIN IMMEDIATE")
        if args.mode == "replace":
            cur.execute("DELETE FROM preguntas WHERE fuente = 'python'")
            print(f"Removed {cur.rowcount} row(s) with fuente='python'")

        inserted = 0
        skipped = 0
        for tema_id, p1, p2, p3, extra, respuesta, fuente in rows:
            if args.mode == "append" and row_exists(cur, tema_id, p1, p2, p3, extra, respuesta):
                skipped += 1
                continue
            cur.execute(
                """
                INSERT INTO preguntas (tema_id, pista1, pista2, pista3, pista_extra, respuesta, fuente)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                """,
                (tema_id, p1, p2, p3, extra, respuesta, fuente),
            )
            inserted += 1

        conn.commit()
        print(f"Done: inserted={inserted}, skipped={skipped}")
    except (sqlite3.Error, ValueError, json.JSONDecodeError, OSError) as e:
        print(f"Error: {e}", file=sys.stderr)
        if conn:
            conn.rollback()
        sys.exit(1)
    finally:
        if conn:
            conn.close()


if __name__ == "__main__":
    main()
