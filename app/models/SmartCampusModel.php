<?php
require_once __DIR__ . '/../config/Database.php';

class SmartCampusModel {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function importCsv(string $filePath): array {
        if (!file_exists($filePath)) {
            return ['ok' => false, 'message' => 'No se encontró el archivo temporal.', 'inserted' => 0];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['ok' => false, 'message' => 'No se pudo abrir el archivo CSV.', 'inserted' => 0];
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = $this->detectDelimiter($firstLine ?: '');

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            return ['ok' => false, 'message' => 'El CSV está vacío o no tiene encabezados.', 'inserted' => 0];
        }

        $headers = array_map([$this, 'normalizeHeader'], $headers);
        $inserted = 0;
        $skipped = 0;
        $errors = [];

        $this->db->beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                    continue;
                }

                $data = [];
                foreach ($headers as $idx => $key) {
                    $data[$key] = $row[$idx] ?? null;
                }

                try {
                    $this->insertNormalizedRow($data);
                    $inserted++;
                } catch (Throwable $e) {
                    $skipped++;
                    if (count($errors) < 8) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            fclose($handle);
            return ['ok' => false, 'message' => 'Error al importar: ' . $e->getMessage(), 'inserted' => $inserted];
        }

        fclose($handle);
        return [
            'ok' => true,
            'message' => 'CSV procesado correctamente.',
            'inserted' => $inserted,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function insertNormalizedRow(array $data): void {
        $idTiempo = (int)$this->pick($data, ['id_tiempo', 'tiempo_id'], 0);
        $hora = (int)$this->pick($data, ['hora'], 0);
        $mes = (int)$this->pick($data, ['mes'], 1);
        $anio = (int)$this->pick($data, ['anio', 'ano', 'año', 'year'], 2026);

        $idEdificio = (int)$this->pick($data, ['id_edificio', 'edificio_id'], 0);
        $idAmbiente = (int)$this->pick($data, ['id_ambiente', 'ambiente_id'], 0);
        $ocupacion = $this->normalizeNumber($this->pick($data, ['ocupacion', 'porcentaje_ocupacion'], 0), 'porcentaje');
        $cantidadPersonas = (int)round($ocupacion);

        $temperatura = $this->normalizeNumber($this->pick($data, ['temperatura', 'temp'], 0), 'temperatura');
        $demandaPico = $this->normalizeNumber($this->pick($data, ['demanda_pico_kw', 'pico_demanda_kw', 'demanda_pico'], 0), 'demanda');
        $factorPotencia = $this->normalizeNumber($this->pick($data, ['factor_potencia'], 0), 'factor');
        $consumoKwh = $this->normalizeNumber($this->pick($data, ['consumo_kwh', 'consumo'], 0), 'consumo');

        if ($idTiempo <= 0 || $idEdificio <= 0 || $idAmbiente <= 0) {
            throw new Exception('Faltan IDs obligatorios: id_tiempo, id_edificio o id_ambiente.');
        }

        $eficiencia = $ocupacion > 0 ? round($consumoKwh / max($ocupacion, 1), 4) : 0;
        $riesgo = ($consumoKwh >= 300 || $demandaPico >= 40 || $eficiencia >= 6 || ($ocupacion <= 25 && $consumoKwh >= 180)) ? 1 : 0;

        $this->insertDimTiempo($idTiempo, $hora, $mes, $anio);
        $this->insertDimEdificio($idEdificio);
        $this->insertDimAmbiente($idAmbiente);
        $idOcupacion = $this->insertDimOcupacion($cantidadPersonas, $ocupacion);
        $this->insertFact($idTiempo, $idEdificio, $idAmbiente, $idOcupacion, $consumoKwh, $demandaPico, $factorPotencia, $temperatura, $eficiencia, $riesgo);
    }

    private function insertDimTiempo(int $id, int $hora, int $mes, int $anio): void {
        $stmt = $this->db->prepare("INSERT IGNORE INTO DimTiempo (id_tiempo, hora, mes, año, dia_semana) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $hora, $mes, $anio, $this->diaSemanaSimulado($id)]);
    }

    private function insertDimEdificio(int $id): void {
        $stmt = $this->db->prepare("INSERT IGNORE INTO DimEdificio (id_edificio, nombre, tipo) VALUES (?, ?, ?)");
        $stmt->execute([$id, 'Edificio ' . $id, $this->tipoEdificio($id)]);
    }

    private function insertDimAmbiente(int $id): void {
        $tipo = $this->tipoAmbiente($id);
        $capacidad = $this->capacidadAmbiente($tipo);
        $stmt = $this->db->prepare("INSERT IGNORE INTO DimAmbiente (id_ambiente, nombre, tipo, capacidad) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $tipo . ' ' . $id, $tipo, $capacidad]);
    }

    private function insertDimOcupacion(int $cantidad, float $porcentaje): int {
        $stmt = $this->db->prepare("INSERT INTO DimOcupacion (cantidad_personas, porcentaje) VALUES (?, ?)");
        $stmt->execute([$cantidad, $porcentaje]);
        return (int)$this->db->lastInsertId();
    }

    private function insertFact(int $idTiempo, int $idEdificio, int $idAmbiente, int $idOcupacion, float $consumo, float $demanda, float $factor, float $temperatura, float $eficiencia, int $riesgo): void {
        $stmt = $this->db->prepare("INSERT INTO FactConsumoEnergetico
            (id_tiempo, id_edificio, id_ambiente, id_ocupacion, consumo_kwh, demanda_pico_kw, factor_potencia, temperatura, eficiencia, riesgo_sobreconsumo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$idTiempo, $idEdificio, $idAmbiente, $idOcupacion, $consumo, $demanda, $factor, $temperatura, $eficiencia, $riesgo]);
    }

    public function getWarehouseRows(int $limit = 500, array $filters = []): array {
        $limit = max(1, min($limit, 10000));
        [$whereSql, $params] = $this->buildWhere($filters);
        $sql = "SELECT
                    f.id_fact,
                    t.id_tiempo,
                    t.`año` AS anio,
                    t.mes,
                    t.hora,
                    t.dia_semana,
                    e.id_edificio,
                    e.nombre AS edificio,
                    e.tipo AS tipo_edificio,
                    a.id_ambiente,
                    a.nombre AS ambiente,
                    a.tipo AS tipo_ambiente,
                    a.capacidad,
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
                {$whereSql}
                ORDER BY t.`año` DESC, t.mes DESC, t.hora DESC, f.id_fact DESC
                LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getKpis(array $filters = []): array {
        $rows = $this->getWarehouseRows(10000, $filters);
        $filterOptions = $this->getFilterOptions();
        $totalDbRows = $this->getTotalFactRows();

        if (empty($rows)) {
            return [
                'hasData' => false,
                'totalDbRows' => $totalDbRows,
                'filteredRows' => 0,
                'filterOptions' => $filterOptions,
                'activeFilters' => $filters,
                'totalConsumo' => 0,
                'consumoActual' => 0,
                'variacion' => 0,
                'causaPrincipal' => 'No existen registros para los filtros aplicados.',
                'prediccion6Meses' => 0,
                'accionRecomendada' => 'Quite filtros o cargue datos CSV.',
                'seriesMes' => [],
                'seriesPrediccion' => [],
                'topEdificios' => [],
                'topAmbientes' => [],
                'distribucionAmbiente' => [],
                'histogramaHoras' => [],
                'riesgoPorMes' => [],
                'demandaAmbientes' => [],
                'periodosAltoConsumo' => [],
                'demandaPorMes' => [],
                'factorPorMes' => [],
                'eficienciaPorMes' => [],
                'heatmapHoraMes' => [],
                'scatterTempConsumo' => [],
                'rankingRiesgo' => [],
                'warehouseRows' => [],
            ];
        }

        $total = array_sum(array_map(fn($r) => (float)$r['consumo_kwh'], $rows));

        $porMes = [];
        $demandaMesStats = [];
        $factorMesStats = [];
        $eficienciaMesStats = [];
        $porEdificio = [];
        $porAmbiente = [];
        $porAmbienteNombre = [];
        $ocupacionPorAmbiente = [];
        $porHora = [];
        $porHoraMes = [];
        $riesgoPorMes = [];
        $riesgoPorEdificio = [];
        $registrosPorEdificio = [];
        $riesgoCount = 0;
        $bajaOcupacionAltoConsumo = 0;
        $demandaAlta = 0;
        $scatter = [];

        foreach ($rows as $r) {
            $mes = (int)($r['mes'] ?? 0);
            $hora = (int)($r['hora'] ?? 0);
            $edificio = $r['edificio'] ?? 'Sin edificio';
            $ambiente = $r['tipo_ambiente'] ?? 'Ambiente';
            $ambienteNombre = $r['ambiente'] ?? $ambiente;
            $consumo = (float)$r['consumo_kwh'];
            $ocup = (float)($r['ocupacion'] ?? 0);
            $demanda = (float)($r['demanda_pico_kw'] ?? 0);
            $factor = (float)($r['factor_potencia'] ?? 0);
            $eficiencia = (float)($r['eficiencia'] ?? 0);
            $temp = (float)($r['temperatura'] ?? 0);
            $riesgo = (int)($r['riesgo_sobreconsumo'] ?? 0);

            $porMes[$mes] = ($porMes[$mes] ?? 0) + $consumo;
            $porEdificio[$edificio] = ($porEdificio[$edificio] ?? 0) + $consumo;
            $porAmbiente[$ambiente] = ($porAmbiente[$ambiente] ?? 0) + $consumo;
            $porAmbienteNombre[$ambienteNombre] = ($porAmbienteNombre[$ambienteNombre] ?? 0) + $consumo;
            $porHora[$hora] = ($porHora[$hora] ?? 0) + $consumo;
            $riesgoPorMes[$mes] = ($riesgoPorMes[$mes] ?? 0) + $riesgo;
            $riesgoPorEdificio[$edificio] = ($riesgoPorEdificio[$edificio] ?? 0) + $riesgo;
            $registrosPorEdificio[$edificio] = ($registrosPorEdificio[$edificio] ?? 0) + 1;
            $porHoraMes[$mes . '-' . $hora] = ($porHoraMes[$mes . '-' . $hora] ?? 0) + $consumo;

            $this->pushAvg($demandaMesStats, $mes, $demanda);
            $this->pushAvg($factorMesStats, $mes, $factor);
            $this->pushAvg($eficienciaMesStats, $mes, $eficiencia);

            if (!isset($ocupacionPorAmbiente[$ambiente])) $ocupacionPorAmbiente[$ambiente] = ['sum' => 0, 'n' => 0];
            $ocupacionPorAmbiente[$ambiente]['sum'] += $ocup;
            $ocupacionPorAmbiente[$ambiente]['n'] += 1;

            $riesgoCount += $riesgo;
            if ($ocup <= 25 && $consumo >= 180) $bajaOcupacionAltoConsumo++;
            if ($demanda >= 40) $demandaAlta++;
            if (count($scatter) < 160) $scatter[] = ['label' => $temp . '°C', 'x' => $temp, 'y' => $consumo, 'r' => $riesgo];
        }

        ksort($porMes);
        ksort($porHora);
        ksort($riesgoPorMes);
        arsort($porEdificio);
        arsort($porAmbiente);
        arsort($porAmbienteNombre);

        $meses = array_keys($porMes);
        $actual = count($meses) ? end($meses) : 0;
        $anterior = count($meses) > 1 ? $meses[count($meses)-2] : 0;
        $consumoActual = $actual ? $porMes[$actual] : $total;
        $consumoAnterior = $anterior ? $porMes[$anterior] : max($consumoActual, 1);
        $variacion = $consumoAnterior > 0 ? (($consumoActual - $consumoAnterior) / $consumoAnterior) * 100 : 0;

        $causa = 'Mayor consumo asociado a demanda pico y ocupación elevada.';
        if ($bajaOcupacionAltoConsumo > $demandaAlta) {
            $causa = 'Alto consumo con baja ocupación: posible consumo fantasma o equipos activos fuera de horario no lectivo.';
        } elseif ($demandaAlta > 0) {
            $causa = 'Picos de demanda elevados en determinados horarios y edificios.';
        }

        $pred = $this->linearProjection($porMes, 6);
        $seriesPred = [];
        for ($i = 0; $i < count($pred); $i++) {
            $seriesPred[] = ['label' => 'Mes +' . ($i + 1), 'value' => round((float)$pred[$i], 2)];
        }

        $demandaAmbientes = [];
        foreach ($ocupacionPorAmbiente as $amb => $stats) {
            $demandaAmbientes[$amb] = $stats['n'] > 0 ? $stats['sum'] / $stats['n'] : 0;
        }
        arsort($demandaAmbientes);

        arsort($porHora);
        $periodosAlto = array_slice($porHora, 0, 7, true);
        ksort($porHora);

        $rankingRiesgo = [];
        foreach ($riesgoPorEdificio as $ed => $countRisk) {
            $totalEd = max(1, $registrosPorEdificio[$ed] ?? 1);
            $rankingRiesgo[$ed] = round(($countRisk / $totalEd) * 100, 2);
        }
        arsort($rankingRiesgo);

        $accion = 'Optimizar horarios, apagar equipos no usados y mejorar climatización.';
        if ($riesgoCount > 0) {
            $accion = 'Priorizar edificios en riesgo, revisar climatización, apagar equipos fuera de horario y programar mantenimiento preventivo.';
        }

        return [
            'hasData' => true,
            'totalDbRows' => $totalDbRows,
            'filteredRows' => count($rows),
            'filterOptions' => $filterOptions,
            'activeFilters' => $filters,
            'totalConsumo' => round($total, 2),
            'consumoActual' => round($consumoActual, 2),
            'variacion' => round($variacion, 2),
            'causaPrincipal' => $causa,
            'prediccion6Meses' => round(array_sum($pred), 2),
            'accionRecomendada' => $accion,
            'seriesMes' => $this->mapAssocToSeries($this->labelMeses($porMes)),
            'seriesPrediccion' => $seriesPred,
            'topEdificios' => $this->mapAssocToSeries(array_slice($porEdificio, 0, 8, true)),
            'topAmbientes' => $this->mapAssocToSeries(array_slice($porAmbienteNombre, 0, 8, true)),
            'distribucionAmbiente' => $this->mapAssocToSeries($porAmbiente),
            'histogramaHoras' => $this->mapAssocToSeries($this->labelHoras($porHora)),
            'riesgoPorMes' => $this->mapAssocToSeries($this->labelMeses($riesgoPorMes)),
            'demandaAmbientes' => $this->mapAssocToSeries($demandaAmbientes),
            'periodosAltoConsumo' => $this->mapAssocToSeries($this->labelHoras($periodosAlto)),
            'demandaPorMes' => $this->avgMapToSeries($demandaMesStats),
            'factorPorMes' => $this->avgMapToSeries($factorMesStats),
            'eficienciaPorMes' => $this->avgMapToSeries($eficienciaMesStats),
            'heatmapHoraMes' => $this->mapAssocToSeries($porHoraMes),
            'scatterTempConsumo' => $scatter,
            'rankingRiesgo' => $this->mapAssocToSeries(array_slice($rankingRiesgo, 0, 8, true)),
            'warehouseRows' => array_slice($rows, 0, 150),
        ];
    }

    public function getFilterOptions(): array {
        $safe = fn($sql) => $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        try {
            return [
                'anios' => $safe("SELECT DISTINCT `año` FROM DimTiempo ORDER BY `año` DESC"),
                'meses' => $safe("SELECT DISTINCT mes FROM DimTiempo ORDER BY mes"),
                'edificios' => $safe("SELECT DISTINCT nombre FROM DimEdificio ORDER BY nombre"),
                'tiposAmbiente' => $safe("SELECT DISTINCT tipo FROM DimAmbiente ORDER BY tipo"),
                'riesgos' => ['0' => 'Normal', '1' => 'Alto riesgo'],
            ];
        } catch (Throwable $e) {
            return ['anios'=>[], 'meses'=>[], 'edificios'=>[], 'tiposAmbiente'=>[], 'riesgos'=>['0'=>'Normal','1'=>'Alto riesgo']];
        }
    }

    public function resetWarehouse(): void {
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->exec('TRUNCATE TABLE FactConsumoEnergetico');
        $this->db->exec('TRUNCATE TABLE DimOcupacion');
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function buildWhere(array $filters): array {
        $where = [];
        $params = [];
        $clean = fn($v) => is_string($v) ? trim($v) : $v;

        $anio = $clean($filters['anio'] ?? '');
        if ($anio !== '' && $anio !== 'todos') { $where[] = 't.`año` = :anio'; $params[':anio'] = (int)$anio; }
        $mes = $clean($filters['mes'] ?? '');
        if ($mes !== '' && $mes !== 'todos') { $where[] = 't.mes = :mes'; $params[':mes'] = (int)$mes; }
        $edificio = $clean($filters['edificio'] ?? '');
        if ($edificio !== '' && $edificio !== 'todos') { $where[] = 'e.nombre = :edificio'; $params[':edificio'] = $edificio; }
        $tipo = $clean($filters['tipo_ambiente'] ?? '');
        if ($tipo !== '' && $tipo !== 'todos') { $where[] = 'a.tipo = :tipo_ambiente'; $params[':tipo_ambiente'] = $tipo; }
        $riesgo = $clean($filters['riesgo'] ?? '');
        if ($riesgo !== '' && $riesgo !== 'todos') { $where[] = 'f.riesgo_sobreconsumo = :riesgo'; $params[':riesgo'] = (int)$riesgo; }
        $horaDesde = $clean($filters['hora_desde'] ?? '');
        if ($horaDesde !== '' && is_numeric($horaDesde)) { $where[] = 't.hora >= :hora_desde'; $params[':hora_desde'] = (int)$horaDesde; }
        $horaHasta = $clean($filters['hora_hasta'] ?? '');
        if ($horaHasta !== '' && is_numeric($horaHasta)) { $where[] = 't.hora <= :hora_hasta'; $params[':hora_hasta'] = (int)$horaHasta; }
        $minConsumo = $clean($filters['min_consumo'] ?? '');
        if ($minConsumo !== '' && is_numeric($minConsumo)) { $where[] = 'f.consumo_kwh >= :min_consumo'; $params[':min_consumo'] = (float)$minConsumo; }

        return [count($where) ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function getTotalFactRows(): int {
        try { return (int)$this->db->query('SELECT COUNT(*) FROM FactConsumoEnergetico')->fetchColumn(); }
        catch (Throwable $e) { return 0; }
    }

    private function pushAvg(array &$map, int|string $key, float $value): void {
        if (!isset($map[$key])) $map[$key] = ['sum' => 0, 'n' => 0];
        $map[$key]['sum'] += $value;
        $map[$key]['n'] += 1;
    }

    private function avgMapToSeries(array $map): array {
        ksort($map);
        $assoc = [];
        foreach ($map as $key => $stats) {
            $assoc[$this->monthName((int)$key)] = $stats['n'] ? round($stats['sum'] / $stats['n'], 2) : 0;
        }
        return $this->mapAssocToSeries($assoc);
    }

    private function labelMeses(array $assoc): array {
        $out = [];
        foreach ($assoc as $k => $v) $out[$this->monthName((int)$k)] = $v;
        return $out;
    }

    private function labelHoras(array $assoc): array {
        $out = [];
        foreach ($assoc as $k => $v) $out[str_pad((string)$k, 2, '0', STR_PAD_LEFT) . ':00'] = $v;
        return $out;
    }

    private function monthName(int $m): string {
        $names = [1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Set',10=>'Oct',11=>'Nov',12=>'Dic'];
        return $names[$m] ?? ('Mes ' . $m);
    }

    private function detectDelimiter(string $line): string {
        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    private function normalizeHeader(string $header): string {
        $header = trim($header);
        $header = str_replace(['á','é','í','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'], ['a','e','i','o','u','n','A','E','I','O','U','N'], $header);
        $header = strtolower($header);
        $header = preg_replace('/[^a-z0-9_]+/', '_', $header);
        return trim($header, '_');
    }

    private function pick(array $data, array $keys, mixed $default): mixed {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== '' && $data[$key] !== null) return $data[$key];
        }
        return $default;
    }

    private function normalizeNumber(mixed $value, string $type): float {
        if ($value === null || $value === '') return 0.0;
        $raw = trim((string)$value);
        $raw = str_replace([' ', "\t"], '', $raw);
        if (str_contains($raw, ',') && !str_contains($raw, '.')) {
            $raw = str_replace(',', '.', $raw);
        }
        $raw = preg_replace('/[^0-9\.\-]/', '', $raw);
        if ($raw === '' || $raw === '-' || $raw === '.') return 0.0;
        $num = (float)$raw;

        if (abs($num) > 1000) {
            $digits = strlen(preg_replace('/[^0-9]/', '', (string)(int)abs($num)));
            if ($type === 'factor') {
                $num = $num / pow(10, max($digits, 1));
            } elseif ($type === 'temperatura' || $type === 'demanda') {
                $num = $num / pow(10, max($digits - 2, 1));
            }
        }
        return round($num, 4);
    }

    private function diaSemanaSimulado(int $id): string {
        $dias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
        return $dias[$id % 7];
    }

    private function tipoEdificio(int $id): string {
        $tipos = ['Académico', 'Administrativo', 'Laboratorio', 'Biblioteca'];
        return $tipos[$id % count($tipos)];
    }

    private function tipoAmbiente(int $id): string {
        if ($id % 5 === 0) return 'Laboratorio';
        if ($id % 4 === 0) return 'Oficina';
        if ($id % 3 === 0) return 'Auditorio';
        return 'Aula';
    }

    private function capacidadAmbiente(string $tipo): int {
        return match($tipo) {
            'Laboratorio' => 35,
            'Oficina' => 20,
            'Auditorio' => 120,
            default => 45,
        };
    }

    private function linearProjection(array $series, int $n): array {
        if (count($series) === 0) return array_fill(0, $n, 0);
        $values = array_values($series);
        if (count($values) === 1) return array_fill(0, $n, (float)$values[0]);
        $count = count($values);
        $xMean = ($count + 1) / 2;
        $yMean = array_sum($values) / $count;
        $num = 0; $den = 0;
        for ($i = 0; $i < $count; $i++) {
            $x = $i + 1;
            $num += ($x - $xMean) * ($values[$i] - $yMean);
            $den += ($x - $xMean) ** 2;
        }
        $slope = $den == 0 ? 0 : $num / $den;
        $intercept = $yMean - $slope * $xMean;
        $pred = [];
        for ($i = 1; $i <= $n; $i++) {
            $x = $count + $i;
            $pred[] = max(0, $intercept + $slope * $x);
        }
        return $pred;
    }

    private function mapAssocToSeries(array $assoc): array {
        $out = [];
        foreach ($assoc as $label => $value) {
            $out[] = ['label' => (string)$label, 'value' => round((float)$value, 2)];
        }
        return $out;
    }
}
