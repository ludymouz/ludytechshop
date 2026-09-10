<?php
require_once 'lang.php';
require_once 'db.php';
$msg = $err = '';
if (isset($_POST['send'])) {
    $tel = trim($_POST['tel'] ?? '');
    $st  = $conn->prepare("SELECT id FROM utilizadores WHERE telefone=? LIMIT 1");
    $st->bind_param("s", $tel); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    if ($row) {
        $code = rand(100000,999999);
        $_SESSION['rc'] = $code; $_SESSION['ri'] = $row['id'];
        $msg = "Código enviado para $tel. (Demo: $code)";
    } else $err = $lang==='pt'?'Número não encontrado.':'Number not found.';
}
if (isset($_POST['change'])) {
    if (!isset($_SESSION['rc']) || (int)$_POST['code'] !== (int)$_SESSION['rc'])
        $err = $lang==='pt'?'Código inválido.':'Invalid code.';
    elseif ($_POST['np'] !== $_POST['nc'])
        $err = $lang==='pt'?'Senhas não coincidem.':'Passwords do not match.';
    elseif (strlen($_POST['np']) < 6)
        $err = $lang==='pt'?'Mínimo 6 caracteres.':'Min 6 chars.';
    else {
        $h = password_hash($_POST['np'], PASSWORD_DEFAULT);
        $st = $conn->prepare("UPDATE utilizadores SET senha=? WHERE id=?");
        $st->bind_param("si", $h, $_SESSION['ri']); $st->execute(); $st->close();
        unset($_SESSION['rc'], $_SESSION['ri']);
        header('Location: login.php'); exit;
    }
}
?><!DOCTYPE html>
<html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $lang==='pt'?'Recuperar senha':'Recover password' ?></title>
<link rel="stylesheet" href="style.css">
<style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}.c{width:100%;max-width:370px}.f{margin-bottom:13px}</style>
</head><body>
<div class="fcard c">
    <h2 style="font-size:18px;font-weight:900;margin-bottom:5px"><?= $lang==='pt'?'Recuperar senha':'Recover password' ?></h2>
    <p style="color:var(--muted);font-size:12px;margin-bottom:18px"><?= $lang==='pt'?'Receberás um código por SMS.':'You will receive a code by SMS.' ?></p>
    <?php if ($err): ?><div class="ale ale-e"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="ale ale-o"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if (!isset($_SESSION['rc'])): ?>
    <form method="POST">
        <div class="fld f"><label><?= $lang==='pt'?'Telemóvel':'Phone' ?></label><input type="tel" name="tel" required></div>
        <button class="btn-p" style="width:100%" name="send" type="submit"><?= $lang==='pt'?'Enviar código':'Send code' ?></button>
    </form>
    <?php else: ?>
    <form method="POST">
        <div class="fld f"><label><?= $lang==='pt'?'Código':'Code' ?></label><input type="text" name="code" required></div>
        <div class="fld f"><label><?= $lang==='pt'?'Nova senha':'New password' ?></label><input type="password" name="np" required minlength="6"></div>
        <div class="fld f"><label><?= $lang==='pt'?'Confirmar':'Confirm' ?></label><input type="password" name="nc" required></div>
        <button class="btn-p" style="width:100%" name="change" type="submit"><?= $lang==='pt'?'Alterar':'Change' ?></button>
    </form>
    <?php endif; ?>
    <a href="login.php" style="display:block;text-align:center;margin-top:14px;color:var(--or);font-size:13px"><?= $lang==='pt'?'Voltar ao login':'Back to login' ?></a>
</div>
</body></html>
