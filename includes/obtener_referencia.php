<?php
require_once 'functions.php';
require_once '../config/database.php';

verificarPermiso('administrador');

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['error' => 'ID de referencia no proporcionado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT * FROM Referencias WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $referencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$referencia) {
        echo json_encode(['error' => 'Referencia no encontrada']);
        exit;
    }

    echo json_encode($referencia);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error al obtener la referencia: ' . $e->getMessage()]);
}
?>