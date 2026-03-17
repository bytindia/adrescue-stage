<?php
include 'db.php';
$id = $_GET['id'];

$conn->query("DELETE FROM cards_data WHERE id = $id");



header("Location: cards.php");
