INSERT INTO temas (nombre) VALUES
    ('adivinanzas'),
    ('cine'),
    ('banderas'),
    ('dichos');

INSERT INTO usuarios (username, password_hash, rol) VALUES
    ('admin', '$2y$12$7homUAqp74z7KBdbwi8Wvu7Oh/.pFvVyimGSZPb5p9je6X3yQU8f2', 'admin');

INSERT INTO preguntas (tema_id, pista1, pista2, pista3, pista_extra, respuesta) VALUES
    (1, 'Tengo agujas pero no coso', 'Doy vueltas todo el día', 'Estoy en tu pared o muñeca', 'Mido el tiempo', 'reloj'),
    (1, 'Cuanto más seco, más moja', 'Es de tela', 'Se abre cuando llueve', 'Suele tener mango', 'toalla'),
    (1, 'Tengo dientes pero no muerdo', 'Sirvo para el pelo', 'Soy pequeño y de plástico o madera', 'Voy en el baño o bolso', 'peine'),

    (2, 'Director: James Cameron', 'Barco famoso', 'Romance trágico', 'Se estrenó en 1997', 'titanic'),
    (2, 'Tiene un elegido llamado Neo', 'Píldora roja o azul', 'Mundo simulado', 'Keanu Reeves es protagonista', 'matrix'),
    (2, 'Saga de magos', 'Escuela Hogwarts', 'El protagonista tiene una cicatriz', 'Basada en libros de J. K. Rowling', 'harry potter'),

    (3, 'País europeo', 'Capital Madrid', 'Bandera roja y amarilla', 'Está en la península ibérica', 'españa'),
    (3, 'País de Norteamérica', 'Tiene hoja de arce', 'Capital Ottawa', 'Bandera roja y blanca', 'canadá'),
    (3, 'País asiático', 'Capital Tokio', 'Bandera blanca con círculo rojo', 'Conocido como el país del sol naciente', 'japón'),

    (4, 'Habla sobre insistir aunque sea difícil', 'Empieza por no rendirse a la primera', 'Se usa para animar a seguir intentando', 'Menciona una caída y una acción de levantarse', 'al mal tiempo buena cara'),
    (4, 'Se dice cuando algo llega en el momento exacto', 'Relaciona dedo con anillo', 'Expresa encaje perfecto entre situación y solución', 'Muy usado en conversaciones informales', 'como anillo al dedo'),
    (4, 'Aconseja esperar antes de sacar conclusiones', 'Menciona un animal y su piel', 'No debes celebrar antes de tiempo', 'Primero verifica el resultado y luego confirma', 'no vendas la piel del oso antes de cazarlo');
