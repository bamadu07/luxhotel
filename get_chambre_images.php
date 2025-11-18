<?php
// get_chambre_images.php
include 'config/database.php';

if (isset($_GET['id'])) {
    $chambre_id = intval($_GET['id']);
    
    try {
        $stmt = $pdo->prepare("SELECT images FROM chambres WHERE id = ?");
        $stmt->execute([$chambre_id]);
        $chambre = $stmt->fetch();
        
        if ($chambre) {
            $images = json_decode($chambre['images'] ?? '[]', true);
            header('Content-Type: application/json');
            echo json_encode(['images' => $images]);
        } else {
            echo json_encode(['images' => []]);
        }
    } catch (PDOException $e) {
        echo json_encode(['images' => []]);
    }
} else {
    echo json_encode(['images' => []]);
}
?>