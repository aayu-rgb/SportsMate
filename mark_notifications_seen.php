<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];

$conn->query("
    UPDATE notifications 
    SET seen=1 
    WHERE user_id=$user_id
");
