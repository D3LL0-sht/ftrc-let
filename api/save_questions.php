<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_role('admin');

header('Content-Type: application/json');

$data      = json_decode(file_get_contents('php://input'), true);
$questions = $data['questions'] ?? [];

if (empty($questions)) {
    echo json_encode(['success' => false, 'error' => 'No questions provided']);
    exit;
}

$saved = 0;

try {
    $stmt = $pdo->prepare("
        INSERT INTO questions
        (topic_id, question_text, choice_a, choice_b, choice_c, choice_d,
         correct_answer, difficulty, explanation)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($questions as $q) {
        $stmt->execute([
            (int)$q['topic_id'],
            $q['question'],
            $q['choice_a'],
            $q['choice_b'],
            $q['choice_c'],
            $q['choice_d'],
            strtoupper($q['correct_answer']),
            $q['difficulty'] ?? 'medium',
            $q['explanation'] ?? '',
        ]);
        $saved++;
    }

    echo json_encode(['success' => true, 'saved' => $saved]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}