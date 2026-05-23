USE smartcampus;

-- Verificar la cantidad de registros cargados en la tabla de hechos
SELECT COUNT(*) AS total_registros_fact FROM FactConsumoEnergetico;

-- Consulta base para revisar registros del Data Warehouse con dimensiones
SELECT
    f.id_fact,
    t.año,
    t.mes,
    t.hora,
    e.nombre AS edificio,
    a.nombre AS ambiente,
    a.tipo AS tipo_ambiente,
    o.porcentaje AS ocupacion,
    f.temperatura,
    f.demanda_pico_kw,
    f.factor_potencia,
    f.consumo_kwh,
    f.eficiencia,
    f.riesgo_sobreconsumo
FROM FactConsumoEnergetico f
LEFT JOIN DimTiempo t ON f.id_tiempo = t.id_tiempo
LEFT JOIN DimEdificio e ON f.id_edificio = e.id_edificio
LEFT JOIN DimAmbiente a ON f.id_ambiente = a.id_ambiente
LEFT JOIN DimOcupacion o ON f.id_ocupacion = o.id_ocupacion
ORDER BY f.id_fact DESC
LIMIT 50;
