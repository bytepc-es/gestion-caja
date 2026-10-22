<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require 'db.php';
$config = $pdo->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_KEY_PAIR);
$nombreEmpresa = $config['nombre_empresa'] ?? 'Gestión de Caja';
$logoBase64 = $config['logo_base64'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ajustes - <?= htmlspecialchars($nombreEmpresa) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; } .card { max-width: 700px; margin: 40px auto; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); } </style>
</head>
<body>
    <div class="container">
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="fw-bold mb-0">⚙️ Ajustes de Empresa</h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Volver a la Caja</a>
            </div>
            <div class="card-body p-4">
                <form id="formAjustes" enctype="multipart/form-data">
                    <div class="mb-4 text-center">
                        <?php if (!empty($logoBase64)): ?>
                            <img src="<?= $logoBase64 ?>" alt="Logo actual" class="img-thumbnail mb-2" style="max-height: 120px;">
                        <?php else: ?>
                            <div class="text-muted mb-2">No hay logo configurado</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre de la Empresa</label>
                        <input type="text" name="nombre_empresa" class="form-control" value="<?= htmlspecialchars($nombreEmpresa) ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Subir Nuevo Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/png, image/jpeg, image/webp">
                        <small class="text-muted">Formatos aceptados: JPG, PNG, WEBP.</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Guardar Cambios</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white py-3"><h5 class="fw-bold mb-0">🔒 Seguridad (Usuario: <?= htmlspecialchars($_SESSION['usuario']) ?>)</h5></div>
            <div class="card-body p-4">
                <form id="formPassword">
                    <div class="mb-3"><label class="form-label fw-bold">Contraseña Actual</label><input type="password" name="actual" class="form-control" required></div>
                    <div class="mb-4"><label class="form-label fw-bold">Nueva Contraseña</label><input type="password" name="nueva" class="form-control" required></div>
                    <button type="submit" class="btn btn-warning w-100 py-2 fw-bold">Cambiar Contraseña</button>
                </form>
            </div>
        </div>

        <!-- FOOTER DE AUTORÍA -->
        <div class="text-center mt-4 mb-5 text-muted" style="font-size: 0.85rem;">
            Creado por <a href="https://bytespc.es" target="_blank" class="text-decoration-none">Bytespc.es</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('formAjustes').addEventListener('submit', function(e) {
            e.preventDefault(); const formData = new FormData(this);
            fetch('api.php?action=save_config', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if(data.status === 'success') { window.location.reload(); } });
        });
        document.getElementById('formPassword').addEventListener('submit', function(e) {
            e.preventDefault(); const formData = new FormData(this);
            fetch('api.php?action=change_password', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                if(data.status === 'success') { alert("Contraseña cambiada correctamente."); this.reset(); } else { alert(data.message); }
            });
        });
    </script>
</body>
</html>