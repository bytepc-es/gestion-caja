<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']); exit; 
}

$userProfile = getenv('USERPROFILE') ?: getenv('HOME');
$db_file = $userProfile . DIRECTORY_SEPARATOR . 'Documents' . DIRECTORY_SEPARATOR . 'GestionCaja' . DIRECTORY_SEPARATOR . 'caja.sqlite';

if (file_exists($db_file)) {
    $tempDir = __DIR__ . DIRECTORY_SEPARATOR . 'temp_downloads';
    if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
    array_map('unlink', glob($tempDir . DIRECTORY_SEPARATOR . '*'));

    $nombre_archivo = 'Copia_Caja_' . date('Y-m-d_H-i') . '.sqlite';
    $ruta_fisica = $tempDir . DIRECTORY_SEPARATOR . $nombre_archivo;

    if (copy($db_file, $ruta_fisica)) {
        echo json_encode(['status' => 'success', 'url' => 'temp_downloads/' . $nombre_archivo]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al copiar el archivo en el servidor.']);
    }
} else { 
    echo json_encode(['status' => 'error', 'message' => 'No se encontró la base de datos.']); 
}
?>