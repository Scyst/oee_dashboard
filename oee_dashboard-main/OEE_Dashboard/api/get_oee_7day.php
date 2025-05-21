<?php
header('Content-Type: application/json');
include 'db.php'; // Adjust if needed

try {
    $stmt = $pdo->prepare("
        SELECT log_date AS date,
               ROUND(AVG(oee), 2) AS oee,
               ROUND(AVG(quality), 2) AS quality,
               ROUND(AVG(performance), 2) AS performance,
               ROUND(AVG(availability), 2) AS availability
        FROM oee_logs
        WHERE log_date >= CURDATE() - INTERVAL 6 DAY
        GROUP BY log_date
        ORDER BY log_date ASC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'records' => $data
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
