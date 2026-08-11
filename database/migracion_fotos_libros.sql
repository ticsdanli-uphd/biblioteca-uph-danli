USE biblioteca;

ALTER TABLE bibliografia ADD COLUMN foto_frontal VARCHAR(255) NULL AFTER foto;
ALTER TABLE bibliografia ADD COLUMN foto_trasera VARCHAR(255) NULL AFTER foto_frontal;

-- Conserva las portadas antiguas como foto frontal.
UPDATE bibliografia SET foto_frontal = foto WHERE (foto_frontal IS NULL OR foto_frontal='') AND foto IS NOT NULL AND foto<>'';
