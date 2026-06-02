<?php

class FactConsumoRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connect();
    }

    public function ensureTable(): void
    {
        $this->pdo->exec(<<<SQL
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
SQL);
    }

    public function truncate(): void
    {
        $this->pdo->exec('TRUNCATE TABLE fact_consumo_energetico_pred');
    }

    public function importCsv(string $csvPath): int
    {
        if (!file_exists($csvPath)) {
            throw new RuntimeException('No existe el CSV procesado: ' . $csvPath);
        }

        $this->ensureTable();
        $this->truncate();

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            throw new RuntimeException('No se pudo abrir el CSV procesado.');
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new RuntimeException('El CSV procesado está vacío.');
        }

        $sql = <<<SQL
INSERT INTO fact_consumo_energetico_pred (
    id_tiempo, hora, mes, anio, id_edificio, id_ambiente,
    ocupacion, temperatura, demanda_pico_kw, factor_potencia,
    consumo_kwh, nombre_edificio, tipo_ambiente, nombre_mes,
    periodo, franja_horaria, eficiencia_energetica_pct,
    riesgo_energetico_indice, pred_consumo_kwh, sobreconsumo_real,
    riesgo_sobreconsumo_prob, riesgo_sobreconsumo_pred
) VALUES (
    ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?,
    ?, ?, ?,
    ?, ?
)
SQL;

        $stmt = $this->pdo->prepare($sql);
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            $data = array_combine($headers, $row);

            $stmt->execute([
                $this->nullIfEmpty($data['id_tiempo'] ?? null),
                $this->nullIfEmpty($data['hora'] ?? null),
                $this->nullIfEmpty($data['mes'] ?? null),
                $this->nullIfEmpty($data['anio'] ?? null),
                $this->nullIfEmpty($data['id_edificio'] ?? null),
                $this->nullIfEmpty($data['id_ambiente'] ?? null),
                $this->nullIfEmpty($data['ocupacion'] ?? null),
                $this->nullIfEmpty($data['temperatura'] ?? null),
                $this->nullIfEmpty($data['demanda_pico_kw'] ?? null),
                $this->nullIfEmpty($data['factor_potencia'] ?? null),
                $this->nullIfEmpty($data['consumo_kwh'] ?? null),
                $this->nullIfEmpty($data['nombre_edificio'] ?? null),
                $this->nullIfEmpty($data['tipo_ambiente'] ?? null),
                $this->nullIfEmpty($data['nombre_mes'] ?? null),
                $this->nullIfEmpty($data['periodo'] ?? null),
                $this->nullIfEmpty($data['franja_horaria'] ?? null),
                $this->nullIfEmpty($data['eficiencia_energetica_pct'] ?? null),
                $this->nullIfEmpty($data['riesgo_energetico_indice'] ?? null),
                $this->nullIfEmpty($data['pred_consumo_kwh'] ?? null),
                $this->nullIfEmpty($data['sobreconsumo_real'] ?? null),
                $this->nullIfEmpty($data['riesgo_sobreconsumo_prob'] ?? null),
                $this->nullIfEmpty($data['riesgo_sobreconsumo_pred'] ?? null),
            ]);

            $count++;
        }

        fclose($handle);
        return $count;
    }

    public function countRows(): int
    {
        $this->ensureTable();
        return (int)$this->pdo->query('SELECT COUNT(*) FROM fact_consumo_energetico_pred')->fetchColumn();
    }

    private function nullIfEmpty($value)
    {
        return ($value === '' || $value === null) ? null : $value;
    }
}
