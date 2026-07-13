<?php

class CloudWarehouseRepository
{
    private SupabaseRestClient $db;

    public function __construct()
    {
        $this->db = Database::supabase();
    }

    public function ensureSchema(): void
    {
        // Supabase no permite crear tablas por REST. Ejecuta primero:
        // database/supabase_copo_nieve_smartcampus.sql en SQL Editor.
        try {
            $this->db->select('dimedificio', ['select' => 'id_edificio', 'limit' => '1']);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo leer Supabase. Ejecuta database/supabase_copo_nieve_smartcampus.sql en Supabase SQL Editor y revisa SUPABASE_URL/API_KEY. Detalle: ' . $e->getMessage());
        }
    }

    public function createBatch(array $fileNames): int
    {
        $this->ensureSchema();
        $rows = $this->db->insert('staging_upload_batch', [[
            'nombre_lote' => 'lote_' . date('Ymd_His'),
            'archivos' => array_values($fileNames),
            'estado' => 'RECIBIDO',
            'registros_crudos' => 0,
            'registros_dw' => 0,
            'registros_predichos' => 0,
            'actualizado_en' => date('c'),
        ]]);
        return (int)($rows[0]['batch_id'] ?? 0);
    }

    public function importCsvToStaging(string $csvPath, int $batchId, string $origenArchivo = 'csv_upload'): int
    {
        $this->ensureSchema();
        if (!file_exists($csvPath)) {
            throw new RuntimeException('No existe el CSV temporal para cargar a Supabase.');
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            throw new RuntimeException('No se pudo abrir el CSV temporal.');
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = $this->detectDelimiter($firstLine ?: '');
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            throw new RuntimeException('El CSV no contiene cabeceras.');
        }

        $headers = array_map(fn($h) => $this->normalizeHeader((string)$h), $headers);
        $this->validateRequiredHeaders($headers);

        $buffer = [];
        $count = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }
            $row = array_pad($row, count($headers), '');
            $data = array_combine($headers, array_slice($row, 0, count($headers)));
            if (!$data) {
                continue;
            }

            $idEdificio = (int)$this->value($data, 'id_edificio', 0);
            $idAmbiente = (int)$this->value($data, 'id_ambiente', 0);

            $buffer[] = [
                'batch_id' => $batchId,
                'origen_archivo' => $origenArchivo,
                'id_tiempo' => $this->intOrNull($this->value($data, 'id_tiempo')),
                'hora' => $this->intOrNull($this->value($data, 'hora')),
                'mes' => $this->intOrNull($this->value($data, 'mes')),
                'anio' => $this->intOrNull($this->value($data, 'anio')),
                'id_edificio' => $idEdificio ?: null,
                'id_ambiente' => $idAmbiente ?: null,
                'ocupacion' => $this->decimalOrNull($this->value($data, 'ocupacion')),
                'temperatura' => $this->decimalOrNull($this->value($data, 'temperatura')),
                'demanda_pico_kw' => $this->decimalOrNull($this->value($data, 'demanda_pico_kw')),
                'factor_potencia' => $this->decimalOrNull($this->value($data, 'factor_potencia')),
                'consumo_kwh' => $this->decimalOrNull($this->value($data, 'consumo_kwh')),
                'nombre_edificio' => $this->value($data, 'nombre_edificio', $idEdificio ? 'Edificio ' . $idEdificio : null),
                'tipo_ambiente' => $this->value($data, 'tipo_ambiente', $this->inferTipoAmbiente($idAmbiente)),
                'payload_json' => $data,
            ];
            $count++;

