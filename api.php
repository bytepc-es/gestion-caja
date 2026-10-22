<?php
// api.php
session_start();
header('Content-Type: application/json');
require 'db.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register_first_user') {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Ya existe un usuario. Vaya al login.']);
        exit;
    }
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    if (empty($usuario) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password) VALUES (?, ?)");
    try {
        $stmt->execute([$usuario, $hash]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['usuario'] = $usuario;
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al crear usuario: ' . $e->getMessage()]);
    }
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

if ($action === 'get_data') {
    $start = $_GET['start'] ?? date('Y-m-d');
    $end = $_GET['end'] ?? date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM turnos WHERE fecha BETWEEN ? AND ? ORDER BY fecha DESC, id DESC");
    $stmt->execute([$start, $end]);
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tot_tarjeta = 0; $tot_efectivo = 0; $tot_gastos = 0; $tot_descuadre = 0;
    foreach ($turnos as $t) {
        $tot_tarjeta += $t['total_tarjeta'];
        $tot_efectivo += $t['total_efectivo'];
        $tot_gastos += $t['gastos_efectivo'];
        $tot_descuadre += $t['descuadre'];
    }
    echo json_encode([
        'status' => 'success',
        'turnos' => $turnos,
        'totales' => [
            'tarjeta' => number_format($tot_tarjeta, 2),
            'efectivo' => number_format($tot_efectivo, 2),
            'gastos' => number_format($tot_gastos, 2),
            'descuadre' => number_format($tot_descuadre, 2)
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_turno') {
    $fecha = $_POST['fecha']; $turno = $_POST['turno']; $proveedor = $_POST['proveedor'] ?? '';
    $fondo = floatval($_POST['fondo_inicial']); $tarjeta = floatval($_POST['tarjeta']); $efectivo = floatval($_POST['efectivo']);
    $gastos = floatval($_POST['gastos']); $contado = floatval($_POST['contado']);
    $esperado = $fondo + $efectivo - $gastos;
    $descuadre = $contado - $esperado;
    $stmt = $pdo->prepare("INSERT INTO turnos (fecha, turno, fondo_inicial, total_tarjeta, total_efectivo, gastos_efectivo, proveedor, efectivo_contado, descuadre) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$fecha, $turno, $fondo, $tarjeta, $efectivo, $gastos, $proveedor, $contado, $descuadre]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_turno') {
    $id = $_POST['id']; $fecha = $_POST['fecha']; $turno = $_POST['turno']; $proveedor = $_POST['proveedor'] ?? '';
    $fondo = floatval($_POST['fondo_inicial']); $tarjeta = floatval($_POST['tarjeta']); $efectivo = floatval($_POST['efectivo']);
    $gastos = floatval($_POST['gastos']); $contado = floatval($_POST['contado']);
    $esperado = $fondo + $efectivo - $gastos;
    $descuadre = $contado - $esperado;
    $stmt = $pdo->prepare("UPDATE turnos SET fecha=?, turno=?, fondo_inicial=?, total_tarjeta=?, total_efectivo=?, gastos_efectivo=?, proveedor=?, efectivo_contado=?, descuadre=? WHERE id=?");
    try {
        $stmt->execute([$fecha, $turno, $fondo, $tarjeta, $efectivo, $gastos, $proveedor, $contado, $descuadre, $id]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_turno') {
    $id = $_POST['id'] ?? 0;
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM turnos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_config') {
    $nombre = $_POST['nombre_empresa'] ?? '';
    $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'nombre_empresa'");
    $stmt->execute([$nombre]);

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = pathinfo($_FILES['logo']['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $imageData = file_get_contents($_FILES['logo']['tmp_name']);
            $base64 = 'data:image/' . $extension . ';base64,' . base64_encode($imageData);
            $stmtLogo = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'logo_base64'");
            $stmtLogo->execute([$base64]);
        }
    }
    echo json_encode(['status' => 'success']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'change_password') {
    $actual = $_POST['actual']; $nueva = $_POST['nueva']; $id_user = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$id_user]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($actual, $user['password'])) {
        $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $update->execute([$nuevoHash, $id_user]);
        echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'La contraseña actual es incorrecta']);
    }
    exit;
}
?>