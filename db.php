<?php
// db.php
$userProfile = getenv('USERPROFILE') ?: getenv('HOME');
$dataDir = $userProfile . DIRECTORY_SEPARATOR . 'Documents' . DIRECTORY_SEPARATOR . 'GestionCaja';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$db_file = $dataDir . DIRECTORY_SEPARATOR . 'caja.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sqlTurnos = "CREATE TABLE IF NOT EXISTS turnos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fecha DATE NOT NULL,
        turno TEXT NOT NULL,
        fondo_inicial REAL DEFAULT 0,
        total_tarjeta REAL DEFAULT 0,
        total_efectivo REAL DEFAULT 0,
        gastos_efectivo REAL DEFAULT 0,
        proveedor TEXT,
        efectivo_contado REAL DEFAULT 0,
        descuadre REAL DEFAULT 0
    )";
    $pdo->exec($sqlTurnos);

    $sqlUsuarios = "CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL
    )";
    $pdo->exec($sqlUsuarios);

    $sqlConfig = "CREATE TABLE IF NOT EXISTS configuracion (
        clave TEXT PRIMARY KEY,
        valor TEXT
    )";
    $pdo->exec($sqlConfig);

    $stmt = $pdo->query("SELECT COUNT(*) FROM configuracion");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO configuracion (clave, valor) VALUES ('nombre_empresa', 'Gestión de Caja')");
        $pdo->exec("INSERT INTO configuracion (clave, valor) VALUES ('logo_base64', '')");
    }

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>