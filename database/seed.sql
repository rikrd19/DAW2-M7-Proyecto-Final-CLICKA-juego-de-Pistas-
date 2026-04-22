-- Create initial themes
INSERT INTO temas (nombre) VALUES
    ('adivinanzas'),
    ('cine'),
    ('banderas'),
    ('dichos');

-- Initial Administrator (login email + password for local/demo only)
-- password_hash('admin123', PASSWORD_DEFAULT) — login: admin@gmail.com / admin123
INSERT INTO usuarios (username, password_hash, rol) VALUES
    ('admin@gmail.com', '$2y$12$9ibvaXEmjB/iSa/xmE6A8eAXyjQrRhknH4ZUP30dg6OpA9E8W30MC', 'admin');

-- Sample questions for testing
INSERT INTO preguntas (tema_id, pista1, pista2, pista3, pista_extra, respuesta) VALUES
    (1, 'Tengo agujas pero no coso', 'Doy vueltas todo el día', 'Mido el tiempo', 'Estoy en tu pared o muñeca', 'reloj'),
    (2, 'Director: James Cameron', 'Barco famoso', 'Romance trágico', 'Se estrenó en 1997', 'titanic');

-- Existing DB still has username "admin"? Run once in sqlite3 so login matches seed (admin@gmail.com / admin123):
-- UPDATE usuarios SET username = 'admin@gmail.com' WHERE rol = 'admin' AND LOWER(username) = 'admin';
