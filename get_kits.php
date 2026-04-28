<?php
require "config.php";

$sport = $_GET['sport'] ?? '';

$q = $conn->prepare("SELECT * FROM sports_kits WHERE sport=?");
$q->bind_param("s",$sport);
$q->execute();
$res = $q->get_result();

while($k = $res->fetch_assoc()){
    echo "
    <div>
      <img src='{$k['image_url']}' width='150'><br>
      {$k['kit_name']}<br>
      <a href='{$k['buy_link']}' target='_blank'>Buy</a>
    </div><hr>";
}
