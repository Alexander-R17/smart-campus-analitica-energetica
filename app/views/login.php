<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Smart Campus</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <main class="login-page">
        <section class="login-card upgraded-login">
            <form class="login-form" method="POST" action="index.php?route=authenticate">
                <span class="mini-label">ACCESO DE INGENIERÍA</span>
                <h2>Iniciar sesión</h2>
                <p class="login-subtitle">Ingrese sus credenciales para acceder al prototipo Smart Campus.</p>
                <?php if (!empty($error)): ?>
                    <div class="alert-error">Usuario o contraseña incorrectos.</div>
                <?php endif; ?>
                <label for="usuario">Usuario</label>
                <input id="usuario" type="text" name="usuario" placeholder="Ingeniero1" required>
                <label for="password">Contraseña</label>
                <input id="password" type="password" name="password" placeholder="12345678" required>
                <button type="submit" class="btn-primary full"><span class="btn-icon">🔐</span> Ingresar</button>
                <p class="login-hint">Usuario: <b>Ingeniero1</b> · Contraseña: <b>12345678</b></p>
            </form>
            <aside class="login-brand">
                <img src="assets/img/logo_UNuevaEsperanza.png" alt="Logo Universidad Nueva Esperanza">
                <h1>UNIVERSIDAD NUEVA<br>ESPERANZA</h1>
                <p>Sapientia et Innovatio</p>
            </aside>
        </section>
    </main>
</body>
</html>
