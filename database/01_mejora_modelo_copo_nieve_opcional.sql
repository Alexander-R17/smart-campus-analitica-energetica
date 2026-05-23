USE smartcampus;

-- Mejora opcional para tu informe: convertir el modelo estrella inicial en un modelo copo de nieve más normalizado.
-- Ejecutar solo cuando quieras evolucionar la base; el prototipo actual trabaja con las tablas base.

CREATE TABLE IF NOT EXISTS DimZonaCampus (
    id_zona INT AUTO_INCREMENT PRIMARY KEY,
    nombre_zona VARCHAR(80) NOT NULL,
    descripcion VARCHAR(200)
);

CREATE TABLE IF NOT EXISTS DimTipoAmbiente (
    id_tipo_ambiente INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo VARCHAR(80) NOT NULL,
    descripcion VARCHAR(200)
);

CREATE TABLE IF NOT EXISTS DimMedidor (
    id_medidor INT AUTO_INCREMENT PRIMARY KEY,
    codigo_medidor VARCHAR(60),
    tipo_medidor VARCHAR(60),
    estado_medidor VARCHAR(30),
    id_edificio INT,
    FOREIGN KEY (id_edificio) REFERENCES DimEdificio(id_edificio)
);

CREATE TABLE IF NOT EXISTS DimClima (
    id_clima INT AUTO_INCREMENT PRIMARY KEY,
    temperatura DECIMAL(10,2),
    humedad DECIMAL(10,2),
    condicion VARCHAR(60)
);

-- Columnas opcionales para enriquecer las dimensiones existentes.
ALTER TABLE DimEdificio ADD COLUMN ubicacion VARCHAR(120) NULL;
ALTER TABLE DimEdificio ADD COLUMN id_zona INT NULL;
ALTER TABLE DimAmbiente ADD COLUMN id_tipo_ambiente INT NULL;

-- Columnas opcionales para enriquecer la tabla de hechos con métricas útiles para KPIs.
ALTER TABLE FactConsumoEnergetico ADD COLUMN potencia_kw DECIMAL(12,4) NULL;
ALTER TABLE FactConsumoEnergetico ADD COLUMN costo_estimado DECIMAL(12,4) NULL;
ALTER TABLE FactConsumoEnergetico ADD COLUMN consumo_por_ocupante DECIMAL(12,4) NULL;
ALTER TABLE FactConsumoEnergetico ADD COLUMN consumo_fantasma TINYINT NULL;
ALTER TABLE FactConsumoEnergetico ADD COLUMN id_medidor INT NULL;
ALTER TABLE FactConsumoEnergetico ADD COLUMN id_clima INT NULL;
