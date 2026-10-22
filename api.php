<?php
// api.php
session_start();
header('Content-Type: application/json');
require 'db.php';

$action = $_GET['action'] ?? '';

// --- GENERAR EXCEL (CSV) FÍSICO ---
if ($action === 'get_csv_data') {
    if (!isset($_SESSION['user_id'])) { echo json_encode(['status' => 'error', 'message' => 'No autorizado']); exit; }
    
    // 1. Crear carpeta temporal dentro de la app (donde sí hay permisos)
    $tempDir = __DIR__ . DIRECTORY_SEPARATOR . 'temp_downloads';
    if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
    
    // 2. Limpiar archivos antiguos para no llenar el disco
    array_map('unlink', glob($tempDir . DIRECTORY_SEPARATOR . '*'));

    // 3. Generar contenido del CSV
    $csv_content = chr(0xEF).chr(0xBB).chr(0xBF); // BOM para Excel
    $csv_content .= "ID;Fecha;Turno;Fondo Inicial;Factura Tarjeta;Factura Efectivo;Gastos;Proveedor;Efectivo Contado;Descuadre\n";
    
    $stmt = $pdo->query("SELECT * FROM turnos ORDER BY fecha DESC, id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $line = implode(';', [
            $row['id'], $row['fecha'], $row['turno'], $row['fondo_inicial'], 
            $row['total_tarjeta'], $row['total_efectivo'], $row['gastos_efectivo'], 
            $row['proveedor'], $row['efectivo_contado'], $row['descuadre']
        ]);
        $csv_content .= $line . "\n";
    }
    
    // 4. Guardar archivo
    $nombre_archivo = 'Historial_Caja_' . date('Ymd_His') . '.csv';
    $ruta_fisica = $tempDir . DIRECTORY_SEPARATOR . $nombre_archivo;
    file_put_contents($ruta_fisica, $csv_content);
    
    // 5. Devolver la URL relativa
    echo json_encode(['status' => 'success', 'url' => 'temp_downloads/' . $nombre_archivo]);
    exit;
}

