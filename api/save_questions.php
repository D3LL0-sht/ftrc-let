<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload: ' . json_last_error_msg()]);
    exit;
}

$questions = $data['questions'] ?? [];

if (empty($questions) || !is_array($questions)) {
    echo json_encode(['success' => false, 'error' => 'No questions provided']);
    exit;
}

// Validate required fields
$required = ['topic_id', 'question', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct_answer'];
foreach ($questions as $i => $q) {
    foreach ($required as $field) {
        if (empty($q[$field])) {
            echo json_encode([
                'success' => false,
                'error'   => "Question #" . ($i + 1) . " is missing field: $field"
            ]);
            exit;
        }
    }
    // Validate correct_answer is A/B/C/D
    $ans = strtoupper(trim($q['correct_answer']));
    if (!in_array($ans, ['A', 'B', 'C', 'D'])) {
        echo json_encode([
            'success' => false,
            'error'   => "Question #" . ($i + 1) . " has invalid correct_answer: '{$q['correct_answer']}'. Must be A, B, C, or D."
        ]);
        exit;
    }
}

$saved = 0;
$errors = [];

try {
    $stmt = $pdo->prepare("
        INSERT INTO questions
            (topic_id, question_text, choice_a, choice_b, choice_c, choice_d,
             correct_answer, difficulty, explanation)
        VALUES
            (:topic_id, :question_text, :choice_a, :choice_b, :choice_c, :choice_d,
             :correct_answer, :difficulty, :explanation)
    ");

    $pdo->beginTransaction();

    foreach ($questions as $i => $q) {
        try {
            $stmt->execute([
                ':topic_id'      => (int) $q['topic_id'],
                ':question_text' => trim($q['question']),
                ':choice_a'      => trim($q['choice_a']),
                ':choice_b'      => trim($q['choice_b']),
                ':choice_c'      => trim($q['choice_c']),
                ':choice_d'      => trim($q['choice_d']),
                ':correct_answer'=> strtoupper(trim($q['correct_answer'])),
                ':difficulty'    => $q['difficulty'] ?? 'medium',
                ':explanation'   => trim($q['explanation'] ?? ''),
            ]);
            $saved++;
        } catch (PDOException $e) {
            $errors[] = "Question #" . ($i + 1) . ": " . $e->getMessage();
        }
    }

    $pdo->commit();

    if ($saved === 0) {
        echo json_encode([
            'success' => false,
            'error'   => 'No questions were saved. Errors: ' . implode('; ', $errors)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'saved'   => $saved,
            'errors'  => $errors, // partial save info
        ]);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'error'   => 'Database error: ' . $e->getMessage()
    ]);
}