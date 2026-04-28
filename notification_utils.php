<?php
require_once "config.php";

function push_notification(mysqli $conn, int $userId, string $message): bool
{
    if ($userId <= 0 || trim($message) === '') {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, seen, created_at) VALUES (?, ?, 0, NOW())");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("is", $userId, $message);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function push_notification_to_all_except(mysqli $conn, int $excludeUserId, string $message): int
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE id <> ?");
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("i", $excludeUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    $userIds = [];

    while ($res && $row = $res->fetch_assoc()) {
        $userIds[] = (int)$row['id'];
    }
    $stmt->close();

    $count = 0;
    foreach ($userIds as $uid) {
        if (push_notification($conn, $uid, $message)) {
            $count++;
        }
    }
    return $count;
}
