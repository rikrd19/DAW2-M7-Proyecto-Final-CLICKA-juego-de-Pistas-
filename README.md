# CLIKA - Setup

## Database initialization

This project stores runtime data in SQLite.

Run these commands from the project root:

```bash
sqlite3 database/clicka.db < database/schema.sql
sqlite3 database/clicka.db < database/seed.sql
```

## Notes

- Commit `database/schema.sql` and `database/seed.sql`.
- Do not commit runtime database files (`*.db`, `*.sqlite`, WAL/SHM files).
