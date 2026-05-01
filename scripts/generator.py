#!/usr/bin/env python3
"""
CLICKA question seeding script (Python + shared SQLite).

Writes into the same database as PHP (database/clicka.db).
Default mode is append with duplicate detection — safe for repeated runs without wiping data.

Examples:
  python3 scripts/generator.py --seed scripts/seeds/adivinanzas.json
  python3 scripts/generator.py --seed scripts/seeds/ciencia.json
  python3 scripts/generator.py --seed scripts/seeds/cultura_popular.json
  python3 scripts/generator.py --seed scripts/seeds/historia.json
  python3 scripts/generator.py --seed scripts/seeds/geografia.json
  python3 scripts/generator.py --seed scripts/seeds/deportes.json
  python3 scripts/generator.py --seed scripts/seeds/arte.json
  python3 scripts/generator.py --seed scripts/seeds/musica.json
  python3 scripts/generator.py --seed scripts/seeds/tecnologia.json
  python3 scripts/generator.py --seed scripts/seeds/naturaleza.json
  python3 scripts/generator.py --seed scripts/seeds/cine.json
  python3 scripts/generator.py --seed scripts/seeds/catalan_basico.json
  python3 scripts/generator.py --mode replace --seed scripts/seeds/adivinanzas.json

Note: --mode replace deletes ALL rows with fuente='python' before inserting the current batch.
"""

from __future__ import annotations

import argparse
import json
import os
import sqlite3
import sys

BASE_PATH = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB_PATH = os.path.join(BASE_PATH, "database", "clicka.db")


def resolve_seed_path(user_path: str) -> str:
    """Resolve JSON path from project root, cwd, or absolute path."""
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


def load_rows_from_json(cur: sqlite3.Cursor, path: str) -> list[tuple[int, str, str, str, str | None, str, str]]:
    with open(path, encoding="utf-8") as f:
        data = json.load(f)
    if not isinstance(data, dict):
        raise ValueError("JSON root must be an object")

    tema_nombre = str(data.get("tema_nombre", "")).strip()
    if not tema_nombre:
        raise ValueError("JSON missing tema_nombre")

    fuente_global = str(data.get("fuente") or "python").strip() or "python"
    items = data.get("preguntas")
    if not isinstance(items, list) or not items:
        raise ValueError("JSON missing non-empty preguntas array")

    tema_id = resolve_tema_id(cur, tema_nombre)
    rows: list[tuple[int, str, str, str, str | None, str, str]] = []

    for i, q in enumerate(items, start=1):
        if not isinstance(q, dict):
            raise ValueError(f"preguntas[{i}] must be an object")
        for key in ("pista1", "pista2", "pista3", "respuesta"):
            if key not in q or not str(q[key]).strip():
                raise ValueError(f"preguntas[{i}] missing or empty {key}")

        extra_raw = q.get("pista_extra")
        if extra_raw is None or str(extra_raw).strip() == "":
            extra: str | None = None
        else:
            extra = str(extra_raw).strip()

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


def row_already_exists(
    cur: sqlite3.Cursor,
    tema_id: int,
    pista1: str,
    pista2: str,
    pista3: str,
    pista_extra: str | None,
    respuesta: str,
) -> bool:
    """Full-row match prevents accidental duplicates on re-runs."""
    cur.execute(
        """
        SELECT 1 FROM preguntas
        WHERE tema_id = ?
          AND pista1 = ? AND pista2 = ? AND pista3 = ?
          AND COALESCE(pista_extra, '') = COALESCE(?, '')
          AND lower(trim(respuesta)) = lower(trim(?))
        LIMIT 1
        """,
        (tema_id, pista1, pista2, pista3, pista_extra or "", respuesta),
    )
    return cur.fetchone() is not None


def seed_questions(mode: str, seed_path: str) -> None:
    if not os.path.exists(DB_PATH):
        print(f"Error: database not found at {DB_PATH}", file=sys.stderr)
        sys.exit(1)

    conn: sqlite3.Connection | None = None
    try:
        conn = sqlite3.connect(DB_PATH)
        conn.execute("PRAGMA foreign_keys = ON")
        conn.execute("PRAGMA journal_mode = WAL")

        cur = conn.cursor()

        path = resolve_seed_path(seed_path)
        if not os.path.isfile(path):
            print(f"Error: seed file not found: {seed_path}", file=sys.stderr)
            sys.exit(1)
        rows_to_insert = load_rows_from_json(cur, path)
        print(f"Loaded {len(rows_to_insert)} question(s) from {path}")

        deleted = 0
        if mode == "replace":
            cur.execute("DELETE FROM preguntas WHERE fuente = 'python'")
            deleted = cur.rowcount if cur.rowcount is not None else 0
            print(f"--- replace mode: removed {deleted} row(s) with fuente='python' ---")

        inserted = 0
        skipped = 0

        conn.execute("BEGIN IMMEDIATE")

        for (
            tema_id,
            pista1,
            pista2,
            pista3,
            pista_extra,
            respuesta,
            fuente,
        ) in rows_to_insert:
            extra = pista_extra if pista_extra is not None else None
            if mode == "append" and row_already_exists(
                cur, tema_id, pista1, pista2, pista3, extra, respuesta
            ):
                skipped += 1
                continue

            cur.execute(
                """
                INSERT INTO preguntas (tema_id, pista1, pista2, pista3, pista_extra, respuesta, fuente)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                """,
                (tema_id, pista1, pista2, pista3, extra, respuesta, fuente),
            )
            inserted += 1

        conn.commit()

        cur.execute("SELECT COUNT(*) FROM preguntas WHERE fuente = 'python'")
        total_python = cur.fetchone()[0]

        print("\n--- summary ---")
        print(f"mode:             {mode}")
        print(f"inserted:         {inserted}")
        print(f"skipped (dup):    {skipped}")
        print(f"total fuente=py:  {total_python}")

    except (sqlite3.Error, ValueError, json.JSONDecodeError, OSError) as e:
        print(f"Error: {e}", file=sys.stderr)
        if conn:
            try:
                conn.rollback()
            except sqlite3.Error:
                pass
        sys.exit(1)
    finally:
        if conn:
            conn.close()


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Seed CLICKA questions into SQLite from a JSON file.",
        epilog="JSON format: see scripts/seeds/adivinanzas.json (tema_nombre + preguntas[]).",
    )
    parser.add_argument(
        "--mode",
        choices=("append", "replace"),
        default="append",
        help="append: skip duplicates (default). replace: delete all fuente=python then insert.",
    )
    parser.add_argument(
        "--seed",
        metavar="PATH",
        required=True,
        help="JSON seed file (e.g. scripts/seeds/adivinanzas.json)",
    )
    args = parser.parse_args()
    seed_questions(args.mode, args.seed)


if __name__ == "__main__":
    main()