            if (count($buffer) >= 500) {
                $this->db->insert('staging_lectura_cruda', $buffer, false);
                $buffer = [];
            }
        }
        fclose($handle);

        if ($buffer) {
            $this->db->insert('staging_lectura_cruda', $buffer, false);
        }

        $this->incrementBatchRawRows($batchId, $count, 'STAGING_COMPLETADO');
        return $count;
    }

    public function validateStaging(int $batchId): array
    {
        $this->ensureSchema();
        $rows = $this->db->selectAll('staging_lectura_cruda', [
            'select' => 'id_staging,origen_archivo,anio,mes,hora,consumo_kwh',
            'batch_id' => 'eq.' . $batchId,
            'order' => 'id_staging.asc'
        ]);

        $registros = count($rows);
        $fuentes = [];
        $anioMin = $anioMax = $mesMin = $mesMax = null;
        $consumoNulos = 0;
        $tiempoIncompleto = 0;

        foreach ($rows as $r) {
            $fuentes[(string)($r['origen_archivo'] ?? 'csv')] = true;
            $anio = $r['anio'] ?? null;
            $mes = $r['mes'] ?? null;
            if ($anio !== null) {
                $anioMin = $anioMin === null ? (int)$anio : min($anioMin, (int)$anio);
                $anioMax = $anioMax === null ? (int)$anio : max($anioMax, (int)$anio);
            }
            if ($mes !== null) {
                $mesMin = $mesMin === null ? (int)$mes : min($mesMin, (int)$mes);
                $mesMax = $mesMax === null ? (int)$mes : max($mesMax, (int)$mes);
            }
            if ($r['consumo_kwh'] === null || $r['consumo_kwh'] === '') {
                $consumoNulos++;
            }
            if (($r['hora'] ?? null) === null || ($r['mes'] ?? null) === null || ($r['anio'] ?? null) === null) {
                $tiempoIncompleto++;
            }
        }

        $ok = $registros > 0 && $consumoNulos === 0 && $tiempoIncompleto === 0;
        $this->updateBatchStatus($batchId, $ok ? 'VALIDADO' : 'OBSERVADO');

        return [
            'ok' => $ok,
            'registros' => $registros,
            'fuentes' => count($fuentes),
            'periodo' => [
                'anio_min' => $anioMin,
                'anio_max' => $anioMax,
                'mes_min' => $mesMin,
                'mes_max' => $mesMax,
            ],
            'observaciones' => [
                'consumo_nulos' => $consumoNulos,
                'tiempo_incompleto' => $tiempoIncompleto,
            ]
        ];
    }

    public function runEtlToSnowflake(int $batchId): array
    {
        $this->ensureSchema();
        $batch = $this->getBatch($batchId);

        // Si se reejecuta el mismo lote, limpiar solo el rango de hechos generado por ese lote.
        if (!empty($batch['id_fact_min']) && !empty($batch['id_fact_max'])) {
            $this->db->delete('fact_consumo_energetico', [
                'and' => '(id_fact.gte.' . (int)$batch['id_fact_min'] . ',id_fact.lte.' . (int)$batch['id_fact_max'] . ')'
            ]);
        }

        $raw = $this->db->selectAll('staging_lectura_cruda', [
            'select' => '*',
            'batch_id' => 'eq.' . $batchId,
            'order' => 'id_staging.asc'
        ]);
        if (!$raw) {
            throw new RuntimeException('No hay registros staging para ejecutar ETL.');
        }

        $dimTiempo = [];
        $dimEdificio = [];
        $dimAmbiente = [];
        foreach ($raw as &$r) {
            $r['id_tiempo_final'] = $this->resolveIdTiempo($r);
            $idTiempo = (int)$r['id_tiempo_final'];
            $hora = (int)($r['hora'] ?? 0);
            $mes = (int)($r['mes'] ?? 1);
            $anio = (int)($r['anio'] ?? 2026);
            $dimTiempo[$idTiempo] = [
                'id_tiempo' => $idTiempo,
                'hora' => $hora,
                'mes' => $mes,
                'anio' => $anio,
                'dia_semana' => $this->diaSemanaSimulado($idTiempo),
            ];

            $idEdificio = (int)($r['id_edificio'] ?? 0);
            if ($idEdificio > 0) {
                $dimEdificio[$idEdificio] = [
                    'id_edificio' => $idEdificio,
                    'nombre' => $r['nombre_edificio'] ?: 'Edificio ' . $idEdificio,
                    'tipo' => $this->tipoEdificio($idEdificio),
                ];
            }

            $idAmbiente = (int)($r['id_ambiente'] ?? 0);
            if ($idAmbiente > 0) {
                $tipoAmbiente = $r['tipo_ambiente'] ?: $this->inferTipoAmbiente($idAmbiente);
                $dimAmbiente[$idAmbiente] = [
                    'id_ambiente' => $idAmbiente,
                    'nombre' => $tipoAmbiente . ' ' . $idAmbiente,
                    'tipo' => $tipoAmbiente,
                    'capacidad' => $this->capacidadAmbiente($tipoAmbiente),
                ];
            }
        }
        unset($r);

        $this->upsertChunks('dimtiempo', array_values($dimTiempo), 'id_tiempo');
        $this->upsertChunks('dimedificio', array_values($dimEdificio), 'id_edificio');
        $this->upsertChunks('dimambiente', array_values($dimAmbiente), 'id_ambiente');

        $facts = [];
        $idFactMin = null;
        $idFactMax = null;
        foreach ($raw as $r) {
            $ocupacion = (float)($r['ocupacion'] ?? 0);
            $cantidad = (int)round($ocupacion);
            $porcentaje = (int)max(0, min(100, round($ocupacion)));
            $ocupRows = $this->db->insert('dimocupacion', [[
                'cantidad_personas' => $cantidad,
                'porcentaje' => $porcentaje,
            ]], true);
            $idOcupacion = (int)($ocupRows[0]['id_ocupacion'] ?? 0);

            $consumo = (float)($r['consumo_kwh'] ?? 0);
            $demanda = (float)($r['demanda_pico_kw'] ?? 0);
            $factor = (float)($r['factor_potencia'] ?? 0);
            $temperatura = (float)($r['temperatura'] ?? 0);
            $eficiencia = $ocupacion > 0 ? round($consumo / max($ocupacion, 1), 4) : 0;
            $riesgo = ($consumo >= 300 || $demanda >= 40 || $eficiencia >= 6 || ($ocupacion <= 25 && $consumo >= 180)) ? 1 : 0;

            $facts[] = [
                'id_tiempo' => (int)$r['id_tiempo_final'],
                'id_edificio' => $this->intOrNull($r['id_edificio'] ?? null),
                'id_ambiente' => $this->intOrNull($r['id_ambiente'] ?? null),
                'id_ocupacion' => $idOcupacion,
                'consumo_kwh' => $consumo,
                'demanda_pico_kw' => $demanda,
                'factor_potencia' => $factor,
                'temperatura' => $temperatura,
                'eficiencia' => $eficiencia,
                'riesgo_sobreconsumo' => $riesgo,
            ];

            if (count($facts) >= 250) {
                $inserted = $this->db->insert('fact_consumo_energetico', $facts, true);
                foreach ($inserted as $ins) {
                    $id = (int)($ins['id_fact'] ?? 0);
                    if ($id > 0) {
                        $idFactMin = $idFactMin === null ? $id : min($idFactMin, $id);
                        $idFactMax = $idFactMax === null ? $id : max($idFactMax, $id);
                    }
                }
                $facts = [];
            }
        }

        if ($facts) {
            $inserted = $this->db->insert('fact_consumo_energetico', $facts, true);
            foreach ($inserted as $ins) {
                $id = (int)($ins['id_fact'] ?? 0);
                if ($id > 0) {
                    $idFactMin = $idFactMin === null ? $id : min($idFactMin, $id);
                    $idFactMax = $idFactMax === null ? $id : max($idFactMax, $id);
                }
            }
        }

        $this->updateBatchStatus($batchId, 'DW_SUPABASE_COMPLETADO', null, null, [
            'registros_dw' => count($raw),
            'id_fact_min' => $idFactMin,
            'id_fact_max' => $idFactMax,
        ]);

        return $this->warehouseStatus($batchId);
    }

    public function warehouseStatus(?int $batchId = null): array
    {
        $this->ensureSchema();
        $counts = [
            'staging' => $batchId ? $this->countRows('staging_lectura_cruda', ['batch_id' => 'eq.' . $batchId]) : $this->countRows('staging_lectura_cruda'),
            'fact' => $batchId ? $this->countFactsForBatch($batchId) : $this->countRows('fact_consumo_energetico'),
            'pred' => $this->countRows('fact_consumo_energetico_pred'),
            'dim_tiempo' => $this->countRows('dimtiempo'),
            'dim_edificio' => $this->countRows('dimedificio'),
            'dim_ambiente' => $this->countRows('dimambiente'),
            'dim_ocupacion' => $this->countRows('dimocupacion'),
        ];
        return ['ok' => true, 'batch_id' => $batchId, 'counts' => $counts, 'engine' => 'Supabase PostgreSQL'];
    }

    public function exportFactCsv(int $batchId): string
    {
        $this->ensureSchema();
        $batch = $this->getBatch($batchId);
        $filters = ['select' => '*', 'order' => 'id_fact.asc', 'limit' => '100000'];
        if (!empty($batch['id_fact_min']) && !empty($batch['id_fact_max'])) {
            $filters['id_fact'] = 'gte.' . (int)$batch['id_fact_min'];
            // PostgREST cannot accept same key twice via array; filter in PHP below for <= max.
        }
        $facts = $this->db->selectAll('fact_consumo_energetico', $filters);
        if (!empty($batch['id_fact_max'])) {
            $max = (int)$batch['id_fact_max'];
            $facts = array_values(array_filter($facts, fn($f) => (int)$f['id_fact'] <= $max));
        }
        if (!$facts) {
            throw new RuntimeException('Supabase no tiene hechos para enviar a Colab. Ejecuta el paso ETL primero.');
        }

        $tiempos = $this->mapBy($this->db->selectAll('dimtiempo', ['select' => '*']), 'id_tiempo');
        $edificios = $this->mapBy($this->db->selectAll('dimedificio', ['select' => '*']), 'id_edificio');
        $ambientes = $this->mapBy($this->db->selectAll('dimambiente', ['select' => '*']), 'id_ambiente');
        $ocupaciones = $this->mapBy($this->db->selectAll('dimocupacion', ['select' => '*']), 'id_ocupacion');

        $out = fopen('php://temp', 'r+');
        fputcsv($out, [
            'id_tiempo','hora','mes','anio','id_edificio','id_ambiente','ocupacion','temperatura',
            'demanda_pico_kw','factor_potencia','consumo_kwh','nombre_edificio','tipo_ambiente',
            'nombre_mes','periodo','franja_horaria'
        ]);

        foreach ($facts as $f) {
            $t = $tiempos[(int)$f['id_tiempo']] ?? [];
            $e = $edificios[(int)$f['id_edificio']] ?? [];
            $a = $ambientes[(int)$f['id_ambiente']] ?? [];
            $o = $ocupaciones[(int)$f['id_ocupacion']] ?? [];
            $anio = (int)($t['anio'] ?? 2026);
            $mes = (int)($t['mes'] ?? 1);
            $hora = (int)($t['hora'] ?? 0);
            fputcsv($out, [
                $f['id_tiempo'] ?? '',
                $hora,
                $mes,
                $anio,
                $f['id_edificio'] ?? '',
                $f['id_ambiente'] ?? '',
                $o['porcentaje'] ?? 0,
                $f['temperatura'] ?? 0,
                $f['demanda_pico_kw'] ?? 0,
                $f['factor_potencia'] ?? 0,
                $f['consumo_kwh'] ?? 0,
                $e['nombre'] ?? ('Edificio ' . ($f['id_edificio'] ?? '')),
                $a['tipo'] ?? 'Ambiente',
                $this->nombreMes($mes),
                $anio . '-' . str_pad((string)$mes, 2, '0', STR_PAD_LEFT),
                $this->franjaHoraria($hora),
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv ?: '';
    }

    public function importPredictionsCsv(string $csvText, int $batchId): int
    {
        $this->ensureSchema();
        // La tabla predictiva original no tiene batch_id; se reemplaza por la ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âºltima corrida IA.
        try {
            $this->db->delete('fact_consumo_energetico_pred', ['id' => 'gte.0']);
        } catch (Throwable $e) {
            // Si estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ vacÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­a, Supabase puede responder sin filas; continuar.
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csvText);
        rewind($handle);
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new RuntimeException('El CSV predictivo devuelto por Colab estÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ vacÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­o.');
        }
        $headers = array_map(fn($h) => $this->normalizeHeader((string)$h), $headers);

        $buffer = [];
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }
            $row = array_pad($row, count($headers), '');
            $data = array_combine($headers, array_slice($row, 0, count($headers)));
            if (!$data) {
                continue;
            }

            $buffer[] = [
                'id_tiempo' => $this->intOrNull($this->value($data, 'id_tiempo')),
                'hora' => $this->intOrNull($this->value($data, 'hora')),
                'mes' => $this->intOrNull($this->value($data, 'mes')),
                'anio' => $this->intOrNull($this->value($data, 'anio')),
                'id_edificio' => $this->intOrNull($this->value($data, 'id_edificio')),
                'id_ambiente' => $this->intOrNull($this->value($data, 'id_ambiente')),
                'ocupacion' => $this->decimalOrNull($this->value($data, 'ocupacion')),
                'temperatura' => $this->decimalOrNull($this->value($data, 'temperatura')),
                'demanda_pico_kw' => $this->decimalOrNull($this->value($data, 'demanda_pico_kw')),
                'factor_potencia' => $this->decimalOrNull($this->value($data, 'factor_potencia')),
                'consumo_kwh' => $this->decimalOrNull($this->value($data, 'consumo_kwh')),
                'nombre_edificio' => $this->value($data, 'nombre_edificio'),
                'tipo_ambiente' => $this->value($data, 'tipo_ambiente'),
                'nombre_mes' => $this->value($data, 'nombre_mes'),
                'periodo' => $this->value($data, 'periodo'),
                'franja_horaria' => $this->value($data, 'franja_horaria'),
                'eficiencia_energetica_pct' => $this->decimalOrNull($this->value($data, 'eficiencia_energetica_pct')),
                'riesgo_energetico_indice' => $this->value($data, 'riesgo_energetico_indice'),
                'pred_consumo_kwh' => $this->decimalOrNull($this->value($data, 'pred_consumo_kwh')),
                'sobreconsumo_real' => $this->intOrNull($this->value($data, 'sobreconsumo_real')),
                'riesgo_sobreconsumo_prob' => $this->decimalOrNull($this->value($data, 'riesgo_sobreconsumo_prob')),
                'riesgo_sobreconsumo_pred' => $this->value($data, 'riesgo_sobreconsumo_pred'),
            ];
            $count++;
            if (count($buffer) >= 500) {
                $this->db->insert('fact_consumo_energetico_pred', $buffer, false);
                $buffer = [];
            }
        }
        fclose($handle);

        if ($buffer) {
            $this->db->insert('fact_consumo_energetico_pred', $buffer, false);
        }

        $this->updateBatchStatus($batchId, 'PREDICCION_COMPLETADA', null, $count);
        return $count;
    }

    public function currentBatchId(): ?int
    {
        return isset($_SESSION['current_batch_id']) ? (int)$_SESSION['current_batch_id'] : null;
    }

    public function requireCurrentBatchId(): int
    {
        $batchId = $this->currentBatchId();
        if (!$batchId) {
            throw new RuntimeException('No existe lote activo. Primero sube archivos en el paso 1.');
        }
        return $batchId;
    }

    private function incrementBatchRawRows(int $batchId, int $rows, string $status): void
    {
        $batch = $this->getBatch($batchId);
        $current = (int)($batch['registros_crudos'] ?? 0);
        $this->updateBatchStatus($batchId, $status, $current + $rows);
    }

    private function updateBatchStatus(int $batchId, string $status, ?int $rawRows = null, ?int $predRows = null, array $extra = []): void
    {
        $values = array_merge([
            'estado' => $status,
            'actualizado_en' => date('c'),
        ], $extra);
        if ($rawRows !== null) {
            $values['registros_crudos'] = $rawRows;
        }
        if ($predRows !== null) {
            $values['registros_predichos'] = $predRows;
        }
        $this->db->update('staging_upload_batch', $values, ['batch_id' => 'eq.' . $batchId]);
    }

    private function getBatch(int $batchId): array
    {
        $rows = $this->db->select('staging_upload_batch', [
            'select' => '*',
            'batch_id' => 'eq.' . $batchId,
            'limit' => '1'
        ]);
        return $rows[0] ?? [];
    }

    private function countFactsForBatch(int $batchId): int
    {
        $batch = $this->getBatch($batchId);
        if (empty($batch['id_fact_min']) || empty($batch['id_fact_max'])) {
            return 0;
        }
        $rows = $this->db->selectAll('fact_consumo_energetico', [
            'select' => 'id_fact',
            'id_fact' => 'gte.' . (int)$batch['id_fact_min']
        ]);
        $max = (int)$batch['id_fact_max'];
        return count(array_filter($rows, fn($r) => (int)$r['id_fact'] <= $max));
    }

    private function countRows(string $table, array $filters = []): int
    {
        try {
            return $this->db->count($table, $filters);
        } catch (Throwable $e) {
            $rows = $this->db->selectAll($table, array_merge(['select' => '*'], $filters));
            return count($rows);
        }
    }

    private function upsertChunks(string $table, array $rows, string $onConflict): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            $this->db->upsert($table, $chunk, $onConflict, false);
        }
    }

    private function mapBy(array $rows, string $key): array
    {
        $map = [];
        foreach ($rows as $r) {
            if (isset($r[$key])) {
                $map[(int)$r[$key]] = $r;
            }
        }
        return $map;
    }

    private function validateRequiredHeaders(array $headers): void
    {
        $required = ['hora', 'mes', 'anio', 'id_edificio', 'id_ambiente', 'ocupacion', 'temperatura', 'demanda_pico_kw', 'factor_potencia', 'consumo_kwh'];
        $missing = array_values(array_diff($required, $headers));
        if ($missing) {
            throw new RuntimeException('El CSV no tiene las columnas requeridas: ' . implode(', ', $missing));
        }
    }

    private function normalizeHeader(string $header): string
    {
        $header = trim(strtolower($header));
        $header = str_replace([' ', '-', '.', 'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡', 'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©', 'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­', 'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â³', 'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Âº', 'ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â±'], ['_', '_', '_', 'a', 'e', 'i', 'o', 'u', 'n'], $header);
        $header = preg_replace('/[^a-z0-9_]/', '', $header) ?: 'columna';
        return $header === 'ano' ? 'anio' : $header;
    }

    private function detectDelimiter(string $line): string
    {
        return substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    private function value(array $data, string $key, $default = null)
    {
        return array_key_exists($key, $data) && trim((string)$data[$key]) !== '' ? trim((string)$data[$key]) : $default;
    }

    private function intOrNull($value): ?int
    {
        return ($value === null || $value === '') ? null : (int)$value;
    }

    private function decimalOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float)str_replace(',', '.', (string)$value);
    }

    private function resolveIdTiempo(array $r): int
    {
        $id = (int)($r['id_tiempo'] ?? 0);
        if ($id > 0) {
            return $id;
        }
        $anio = (int)($r['anio'] ?? 2026);
        $mes = (int)($r['mes'] ?? 1);
        $hora = (int)($r['hora'] ?? 0);
        return (int)($anio . str_pad((string)$mes, 2, '0', STR_PAD_LEFT) . str_pad((string)$hora, 2, '0', STR_PAD_LEFT));
    }

    private function inferTipoAmbiente(int $idAmbiente): string
    {
        $tipos = ['Aula', 'Laboratorio', 'Auditorio', 'Biblioteca', 'Oficina', 'Administrativo'];
        return $tipos[abs($idAmbiente) % count($tipos)];
    }

    private function capacidadAmbiente(string $tipo): int
    {
        return match ($tipo) {
            'Auditorio' => 120,
            'Laboratorio' => 35,
            'Oficina' => 20,
            'Administrativo' => 30,
            'Biblioteca' => 80,
            default => 45,
        };
    }

    private function tipoEdificio(int $id): string
    {
        return match ($id % 4) {
            0 => 'AcadÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©mico',
            1 => 'Administrativo',
            2 => 'Laboratorio',
            default => 'Biblioteca',
        };
    }

    private function diaSemanaSimulado(int $id): string
    {
        $dias = ['Lunes', 'Martes', 'MiÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©rcoles', 'Jueves', 'Viernes', 'SÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡bado', 'Domingo'];
        return $dias[abs($id) % count($dias)];
    }

    private function nombreMes(int $mes): string
    {
        $meses = [1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic'];
        return $meses[$mes] ?? 'Sin mes';
    }

    private function franjaHoraria(int $hora): string
    {
        if ($hora >= 6 && $hora <= 11) return 'MaÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Â ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â±ana';
        if ($hora >= 12 && $hora <= 17) return 'Tarde';
        return 'Noche';
    }
}