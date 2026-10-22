<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios"); $hayUsuarios = $stmt->fetchColumn() > 0;
$config = $pdo->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_KEY_PAIR);
$nombreEmpresa = $config['nombre_empresa'] ?? 'Gestión de Caja';
$logoBase64 = $config['logo_base64'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hayUsuarios) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?"); $stmt->execute([$_POST['usuario']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id']; $_SESSION['usuario'] = $user['usuario'];
        header("Location: index.php"); exit;
    } else { $error = "Usuario o contraseña incorrectos"; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><title>Login - <?= htmlspecialchars($nombreEmpresa) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; } .login-card { width: 100%; max-width: 400px; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); background: white; border-top: 5px solid #0d6efd; } .logo-img { max-height: 80px; max-width: 100%; object-fit: contain; margin-bottom: 15px; } </style>
</head>
<body>
    <div>
        <div class="login-card">
            <div class="text-center mb-4">
                <?php if (!empty($logoBase64)): ?><img src="<?= $logoBase64 ?>" alt="Logo" class="logo-img"><?php endif; ?>
                <h4 class="fw-bold text-secondary"><?= htmlspecialchars($nombreEmpresa) ?></h4>
                <?php if (!$hayUsuarios): ?><p class="text-primary small fw-bold">¡Bienvenido! Cree su cuenta de administrador.</p>
                <?php else: ?><p class="text-muted small">Acceso Restringido</p><?php endif; ?>
            </div>
            <?php if ($error): ?><div class="alert alert-danger py-2 text-center small"><?= $error ?></div><?php endif; ?>
            <?php if ($hayUsuarios): ?>
                <form method="POST">
                    <div class="mb-3"><label class="form-label fw-bold">Usuario</label><input type="text" name="usuario" class="form-control" required autofocus></div>
                    <div class="mb-4"><label class="form-label fw-bold">Contraseña</label><input type="password" name="password" class="form-control" required></div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Entrar</button>
                </form>
            <?php else: ?>
                <form id="formRegistroInicial">
                    <div class="mb-3"><label class="form-label fw-bold">Nombre de Usuario</label><input type="text" name="usuario" id="reg-usuario" class="form-control" required autofocus></div>
                    <div class="mb-4"><label class="form-label fw-bold">Contraseña</label><input type="password" name="password" id="reg-password" class="form-control" required></div>
                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold">Crear Cuenta y Entrar</button>
                </form>
            <?php endif; ?>
        </div>
        <div class="text-center mt-3 text-muted" style="font-size: 0.85rem;">Creado por <a href="https://bytespc.es" target="_blank" class="text-decoration-none">Bytespc.es</a></div>
    </div>
    <?php if (!$hayUsuarios): ?>
    <script>
        document.getElementById('formRegistroInicial').addEventListener('submit', function(e) {
            e.preventDefault(); const formData = new FormData(this);
            fetch('api.php?action=register_first_user', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                if(data.status === 'success') { window.location.href = 'index.php'; } else { alert(data.message); }
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>