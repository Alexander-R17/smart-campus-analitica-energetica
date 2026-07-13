<?php

class CloudController
{
    /**
     * Obtiene el lote enviado por Firebase.
     * Si no se envía, utiliza la sesión anterior como respaldo.
     */
    private function getBatchId(
        CloudWarehouseRepository $repo
    ): int {
        $batchId =
            $_POST['batch_id']
            ?? $_GET['batch_id']
            ?? null;

        if (
            $batchId !== null
            && filter_var(
                $batchId,
                FILTER_VALIDATE_INT
            ) !== false
            && (int)$batchId > 0
        ) {
            return (int)$batchId;
        }

        return $repo->requireCurrentBatchId();
    }

    public function validateStaging(): void
    {
        $repo = new CloudWarehouseRepository();

        $batchId = $this->getBatchId($repo);

        $result = $repo->validateStaging($batchId);

        if (!($result['ok'] ?? false)) {
            json_response([
                'ok' => false,
                'message' => 'La validación en Supabase encontró observaciones.',
                'batch_id' => $batchId,
                'detalle' => $result
            ], 422);

            return;
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

        $batchId = $this->getBatchId($repo);

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

        $batchId =
            $_GET['batch_id']
            ?? $_POST['batch_id']
            ?? $repo->currentBatchId();

        if (
            $batchId !== null
            && filter_var(
                $batchId,
                FILTER_VALIDATE_INT
            ) !== false
        ) {
            $batchId = (int)$batchId;
        } else {
            $batchId = null;
        }

        $result = $repo->warehouseStatus($batchId);

        json_response([
            'ok' => true,
            'message' => 'Estado actual del Data Warehouse Supabase.',
            'batch_id' => $batchId,
            'warehouse' => $result
        ]);
    }
}