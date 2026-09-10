<?php
require_once 'db.php';
$id = (int)($_GET['id'] ?? 0);
$t  = $_GET['t'] ?? 'prod';
if (!$id) { header('Location: https://placehold.co/400x300/111/FF5A1F?text=LTS'); exit; }
header('Cache-Control: public, max-age=86400');
if ($t === 'user') {
    $st = $conn->prepare("SELECT foto, foto_tipo FROM utilizadores WHERE id=? LIMIT 1");
} else {
    $st = $conn->prepare("SELECT imagem AS foto, imagem_tipo AS foto_tipo FROM produtos WHERE id=? LIMIT 1");
}
$st->bind_param("i", $id);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();
if (!$row || !$row['foto']) { header('Location: https://placehold.co/400x300/111/FF5A1F?text=LTS'); exit; }
header('Content-Type: ' . ($row['foto_tipo'] ?? 'image/jpeg'));
header('Content-Length: ' . strlen($row['foto']));
echo $row['foto'];
