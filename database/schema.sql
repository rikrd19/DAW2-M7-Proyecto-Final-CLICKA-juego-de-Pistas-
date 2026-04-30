PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS temas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS preguntas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tema_id INTEGER NOT NULL,
    pista1 TEXT NOT NULL,
    pista2 TEXT NOT NULL,
    pista3 TEXT NOT NULL,
    pista_extra TEXT,
    respuesta TEXT NOT NULL,
    fuente TEXT NOT NULL DEFAULT 'manual',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tema_id) REFERENCES temas (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    -- username stores normalized login email (lowercase); label in UI remains "Usuario".
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    rol TEXT NOT NULL DEFAULT 'jugador' CHECK (rol IN ('jugador', 'admin')),
    foto TEXT DEFAULT 'default.png',
    puntos INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS partidas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NULL,
    nombre_temporal TEXT,
    puntos INTEGER NOT NULL DEFAULT 0,
    tema TEXT NOT NULL,
    fecha TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
);

-- Per-question analytics for each round: enables clues_used distribution and correctness metrics.
CREATE TABLE IF NOT EXISTS round_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    partida_id INTEGER NOT NULL,
    question_id INTEGER NULL,
    clues_used INTEGER NOT NULL CHECK (clues_used BETWEEN 1 AND 4),
    correct INTEGER NOT NULL CHECK (correct IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partida_id) REFERENCES partidas (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_preguntas_tema ON preguntas (tema_id);
CREATE INDEX IF NOT EXISTS idx_partidas_usuario ON partidas (usuario_id);
CREATE INDEX IF NOT EXISTS idx_round_answers_partida ON round_answers (partida_id);
