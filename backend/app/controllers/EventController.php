<?php

class EventController
{
    public function register(): void
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw ?: '{}', true);
        if (!is_array($payload)) {
            json_response(['ok' => false, 'error' => 'JSON inválido.'], 400);
        }

        $evento = trim((string)($payload['evento'] ?? 'evento_desconocido'));
        $usuario = trim((string)($payload['usuario_id'] ?? 'usuario_demo'));
        $sesion = trim((string)($payload['sesion_id'] ?? session_id()));

        $row = [[
            'fecha_hora' => date('c'),
            'fecha' => date('Y-m-d'),
            'usuario_id' => $usuario ?: 'usuario_demo',
            'sesion_id' => $sesion ?: session_id(),
            'evento' => $evento ?: 'evento_desconocido',
            'etapa_numero' => isset($payload['etapa_numero']) ? (int)$payload['etapa_numero'] : null,
            'etapa_nombre' => trim((string)($payload['etapa_nombre'] ?? '')),
            'dispositivo' => trim((string)($payload['dispositivo'] ?? 'Desktop')),
            'navegador' => substr(trim((string)($payload['navegador'] ?? '')), 0, 500),
            'resultado' => trim((string)($payload['resultado'] ?? 'exitoso')),
            'tiempo_seg' => isset($payload['tiempo_seg']) ? (int)$payload['tiempo_seg'] : null,
            'url_pagina' => substr(trim((string)($payload['url_pagina'] ?? '')), 0, 500),
        ]];

        try {
            $db = Database::supabase();
            $inserted = $db->insert('web_eventos', $row, true);
            json_response(['ok' => true, 'inserted' => count($inserted), 'evento' => $evento]);
        } catch (Throwable $e) {
            // No rompemos el flujo principal si la tabla de eventos aún no existe.
            json_response([
                'ok' => false,
                'error' => $e->getMessage(),
                'hint' => 'Ejecuta database/02_supabase_web_analytics_streamlit.sql en Supabase SQL Editor.'
            ], 200);
        }
    }
}
