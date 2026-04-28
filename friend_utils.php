<?php
function are_friends(mysqli $conn, int $userA, int $userB): bool
{
    if ($userA <= 0 || $userB <= 0 || $userA === $userB) {
        return false;
    }

    $stmt = $conn->prepare("
        SELECT id
        FROM friends
        WHERE status = 'accepted'
          AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        LIMIT 1
    ");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("iiii", $userA, $userB, $userB, $userA);
    $stmt->execute();
    $stmt->store_result();
    $ok = $stmt->num_rows > 0;
    $stmt->close();

    return $ok;
}
