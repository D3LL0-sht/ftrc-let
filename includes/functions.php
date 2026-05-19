<?php
// ============================================================
// FTRC LET — Helper Functions
// ============================================================

function calculate_score(array $answers): array {
    $total   = count($answers);
    $correct = count(array_filter($answers, fn($a) => $a['is_correct']));
    $pct     = $total > 0 ? round(($correct / $total) * 100, 2) : 0;

    return [
        'total'   => $total,
        'correct' => $correct,
        'wrong'   => $total - $correct,
        'pct'     => $pct,
        'passed'  => $pct >= 75,
    ];
}

function update_topic_analytics(PDO $pdo, int $user_id, int $topic_id, bool $is_correct): void {
    $stmt = $pdo->prepare("
        INSERT INTO topic_analytics (user_id, topic_id, total_attempts, correct_count, last_updated)
        VALUES (?, ?, 1, ?, NOW())
        ON DUPLICATE KEY UPDATE
            total_attempts = total_attempts + 1,
            correct_count  = correct_count + ?,
            last_updated   = NOW()
    ");
    $correct_int = $is_correct ? 1 : 0;
    $stmt->execute([$user_id, $topic_id, $correct_int, $correct_int]);
}

function pass_prediction(PDO $pdo, int $user_id): array {
    $stmt = $pdo->prepare("
        SELECT t.name, ta.accuracy_pct, ta.total_attempts
        FROM topic_analytics ta
        JOIN topics t ON t.id = ta.topic_id
        WHERE ta.user_id = ?
        ORDER BY ta.accuracy_pct ASC
    ");
    $stmt->execute([$user_id]);
    $topics = $stmt->fetchAll();

    $avg = count($topics) > 0
        ? round(array_sum(array_column($topics, 'accuracy_pct')) / count($topics), 2)
        : 0;

    return [
        'topics'     => $topics,
        'average'    => $avg,
        'will_pass'  => $avg >= 75,
        'weak_areas' => array_filter($topics, fn($t) => $t['accuracy_pct'] < 75),
    ];
}