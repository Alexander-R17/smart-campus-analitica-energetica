<?php

/**
 * Repositorio heredado conservado por compatibilidad.
 * La versión actual ya no usa MySQL; delega las predicciones al repositorio Supabase.
 */
class FactConsumoRepository
{
    public function importCsv(string $csvPath): int
    {
        if (!file_exists($csvPath)) {
            throw new RuntimeException('No existe el CSV procesado: ' . $csvPath);
        }
        $csvText = file_get_contents($csvPath);
        $repo = new CloudWarehouseRepository();
        $batchId = $repo->currentBatchId() ?: 0;
        return $repo->importPredictionsCsv((string)$csvText, $batchId);
    }

    public function countRows(): int
    {
        $repo = new CloudWarehouseRepository();
        $status = $repo->warehouseStatus($repo->currentBatchId());
        return (int)($status['counts']['pred'] ?? 0);
    }

    public function ensureTable(): void
    {
        (new CloudWarehouseRepository())->ensureSchema();
    }

    public function truncate(): void
    {
        Database::supabase()->delete('fact_consumo_energetico_pred', ['id' => 'gte.0']);
    }
}
