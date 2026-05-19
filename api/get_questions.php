<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_login();

header('Content-Type: application/json');

$mode     = $_GET['mode']     ?? 'mock';
$topic_id = (int)($_GET['topic_id'] ?? 0);
$limit    = (int)($_GET['limit']    ?? 100);

// Cap limits per mode
if ($mode === 'mock')  $limit = 100;
if ($mode === 'drill') $limit = min($limit, 50);
if ($mode === 'topic') $limit = min($limit, 30);

try {
    if ($mode === 'topic' && $topic_id > 0) {
        $stmt = $pdo->prepare("
            SELECT id, topic_id, question_text,
                   choice_a, choice_b, choice_c, choice_d
            FROM questions
            WHERE topic_id = ?
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->execute([$topic_id, $limit]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, topic_id, question_text,
                   choice_a, choice_b, choice_c, choice_d
            FROM questions
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->execute([$limit]);
    }

    $questions = $stmt->fetchAll();
    echo json_encode(['success' => true, 'questions' => $questions]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}