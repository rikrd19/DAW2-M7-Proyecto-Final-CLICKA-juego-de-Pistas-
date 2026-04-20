import sqlite3
import os
import sys

# --- COMENTARIOS EDUCATIVOS ---
# 1. Interoperabilidad: Este script demuestra cómo Python puede manipular la misma base de datos
#    que utiliza la aplicación web en PHP. Ambas tecnologías comparten el archivo 'clicka.db'.
# 2. Librería Estándar: Usamos 'sqlite3', que viene integrada en Python, evitando dependencias externas.
# 3. Rutas Absolutas: Calculamos la ruta de la base de datos dinámicamente para que el script
#    funcione sin importar desde dónde se ejecute (BASE_PATH).

# Obtener la ruta absoluta de la base de datos (DirectorioRaiz/database/clicka.db)
BASE_PATH = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB_PATH = os.path.join(BASE_PATH, 'database', 'clicka.db')

def seed_questions():
    """
    Función que inserta preguntas y pistas en la base de datos usando sentencias SQL.
    Cumple con el requisito de 'dos lenguajes, una misma base de datos'.
    """
    
    # Verificación de pre-requisito: ¿Existe el archivo de base de datos creado por PHP/SQL?
    if not os.path.exists(DB_PATH):
        print(f"Error: Base de datos no encontrada en {DB_PATH}")
        sys.exit(1)

    try:
        # CONEXIÓN: Establecemos el puente con SQLite3
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()

        # LIMPIEZA: Opcional - Borramos las preguntas previas de Python para evitar duplicados.
        # Esto permite que el script sea "idempotente" (puedes correrlo mil veces y el resultado es el mismo).
        cursor.execute("DELETE FROM preguntas WHERE fuente = 'python'")
        conn.commit()

        # AUDITORÍA PREVIA: Contamos cuántas preguntas hay después de limpiar (debería ser 0)
        cursor.execute("SELECT COUNT(*) FROM preguntas WHERE fuente = 'python'")
        total_antes = cursor.fetchone()[0]
        print(f"--- ESTADO INICIAL (Tras limpieza) ---")
        print(f"Preguntas de Python detectadas: {total_antes}")

        # DATOS DE EJEMPLO: (tema_id, pista1, pista2, pista3, pista_extra, respuesta, fuente)
        new_questions = [
            (2, 'Protagonista: Jack Sparrow', 'Barco: La Perla Negra', 'Saga de piratas', 'Johnny Depp es el actor', 'piratas del caribe', 'python'),
            (3, 'País de Sudamérica', 'Capital Brasilia', 'Bandera verde, amarilla y azul', 'Famoso por el carnaval de Río', 'brasil', 'python'),
            (1, 'Vuelo sin tener alas', 'Lloro sin tener ojos', 'Soy gris o blanca', 'Traigo la lluvia', 'nube', 'python'),
            # (4, 'Protagonista: Jack Sparrow', 'Barco: La Perla Negra', 'Saga de piratas', 'Johnny Depp es el actor', 'piratas del caribe', 'python'),
            # (5, 'País de Sudamérica', 'Capital Brasilia', 'Bandera verde, amarilla y azul', 'Famoso por el carnaval de Río', 'brasil', 'python'),
            # (6, 'Vuelo sin tener alas', 'Lloro sin tener ojos', 'Soy gris o blanca', 'Traigo la lluvia', 'nube', 'python')
        ]

        print(f"\nInsertando {len(new_questions)} preguntas nuevas...")
        
        # OPERACIÓN BATCH: 'executemany' es más eficiente para múltiples inserciones.
        cursor.executemany('''
            INSERT INTO preguntas (tema_id, pista1, pista2, pista3, pista_extra, respuesta, fuente)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ''', new_questions)

        # PERSISTENCIA: Los cambios no se guardan físicamente hasta hacer el commit.
        conn.commit()

        # AUDITORÍA POSTERIOR: Verificamos el nuevo total
        cursor.execute("SELECT COUNT(*) FROM preguntas WHERE fuente = 'python'")
        total_despues = cursor.fetchone()[0]
        
        print(f"\n--- ESTADO FINAL ---")
        print(f"Total de preguntas de Python en la BD: {total_despues}")
        print(f"Se han añadido {total_despues - total_antes} registros en esta ejecución.")
        print(f"¡Éxito! Ahora puedes ver los cambios en la aplicación web.")

    except sqlite3.Error as e:
        print(f"Error de SQLite: {e}")
    finally:
        # CIERRE: Siempre liberamos la conexión para evitar bloqueos (database is locked).
        if conn:
            conn.close()

if __name__ == "__main__":
    seed_questions()
