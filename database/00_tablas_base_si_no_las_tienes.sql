CREATE DATABASE IF NOT EXISTS smartcampus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartcampus;

CREATE TABLE IF NOT EXISTS DimTiempo (
    id_tiempo INT PRIMARY KEY,
    hora INT,
    mes INT,
    año INT,
    dia_semana VARCHAR(20)
);

CREATE TABLE IF NOT EXISTS DimEdificio (
    id_edificio INT PRIMARY KEY,
    nombre VARCHAR(80),
    tipo VARCHAR(60)
);

CREATE TABLE IF NOT EXISTS DimAmbiente (
    id_ambiente INT PRIMARY KEY,
    nombre VARCHAR(80),
    tipo VARCHAR(60),
    capacidad INT
);

CREATE TABLE IF NOT EXISTS DimOcupacion (
    id_ocupacion INT AUTO_INCREMENT PRIMARY KEY,
    cantidad_personas INT,
    porcentaje DECIMAL(10,2)
);

CREATE TABLE IF NOT EXISTS FactConsumoEnergetico (
    id_fact INT AUTO_INCREMENT PRIMARY KEY,
    id_tiempo INT,
    id_edificio INT,
    id_ambiente INT,
    id_ocupacion INT,
    consumo_kwh DECIMAL(12,4),
    demanda_pico_kw DECIMAL(12,4),
    factor_potencia DECIMAL(12,6),
    temperatura DECIMAL(12,4),
    eficiencia DECIMAL(12,4),
    riesgo_sobreconsumo TINYINT,
    FOREIGN KEY (id_tiempo) REFERENCES DimTiempo(id_tiempo),
    FOREIGN KEY (id_edificio) REFERENCES DimEdificio(id_edificio),
    FOREIGN KEY (id_ambiente) REFERENCES DimAmbiente(id_ambiente),
    FOREIGN KEY (id_ocupacion) REFERENCES DimOcupacion(id_ocupacion)
);
