<?php
require "config.php";

$sport = $_GET['sport'] ?? '';

$q = $conn->prepare("SELECT * FROM sports_videos WHERE sport=?");
$q->bind_param("s",$sport);
$q->execute();
$res = $q->get_result();

while($v = $res->fetch_assoc()){
    echo "
    <iframe width='100%' height='250'
    src='https://www.youtube.com/embed/{$v['youtube_id']}'
    frameborder='0' allowfullscreen></iframe>
    <p>{$v['title']}</p><hr>";
}
