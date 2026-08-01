-- Se ejecuta una única vez, cuando el volumen de datos se crea desde cero.

-- Búsquedas insensibles a acentos/mayúsculas (útil para nombres y placas).
CREATE EXTENSION IF NOT EXISTS unaccent;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS citext;
