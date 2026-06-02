CREATE DATABASE IF NOT EXISTS smartcampus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartcampus;

CREATE TABLE IF NOT EXISTS fact_consumo_energetico_pred (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tiempo INT NULL,
    hora INT NULL,
    mes INT NULL,
    anio INT NULL,
    id_edificio INT NULL,
    id_ambiente INT NULL,
    ocupacion DECIMAL(10,2) NULL,
    temperatura DECIMAL(10,2) NULL,
    demanda_pico_kw DECIMAL(10,2) NULL,
    factor_potencia DECIMAL(10,3) NULL,
    consumo_kwh DECIMAL(10,2) NULL,
    nombre_edificio VARCHAR(100) NULL,
    tipo_ambiente VARCHAR(100) NULL,
    nombre_mes VARCHAR(20) NULL,
    periodo VARCHAR(20) NULL,
    franja_horaria VARCHAR(30) NULL,
    eficiencia_energetica_pct DECIMAL(10,2) NULL,
    riesgo_energetico_indice VARCHAR(30) NULL,
    pred_consumo_kwh DECIMAL(10,2) NULL,
    sobreconsumo_real TINYINT NULL,
    riesgo_sobreconsumo_prob DECIMAL(10,4) NULL,
    riesgo_sobreconsumo_pred VARCHAR(30) NULL,
    fecha_proceso TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
