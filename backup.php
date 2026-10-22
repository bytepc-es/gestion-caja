<?php
session_start();
if (!isset($_SESSION['user_id'])) { die("Acceso denegado."); }

$userProfile = getenv('USERPROFILE') ?: getenv('HOME');
$db_file = $userProfile . DIRECTORY_SEPARATOR . 'Documents' . DIRECTORY_SEPARATOR . 'GestionCaja' . DIRECTORY_SEPARATOR . 'caja.sqlite';

if (file_exists($db_file)) {
    $nombre_descarga = 'Copia_Caja_' . date('Y-m-d_H-i') . '.sqlite';
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $nombre_descarga . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($db_file));
    readfile($db_file);
    exit;
} else { die("No se encontró la base de datos."); }
?>