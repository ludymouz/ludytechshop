<?php
require_once 'db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false]); exit; }
$uid = (int)$_SESSION['user_id'];
$pid = (int)($_POST['pid'] ?? 0);
if (!$pid) { echo json_encode(['ok'=>false]); exit; }
$st = $conn->prepare("SELECT id FROM favoritos WHERE user_id=? AND produto_id=?");
$st->bind_param("ii", $uid, $pid);
$st->execute();
$ex = $st->get_result()->num_rows > 0;
$st->close();
if ($ex) {
    $st = $conn->prepare("DELETE FROM favoritos WHERE user_id=? AND produto_id=?");
    $st->bind_param("ii", $uid, $pid);
    $st->execute(); $st->close();
    echo json_encode(['ok'=>true,'fav'=>false]);
} else {
    $st = $conn->prepare("INSERT INTO favoritos (user_id,produto_id) VALUES (?,?)");
    $st->bind_param("ii", $uid, $pid);
    $st->execute(); $st->close();
    echo json_encode(['ok'=>true,'fav'=>true]);
}
