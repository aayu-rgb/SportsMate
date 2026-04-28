<?php
require "config.php";

$result = $conn->query("SELECT * FROM events ORDER BY event_date ASC");

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

echo json_encode($events);
