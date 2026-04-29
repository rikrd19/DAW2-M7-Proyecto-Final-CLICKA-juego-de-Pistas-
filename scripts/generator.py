#!/usr/bin/env python3
"""
CLICKA question seeding script (Python + shared SQLite).

Writes into the same database as PHP (database/clicka.db).
Default mode is append with duplicate detection — safe for repeated runs without wiping data.
Use --mode replace only when you intentionally want to reload the Python-authored subset.
"""

from __future__ import annotations

import argparse
import os
import sqlite3
import sys

BASE_PATH = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB_PATH = os.path.join(BASE_PATH, "database", "clicka.db")

# Canonical dataset bundled with the script (extend this list to grow the bank).
SAMPLE_QUESTIONS: list[tuple[int, str, str, str, str | None, str, str]] = [
    (
        2,
        "Protagonista: Jack Sparrow",
        "Barco: La Perla Negra",
        "Saga de piratas",
        "Johnny Depp es el actor",
        "piratas del caribe",
        "python",
    ),
    (
        3,
        "País de Sudamérica",
        "Capital Brasilia",
        "Bandera verde, amarilla y azul",
        "Famoso por el carnaval de Río",
        "brasil",
        "python",
    ),
    (
        1,
        "Vuelo sin tener alas",
        "Lloro sin tener ojos",
        "Soy gris o blanca",
        "Traigo la lluvia",
        "nube",
        "python",
    ),
]


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


def seed_questions(mode: str) -> None:
    if not os.path.exists(DB_PATH):
        print(f"Error: database not found at {DB_PATH}", file=sys.stderr)
        sys.exit(1)

    conn: sqlite3.Connection | None = None
    try:
        conn = sqlite3.connect(DB_PATH)
        conn.execute("PRAGMA foreign_keys = ON")
        # Helps concurrent reads while PHP serves the app (best-effort).
        conn.execute("PRAGMA journal_mode = WAL")

        cur = conn.cursor()

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
        ) in SAMPLE_QUESTIONS:
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
        print(f"mode:           {mode}")
        print(f"inserted:       {inserted}")
        print(f"skipped (dup):  {skipped}")
        print(f"total python Q: {total_python}")

    except sqlite3.Error as e:
        print(f"SQLite error: {e}", file=sys.stderr)
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
    parser = argparse.ArgumentParser(description="Seed CLICKA questions into SQLite.")
    parser.add_argument(
        "--mode",
        choices=("append", "replace"),
        default="append",
        help=(
            "append: skip rows that already exist (default). "
            "replace: delete all fuente='python' rows then insert SAMPLE_QUESTIONS."
        ),
    )
    args = parser.parse_args()
    seed_questions(args.mode)


if __name__ == "__main__":
    main()

"""
Uso
python3 scripts/generator.py
python3 scripts/generator.py --mode append
python3 scripts/generator.py --mode replace   # solo si queréis reset explícito del subset python

Nota: En --mode replace, solo se borran filas de preguntas con fuente='python'. Las partidas y 
round_answers no se tocan; los question_id antiguos podrían quedar huérfanos si borráis preguntas 
que ya se jugaron — es el trade-off de un reset selectivo. Para día a día, quedaos en append.
"""