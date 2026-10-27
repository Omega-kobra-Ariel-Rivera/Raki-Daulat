-- ==========================================================================
-- Raki Daulat — esquema de base de datos (PostgreSQL 13+)
-- Uso:  psql -U postgres -d raki_daulat -f database/schema.sql
-- ==========================================================================

-- Mensajes enviados desde el formulario de contacto
CREATE TABLE IF NOT EXISTS contactos (
    id         SERIAL       PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    correo     VARCHAR(150) NOT NULL,
    telefono   VARCHAR(20),
    mensaje    TEXT         NOT NULL,
    leido      BOOLEAN      NOT NULL DEFAULT FALSE,
    ip         VARCHAR(45),
    creado_en  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- Solicitudes de reserva
CREATE TABLE IF NOT EXISTS reservas (
    id           SERIAL       PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    correo       VARCHAR(150) NOT NULL,
    telefono     VARCHAR(20)  NOT NULL,
    fecha        DATE         NOT NULL,
    hora         TIME         NOT NULL,
    personas     SMALLINT     NOT NULL CHECK (personas BETWEEN 1 AND 12),
    comentarios  TEXT,
    estado       VARCHAR(20)  NOT NULL DEFAULT 'pendiente'
                 CHECK (estado IN ('pendiente', 'confirmada', 'cancelada')),
    ip           VARCHAR(45),
    creado_en    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_reservas_fecha  ON reservas (fecha, hora);
CREATE INDEX IF NOT EXISTS idx_reservas_estado ON reservas (estado);
