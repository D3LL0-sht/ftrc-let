<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/config.php';
require_login();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

try {
    // Topic analytics
    $stmt = $pdo->prepare("
        SELECT t.name, ta.total_attempts,
               ta.correct_count, ta.accuracy_pct
        FROM topic_analytics ta
        JOIN topics t ON t.id = ta.topic_id
        WHERE ta.user_id = ?
        ORDER BY ta.accuracy_pct ASC
    ");
    $stmt->execute([$user_id]);
    $topics = $stmt->fetchAll();

    // Exam history
    $stmt = $pdo->prepare("
        SELECT es.id, es.mode, es.submitted_at,
               COUNT(sq.id) as total,
               SUM(sq.is_correct) as correct
        FROM exam_sessions es
        LEFT JOIN session_questions sq ON sq.session_id = es.id
        WHERE es.user_id = ? AND es.submitted_at IS NOT NULL
        GROUP BY es.id
        ORDER BY es.submitted_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $history = $stmt->fetchAll();

    // Pass prediction
    $prediction = pass_prediction($pdo, $user_id);

    echo json_encode([
        'success'    => true,
        'topics'     => $topics,
        'history'    => $history,
        'prediction' => $prediction,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}