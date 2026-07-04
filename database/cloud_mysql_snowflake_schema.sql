CREATE TABLE IF NOT EXISTS staging_upload_batch (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_lote VARCHAR(120) NOT NULL,
    archivos JSON NULL,
    estado VARCHAR(40) NOT NULL DEFAULT 'RECIBIDO',
    registros_crudos INT NOT NULL DEFAULT 0,
    registros_predichos INT NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staging_lectura_cruda (
    id_staging BIGINT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    origen_archivo VARCHAR(180) NULL,
    id_tiempo INT NULL,
    hora INT NULL,
    mes INT NULL,
    anio INT NULL,
    id_edificio INT NULL,
    id_ambiente INT NULL,
    ocupacion DECIMAL(10,2) NULL,
    temperatura DECIMAL(10,2) NULL,
    demanda_pico_kw DECIMAL(10,2) NULL,
    factor_potencia DECIMAL(10,4) NULL,
    consumo_kwh DECIMAL(12,2) NULL,
    nombre_edificio VARCHAR(100) NULL,
    tipo_ambiente VARCHAR(100) NULL,
    codigo_medidor VARCHAR(60) NULL,
    estado_medidor VARCHAR(40) NULL,
    payload_json JSON NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staging_batch (batch_id),
    INDEX idx_staging_tiempo (anio, mes, hora),
    CONSTRAINT fk_staging_batch FOREIGN KEY (batch_id) REFERENCES staging_upload_batch(batch_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dim_tiempo (
    id_tiempo INT PRIMARY KEY,
    hora INT NULL,
    mes INT NULL,
    anio INT NULL,
    nombre_mes VARCHAR(20) NULL,
    periodo VARCHAR(20) NULL,
    franja_horaria VARCHAR(30) NULL,
    INDEX idx_dim_tiempo_periodo (periodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dim_edificio (
    id_edificio INT PRIMARY KEY,
    nombre VARCHAR(100) NULL,
    tipo VARCHAR(60) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dim_ambiente (
    id_ambiente INT PRIMARY KEY,
    nombre VARCHAR(100) NULL,
    tipo VARCHAR(60) NULL,
    capacidad INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dim_ocupacion (
    id_ocupacion INT AUTO_INCREMENT PRIMARY KEY,
    cantidad_personas INT NULL,
    porcentaje INT NULL,
    UNIQUE KEY uq_ocupacion (cantidad_personas, porcentaje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dim_medidor (
    id_medidor INT AUTO_INCREMENT PRIMARY KEY,
    codigo_medidor VARCHAR(60) NOT NULL,
    tipo VARCHAR(60) NULL,
    estado VARCHAR(40) NULL,
    UNIQUE KEY uq_medidor_codigo (codigo_medidor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fact_consumo_energetico (
    id_fact BIGINT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    id_tiempo INT NULL,
    id_edificio INT NULL,
    id_ambiente INT NULL,
    id_ocupacion INT NULL,
    id_medidor INT NULL,
    ocupacion DECIMAL(10,2) NULL,
    temperatura DECIMAL(10,2) NULL,
    demanda_pico_kw DECIMAL(10,2) NULL,
    factor_potencia DECIMAL(10,4) NULL,
    consumo_kwh DECIMAL(12,2) NULL,
    fecha_carga TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fact_batch (batch_id),
    INDEX idx_fact_tiempo (id_tiempo),
    INDEX idx_fact_edificio (id_edificio),
    INDEX idx_fact_ambiente (id_ambiente),
    CONSTRAINT fk_fact_batch FOREIGN KEY (batch_id) REFERENCES staging_upload_batch(batch_id) ON DELETE CASCADE,
    CONSTRAINT fk_fact_tiempo FOREIGN KEY (id_tiempo) REFERENCES dim_tiempo(id_tiempo),
    CONSTRAINT fk_fact_edificio FOREIGN KEY (id_edificio) REFERENCES dim_edificio(id_edificio),
    CONSTRAINT fk_fact_ambiente FOREIGN KEY (id_ambiente) REFERENCES dim_ambiente(id_ambiente),
    CONSTRAINT fk_fact_ocupacion FOREIGN KEY (id_ocupacion) REFERENCES dim_ocupacion(id_ocupacion),
    CONSTRAINT fk_fact_medidor FOREIGN KEY (id_medidor) REFERENCES dim_medidor(id_medidor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fact_consumo_energetico_pred (
    id_pred BIGINT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    id_tiempo INT NULL,
    hora INT NULL,
    mes INT NULL,
    anio INT NULL,
    id_edificio INT NULL,
    id_ambiente INT NULL,
    ocupacion DECIMAL(10,2) NULL,
    temperatura DECIMAL(10,2) NULL,
    demanda_pico_kw DECIMAL(10,2) NULL,
    factor_potencia DECIMAL(10,4) NULL,
    consumo_kwh DECIMAL(12,2) NULL,
    nombre_edificio VARCHAR(100) NULL,
    tipo_ambiente VARCHAR(100) NULL,
    nombre_mes VARCHAR(20) NULL,
    periodo VARCHAR(20) NULL,
    franja_horaria VARCHAR(30) NULL,
    eficiencia_energetica_pct DECIMAL(10,2) NULL,
    riesgo_energetico_indice VARCHAR(30) NULL,
    pred_consumo_kwh DECIMAL(12,2) NULL,
    sobreconsumo_real TINYINT NULL,
    riesgo_sobreconsumo_prob DECIMAL(10,4) NULL,
    riesgo_sobreconsumo_pred VARCHAR(30) NULL,
    fecha_proceso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pred_batch (batch_id),
    INDEX idx_pred_periodo (periodo),
    CONSTRAINT fk_pred_batch FOREIGN KEY (batch_id) REFERENCES staging_upload_batch(batch_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS etl_log (
    id_log BIGINT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NULL,
    etapa VARCHAR(80) NOT NULL,
    mensaje TEXT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_batch (batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
