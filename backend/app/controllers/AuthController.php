<?php

class AuthController
{
    /**
     * Lee JSON enviado desde Firebase.
     * También acepta formularios POST normales.
     */
    private function input(): array
    {
        $raw = file_get_contents(
            'php://input'
        );

        if (
            is_string($raw)
            && trim($raw) !== ''
        ) {
            $json = json_decode(
                $raw,
                true
            );

            if (is_array($json)) {
                return $json;
            }
        }

        return $_POST;
    }

    public function login(): void
    {
        $input = $this->input();

        $username = trim(
            (string)(
                $input['username']
                ?? ''
            )
        );

        $password = (string)(
            $input['password']
            ?? ''
        );

        if (
            $username === ''
            || $password === ''
        ) {
            json_response([
                'ok' => false,
                'error' =>
                    'Debes ingresar usuario y contraseña.'
            ], 422);
        }

        $repo = new AuthRepository();

        $usuario = $repo->findActiveUser(
            $username
        );

        if (!$usuario) {
            json_response([
                'ok' => false,
                'error' =>
                    'Usuario o contraseña incorrectos.'
            ], 401);
        }

        $hash = (string)(
            $usuario['password_hash']
            ?? ''
        );

        if (
            $hash === ''
            || !password_verify(
                $password,
                $hash
            )
        ) {
            json_response([
                'ok' => false,
                'error' =>
                    'Usuario o contraseña incorrectos.'
            ], 401);
        }

        $sesion = $repo->createSession(
            (int)$usuario['id']
        );

        $repo->updateLastAccess(
            (int)$usuario['id']
        );

        json_response([
            'ok' => true,
            'message' =>
                'Inicio de sesión correcto.',
            'usuario' => [
                'id' =>
                    (int)$usuario['id'],
                'username' =>
                    (string)$usuario['username'],
                'nombre_completo' =>
                    (string)$usuario['nombre_completo'],
                'rol' =>
                    (string)$usuario['rol']
            ],
            'sesion_id' =>
                (string)$sesion['id'],
            'inicio' =>
                $sesion['inicio']
                ?? null
        ]);
    }

    public function logout(): void
    {
        $input = $this->input();

        $sessionId = trim(
            (string)(
                $input['sesion_id']
                ?? ''
            )
        );

        if ($sessionId === '') {
            json_response([
                'ok' => false,
                'error' =>
                    'No se recibió el identificador de sesión.'
            ], 422);
        }

        $repo = new AuthRepository();

        $cerrada = $repo->closeSession(
            $sessionId
        );

        json_response([
            'ok' => true,
            'message' => $cerrada
                ? 'Sesión cerrada correctamente.'
                : 'La sesión ya estaba cerrada o no existe.'
        ]);
    }
}