<?php

class AuthRepository
{
    private SupabaseRestClient $db;

    public function __construct()
    {
        $this->db = Database::supabase();
    }

    /**
     * Busca un usuario activo sin diferenciar mayúsculas
     * y minúsculas.
     */
    public function findActiveUser(
        string $username
    ): ?array {
        $usernameNormalizado = strtolower(
            trim($username)
        );

        if ($usernameNormalizado === '') {
            return null;
        }

        $rows = $this->db->select(
            'app_usuarios',
            [
                'select' =>
                    'id,username,username_normalizado,nombre_completo,password_hash,rol,activo',
                'username_normalizado' =>
                    'eq.' . $usernameNormalizado,
                'activo' => 'eq.true',
                'limit' => '1'
            ]
        );

        return $rows[0] ?? null;
    }

    /**
     * Crea una sesión real por cada inicio de sesión correcto.
     */
    public function createSession(
        int $usuarioId
    ): array {
        $ip =
            $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? null;

        if (
            is_string($ip)
            && str_contains($ip, ',')
        ) {
            $ip = trim(
                explode(',', $ip)[0]
            );
        }

        $userAgent = substr(
            (string)(
                $_SERVER['HTTP_USER_AGENT']
                ?? 'No identificado'
            ),
            0,
            500
        );

        $rows = $this->db->insert(
            'app_sesiones',
            [[
                'usuario_id' => $usuarioId,
                'activa' => true,
                'ip' => $ip,
                'user_agent' => $userAgent
            ]],
            true
        );

        if (!isset($rows[0]['id'])) {
            throw new RuntimeException(
                'No se pudo registrar la sesión en Supabase.'
            );
        }

        return $rows[0];
    }

    /**
     * Actualiza la fecha del último ingreso.
     */
    public function updateLastAccess(
        int $usuarioId
    ): void {
        $this->db->update(
            'app_usuarios',
            [
                'ultimo_acceso' => date('c')
            ],
            [
                'id' => 'eq.' . $usuarioId
            ]
        );
    }

    /**
     * Cierra una sesión cuando el usuario pulsa Salir.
     */
    public function closeSession(
        string $sessionId
    ): bool {
        $sessionId = trim($sessionId);

        if (
            $sessionId === ''
            || !preg_match(
                '/^[0-9a-fA-F-]{36}$/',
                $sessionId
            )
        ) {
            return false;
        }

        $rows = $this->db->update(
            'app_sesiones',
            [
                'activa' => false,
                'fin' => date('c')
            ],
            [
                'id' => 'eq.' . $sessionId,
                'activa' => 'eq.true'
            ]
        );

        return count($rows) > 0;
    }
}