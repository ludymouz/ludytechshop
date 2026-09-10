<?php
require_once 'db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'err'=>'login']); exit; }
$uid = (int)$_SESSION['user_id'];
$txt = trim($_POST['txt'] ?? '');
$tot = (float)($_POST['tot'] ?? 0);
$met = trim($_POST['met'] ?? '');
$ent = trim($_POST['ent'] ?? '');
if (!$txt || $tot <= 0 || !$met || !in_array($ent, ['entrega','levantamento'])) {
    echo json_encode(['ok'=>false,'err'=>'dados invalidos']); exit;
}
$st = $conn->prepare("INSERT INTO pedidos (user_id,produtos_txt,total,metodo_pag,tipo_entrega) VALUES (?,?,?,?,?)");
$st->bind_param("isdss", $uid, $txt, $tot, $met, $ent);
if ($st->execute()) {
    $pid = $st->insert_id;
    $st->close();
    notif($conn, $uid, "O teu pedido #$pid foi recebido! Estado: Pendente.");
    $u = $conn->query("SELECT nome_completo,telefone FROM utilizadores WHERE id=$uid LIMIT 1")->fetch_assoc();
    @mail(ADMIN_EMAIL, "Novo pedido #$pid — Ludy Tech Shop",
        "Cliente: {$u['nome_completo']}\nTelefone: {$u['telefone']}\nProdutos: $txt\nTotal: $tot Kz\nPagamento: $met\nEntrega: $ent",
        "From: noreply@ludytech.ao");
    echo json_encode(['ok'=>true,'id'=>$pid]);
} else {
    $st->close();
    echo json_encode(['ok'=>false,'err'=>$conn->error]);
}
