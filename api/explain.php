<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_login();

header('Content-Type: application/json');

$data               = json_decode(file_get_contents('php://input'), true);
$session_question_id = (int)($data['session_question_id'] ?? 0);

if (!$session_question_id) {
    echo json_encode(['success' => false, 'error' => 'Missing session_question_id']);
    exit;
}

try {
    // Check if explanation already exists
    $stmt = $pdo->prepare("
        SELECT ai_response FROM ai_explanations
        WHERE session_question_id = ?
    ");
    $stmt->execute([$session_question_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        echo json_encode(['success' => true, 'explanation' => $existing['ai_response']]);
        exit;
    }

    // Get question details
    $stmt = $pdo->prepare("
        SELECT sq.user_answer, sq.is_correct,
               q.question_text, q.choice_a, q.choice_b,
               q.choice_c, q.choice_d, q.correct_answer,
               q.explanation, t.name as topic_name
        FROM session_questions sq
        JOIN questions q ON q.id = sq.question_id
        JOIN topics t ON t.id = q.topic_id
        WHERE sq.id = ?
    ");
    $stmt->execute([$session_question_id]);
    $sq = $stmt->fetch();

    if (!$sq) {
        echo json_encode(['success' => false, 'error' => 'Question not found']);
        exit;
    }

    // Use pre-written explanation as fallback
    $fallback = $sq['explanation'] ?? '';

    // Build Gemini prompt
    $prompt = "You are an expert LET reviewer for the Philippines Licensure Examination for Teachers.

Topic: {$sq['topic_name']}
Question: {$sq['question_text']}
A) {$sq['choice_a']}
B) {$sq['choice_b']}
C) {$sq['choice_c']}
D) {$sq['choice_d']}

Correct Answer: {$sq['correct_answer']}
Student's Answer: {$sq['user_answer']}

Please explain in 3-4 sentences:
1. Why the correct answer is right
2. Why the student's answer is wrong (if different)
3. The key concept to remember for the LET exam

Keep it clear and helpful for a Filipino teacher candidate.";

    // Call Gemini API
    $url     = AI_API_URL . AI_API_KEY;
    $payload = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    $explanation = $fallback; // default to fallback

    if (!$err && $response) {
        $json = json_decode($response, true);
        $ai_text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($ai_text) {
            $explanation = $ai_text;
        }
    }

    // Save explanation
    $pdo->prepare("
        INSERT INTO ai_explanations (session_question_id, ai_response)
        VALUES (?, ?)
    ")->execute([$session_question_id, $explanation]);

    echo json_encode(['success' => true, 'explanation' => $explanation]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}