<?php

class CloudController
{
    public function validateStaging(): void
    {
        $repo = new CloudWarehouseRepository();
        $batchId = $repo->requireCurrentBatchId();
        $result = $repo->validateStaging($batchId);

        if (!$result['ok']) {
            json_response([
                'ok' => false,
                'message' => 'La validación en Supabase encontró observaciones.',
                'batch_id' => $batchId,
                'detalle' => $result
            ], 422);
        }

        json_response([
            'ok' => true,
            'message' => 'Staging Supabase validado correctamente.',
            'batch_id' => $batchId,
            'detalle' => $result
        ]);
    }

    public function runEtl(): void
    {
        $repo = new CloudWarehouseRepository();
        $batchId = $repo->requireCurrentBatchId();
        $result = $repo->runEtlToSnowflake($batchId);

        json_response([
            'ok' => true,
            'message' => 'ETL ejecutado. El modelo copo de nieve en Supabase quedó actualizado.',
            'batch_id' => $batchId,
            'warehouse' => $result
        ]);
    }

    public function status(): void
    {
        $repo = new CloudWarehouseRepository();
        $batchId = $repo->currentBatchId();
        $result = $repo->warehouseStatus($batchId);

        json_response([
            'ok' => true,
            'message' => 'Estado actual del Data Warehouse Supabase.',
            'batch_id' => $batchId,
            'warehouse' => $result
        ]);
    }
}
