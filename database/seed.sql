-- Create initial themes
INSERT INTO temas (nombre) VALUES
    ('adivinanzas'),
    ('cine'),
    ('banderas'),
    ('dichos');

-- Initial Administrator User (Password: admin123)
-- Generated with password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO usuarios (username, password_hash, rol) VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample questions for testing
INSERT INTO preguntas (tema_id, pista1, pista2, pista3, pista_extra, respuesta) VALUES
    (1, 'Tengo agujas pero no coso', 'Doy vueltas todo el día', 'Mido el tiempo', 'Estoy en tu pared o muñeca', 'reloj'),
    (2, 'Director: James Cameron', 'Barco famoso', 'Romance trágico', 'Se estrenó en 1997', 'titanic');
