<?php
session_start();
require_once 'functions.php';
require_once '../config/database.php';

if (!isset($_SESSION['cedula'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de referencia no proporcionado'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT tallas FROM referencias WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['tallas']) {
        $tallas = is_string($result['tallas']) ? json_decode($result['tallas'], true) : $result['tallas'];
        
        // Filtrar tallas con cantidad mayor a 0
        $tallasFiltradas = array_filter($tallas, function($cantidad) {
            return $cantidad > 0;
        });

        echo json_encode([
            'success' => true,
            'tallas' => $tallasFiltradas
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontraron tallas para esta referencia'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las tallas: ' . $e->getMessage()
    ]);
}