// --- REGISTRO DEL PRIMER USUARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register_first_user') {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() > 0) { echo json_encode(['status' => 'error', 'message' => 'Ya existe un usuario.']); exit; }
    $usuario = trim($_POST['usuario']); $password = $_POST['password'];
    if (empty($usuario) || empty($password)) { echo json_encode(['status' => 'error', 'message' => 'Campos obligatorios.']); exit; }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password) VALUES (?, ?)");
    try {
        $stmt->execute([$usuario, $hash]);
        $_SESSION['user_id'] = $pdo->lastInsertId(); $_SESSION['usuario'] = $usuario;
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

if (!isset($_SESSION['user_id'])) { echo json_encode(['status' => 'error', 'message' => 'No autorizado']); exit; }

// --- OBTENER DATOS ---
if ($action === 'get_data') {
    $start = $_GET['start'] ?? date('Y-m-d'); $end = $_GET['end'] ?? date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT * FROM turnos WHERE fecha BETWEEN ? AND ? ORDER BY fecha DESC, id DESC");
    $stmt->execute([$start, $end]);
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tot_tarjeta = 0; $tot_efectivo = 0; $tot_gastos = 0; $tot_descuadre = 0;
    foreach ($turnos as $t) {
        $tot_tarjeta += $t['total_tarjeta']; $tot_efectivo += $t['total_efectivo'];
        $tot_gastos += $t['gastos_efectivo']; $tot_descuadre += $t['descuadre'];
    }

    $stmtChart = $pdo->prepare("SELECT fecha, SUM(total_tarjeta) as t, SUM(total_efectivo) as e, SUM(gastos_efectivo) as g FROM turnos WHERE fecha BETWEEN ? AND ? GROUP BY fecha ORDER BY fecha ASC");
    $stmtChart->execute([$start, $end]);
    $chartData = $stmtChart->fetchAll(PDO::FETCH_ASSOC);
    
    $chart_labels = []; $chart_tarjeta = []; $chart_efectivo = []; $chart_gastos = [];
    foreach ($chartData as $c) {
        $chart_labels[] = $c['fecha']; $chart_tarjeta[] = $c['t']; $chart_efectivo[] = $c['e']; $chart_gastos[] = $c['g'];
    }

    echo json_encode([
        'status' => 'success', 'turnos' => $turnos,
        'totales' => [
            'tarjeta' => number_format($tot_tarjeta, 2), 'efectivo' => number_format($tot_efectivo, 2),
            'gastos' => number_format($tot_gastos, 2), 'descuadre' => number_format($tot_descuadre, 2)
        ],
        'chart' => [ 'labels' => $chart_labels, 'tarjeta' => $chart_tarjeta, 'efectivo' => $chart_efectivo, 'gastos' => $chart_gastos ]
    ]);
    exit;
}

// --- GUARDAR TURNO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_turno') {
    $fecha = $_POST['fecha']; $turno = $_POST['turno']; $proveedor = $_POST['proveedor'] ?? '';
    $fondo = floatval($_POST['fondo_inicial']); $tarjeta = floatval($_POST['tarjeta']); $efectivo = floatval($_POST['efectivo']);
    $gastos = floatval($_POST['gastos']); $contado = floatval($_POST['contado']);
    $esperado = $fondo + $efectivo - $gastos; $descuadre = $contado - $esperado;
    $stmt = $pdo->prepare("INSERT INTO turnos (fecha, turno, fondo_inicial, total_tarjeta, total_efectivo, gastos_efectivo, proveedor, efectivo_contado, descuadre) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    try { $stmt->execute([$fecha, $turno, $fondo, $tarjeta, $efectivo, $gastos, $proveedor, $contado, $descuadre]); echo json_encode(['status' => 'success']); } 
    catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

// --- ACTUALIZAR TURNO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_turno') {
    $id = $_POST['id']; $fecha = $_POST['fecha']; $turno = $_POST['turno']; $proveedor = $_POST['proveedor'] ?? '';
    $fondo = floatval($_POST['fondo_inicial']); $tarjeta = floatval($_POST['tarjeta']); $efectivo = floatval($_POST['efectivo']);
    $gastos = floatval($_POST['gastos']); $contado = floatval($_POST['contado']);
    $esperado = $fondo + $efectivo - $gastos; $descuadre = $contado - $esperado;
    $stmt = $pdo->prepare("UPDATE turnos SET fecha=?, turno=?, fondo_inicial=?, total_tarjeta=?, total_efectivo=?, gastos_efectivo=?, proveedor=?, efectivo_contado=?, descuadre=? WHERE id=?");
    try { $stmt->execute([$fecha, $turno, $fondo, $tarjeta, $efectivo, $gastos, $proveedor, $contado, $descuadre, $id]); echo json_encode(['status' => 'success']); } 
    catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

// --- BORRAR TURNO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_turno') {
    $id = $_POST['id'] ?? 0;
    if ($id > 0) { $stmt = $pdo->prepare("DELETE FROM turnos WHERE id = ?"); $stmt->execute([$id]); echo json_encode(['status' => 'success']); }
    exit;
}

// --- GUARDAR CONFIGURACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_config') {
    $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'nombre_empresa'"); $stmt->execute([$_POST['nombre_empresa']]);
    $stmtM = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'horario_manana'"); $stmtM->execute([$_POST['horario_manana']]);
    $stmtT = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'horario_tarde'"); $stmtT->execute([$_POST['horario_tarde']]);

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = pathinfo($_FILES['logo']['name']); $extension = strtolower($fileInfo['extension']);
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $base64 = 'data:image/' . $extension . ';base64,' . base64_encode(file_get_contents($_FILES['logo']['tmp_name']));
            $stmtLogo = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'logo_base64'"); $stmtLogo->execute([$base64]);
        }
    }
    echo json_encode(['status' => 'success']); exit;
}

// --- CAMBIAR CONTRASEÑA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'change_password') {
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?"); $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($_POST['actual'], $user['password'])) {
        $update = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $update->execute([password_hash($_POST['nueva'], PASSWORD_DEFAULT), $_SESSION['user_id']]);
        echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada']);
    } else { echo json_encode(['status' => 'error', 'message' => 'Contraseña actual incorrecta']); }
    exit;
}
?>