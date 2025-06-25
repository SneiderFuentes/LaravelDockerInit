-- Archivo para revertir los cambios realizados en external_tables_updates.sql
-- Fecha: 2024-03-XX
-- IMPORTANTE: Ejecutar en orden inverso a la creación

-- --------------------------------------------------------
-- Eliminar tabla: cups_flujo_asignacion
-- --------------------------------------------------------

-- Luego eliminar la tabla (las FK se eliminan automáticamente)
DROP TABLE IF EXISTS datosipsndx.cup_medico;

-- --------------------------------------------------------
-- Eliminar tabla: cups_horarios_especificos
-- --------------------------------------------------------

DROP TABLE IF EXISTS datosipsndx.cups_horarios_especificos;

-- --------------------------------------------------------
-- Eliminar tabla: cups_procedimientos
-- --------------------------------------------------------

DROP TABLE IF EXISTS datosipsndx.cups_procedimientos;

-- --------------------------------------------------------
-- Revertir cambios en tabla: citas
-- --------------------------------------------------------

-- Eliminar índices
ALTER TABLE datosipsndx.citas
    DROP INDEX IF EXISTS idx_confirmacion,
    DROP INDEX IF EXISTS idx_medio_confirmacion;

-- Eliminar columnas
ALTER TABLE datosipsndx.citas
    DROP COLUMN IF EXISTS Confirmada,
    DROP COLUMN IF EXISTS FechaConfirmacion,
    DROP COLUMN IF EXISTS MedioConfirmacion,
    DROP COLUMN IF EXISTS IdMedioConfirmacion;

-- Comentarios:
-- 1. Los scripts se ejecutan en orden inverso para manejar correctamente las dependencias
-- 2. Se usa IF EXISTS para evitar errores si algo ya fue eliminado
-- 3. Las FKs se eliminan automáticamente con DROP TABLE gracias a InnoDB
-- 4. Los índices se eliminan antes que las columnas en citas

-- --------------------------------------------------------
-- Eliminar usuario dedicado de la aplicación
-- --------------------------------------------------------

DROP USER IF EXISTS 'appuser'@'%';
