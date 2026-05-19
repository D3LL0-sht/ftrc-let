<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_login();

header('Content-Type: application/json');

$session_id = (int)($_GET['session_id'] ?? 0);
$user_id    = $_SESSION['user_id'];

if (!$session_id) {
    echo json_encode(['success' => false, 'error' => 'Missing session_id']);
    exit;
}

try {
    // Get session
    $stmt = $pdo->prepare("
        SELECT es.*, u.name as student_name
        FROM exam_sessions es
        JOIN users u ON u.id = es.user_id
        WHERE es.id = ? AND es.user_id = ?
    ");
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'error' => 'Session not found']);
        exit;
    }

    // Get answers with question details
    $stmt = $pdo->prepare("
        SELECT sq.*,
               q.question_text, q.choice_a, q.choice_b,
               q.choice_c, q.choice_d, q.correct_answer,
               q.explanation, t.name as topic_name
        FROM session_questions sq
        JOIN questions q ON q.id = sq.question_id
        JOIN topics t ON t.id = q.topic_id
        WHERE sq.session_id = ?
        ORDER BY sq.id
    ");
    $stmt->execute([$session_id]);
    $answers = $stmt->fetchAll();

    // Per-topic breakdown
    $topics = [];
    foreach ($answers as $a) {
        $tn = $a['topic_name'];
        if (!isset($topics[$tn])) {
            $topics[$tn] = ['total' => 0, 'correct' => 0];
        }
        $topics[$tn]['total']++;
        if ($a['is_correct']) $topics[$tn]['correct']++;
    }

    $total   = count($answers);
    $correct = count(array_filter($answers, fn($a) => $a['is_correct']));
    $pct     = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    echo json_encode([
        'success' => true,
        'session' => $session,
        'score'   => [
            'total'   => $total,
            'correct' => $correct,
            'wrong'   => $total - $correct,
            'pct'     => $pct,
            'passed'  => $pct >= 75,
        ],
        'topics'  => $topics,
        'answers' => $answers,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}