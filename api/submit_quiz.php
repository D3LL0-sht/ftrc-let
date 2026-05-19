<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/config.php';
require_login();

header('Content-Type: application/json');

$data         = json_decode(file_get_contents('php://input'), true);
$session_id   = (int)($data['session_id']   ?? 0);
$answers      = $data['answers']             ?? [];
$time_used    = (int)($data['time_used']     ?? 0);
$user_id      = $_SESSION['user_id'];

if (!$session_id || empty($answers)) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

try {
    // Verify session belongs to this user
    $stmt = $pdo->prepare("SELECT * FROM exam_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'error' => 'Invalid session']);
        exit;
    }

    // Get correct answers for submitted questions
    $question_ids = array_column($answers, 'question_id');
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT id, correct_answer, topic_id
        FROM questions
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($question_ids);
    $correct_map = [];
    foreach ($stmt->fetchAll() as $q) {
        $correct_map[$q['id']] = [
            'answer'   => $q['correct_answer'],
            'topic_id' => $q['topic_id'],
        ];
    }

    // Save each answer
    $results = [];
    foreach ($answers as $ans) {
        $q_id        = (int)$ans['question_id'];
        $user_answer = strtoupper($ans['answer'] ?? '');
        $is_correct  = isset($correct_map[$q_id]) &&
                       $correct_map[$q_id]['answer'] === $user_answer;
        $time_spent  = (int)($ans['time_spent'] ?? 0);

        $stmt = $pdo->prepare("
            INSERT INTO session_questions
            (session_id, question_id, user_answer, is_correct, time_spent_sec)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$session_id, $q_id, $user_answer, $is_correct ? 1 : 0, $time_spent]);

        // Update topic analytics
        if (isset($correct_map[$q_id])) {
            update_topic_analytics($pdo, $user_id, $correct_map[$q_id]['topic_id'], $is_correct);
        }

        $results[] = [
            'question_id' => $q_id,
            'is_correct'  => $is_correct,
            'correct'     => $correct_map[$q_id]['answer'] ?? '',
            'yours'       => $user_answer,
        ];
    }

    // Mark session as submitted
    $pdo->prepare("
        UPDATE exam_sessions
        SET submitted_at = NOW(), time_used_sec = ?
        WHERE id = ?
    ")->execute([$time_used, $session_id]);

    // Calculate score
    $score = calculate_score($results);

    echo json_encode([
        'success'    => true,
        'session_id' => $session_id,
        'score'      => $score,
        'results'    => $results,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}