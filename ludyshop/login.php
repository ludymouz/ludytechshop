<?php
require_once 'lang.php';
require_once 'db.php';
if (isset($_SESSION['user_id']) || isAdmin()) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['u'] ?? '');
    $p = trim($_POST['p'] ?? '');
    if ($u === ADMIN_EMAIL && $p === ADMIN_PASS) {
        $_SESSION['is_admin'] = true;
        $_SESSION['username'] = 'Admin';
        header('Location: admin.php'); exit;
    }
    $st = $conn->prepare("SELECT id,nome_completo,senha FROM utilizadores WHERE telefone=? LIMIT 1");
    $st->bind_param("s", $u);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if ($row && password_verify($p, $row['senha'])) {
        $_SESSION['user_id']  = $row['id'];
        $_SESSION['username'] = $row['nome_completo'];
        $_SESSION['is_admin'] = false;
        header('Location: index.php'); exit;
    }
    $err = $lang === 'pt' ? 'Dados incorrectos.' : 'Wrong credentials.';
}
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= t('login') ?> — <?= t('shop') ?></title>
<link rel="stylesheet" href="style.css">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.w{display:flex;width:100%;max-width:820px;border-radius:var(--rM);overflow:hidden;box-shadow:var(--shL)}
.br{flex:1;background:linear-gradient(145deg,#1A1A1A,#3D1500 60%,var(--or));display:flex;flex-direction:column;align-items:center;justify-content:center;padding:44px 30px;color:#fff}
.brl{width:76px;height:76px;background:rgba(255,255,255,.12);border:3px solid var(--or);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:900;margin-bottom:18px}
.br h2{font-size:18px;font-weight:900;margin-bottom:5px}
.br p{font-size:12px;opacity:.6;text-align:center}
.fs{flex:1;background:var(--card);padding:44px 34px;display:flex;flex-direction:column;justify-content:center}
.fs h3{font-size:20px;font-weight:900;margin-bottom:4px}
.fs .sub{color:var(--muted);font-size:12px;margin-bottom:24px}
.lnk{margin-top:16px;display:flex;flex-direction:column;gap:8px}
.lnk a{color:var(--or);font-size:13px}
.lnk a:hover{text-decoration:underline}
.lbar{position:fixed;top:14px;right:16px;display:flex;gap:5px}
.lbar a{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:800;border:1.5px solid var(--bdr);color:var(--text);background:var(--card)}
.lbar a.on{background:var(--or);color:#fff;border-color:var(--or)}
@media(max-width:560px){.br{display:none}.fs{padding:32px 18px}}
</style>
</head>
<body>
<div class="lbar">
    <a href="?lang=pt" class="<?= $lang==='pt'?'on':'' ?>">PT</a>
    <a href="?lang=en" class="<?= $lang==='en'?'on':'' ?>">EN</a>
</div>
<div class="w">
    <div class="br">
        <div class="brl">LTS</div>
        <h2><?= t('shop') ?></h2>
        <p><?= $lang==='pt'?'A tua loja de confiança em Uíge, Angola.':'Your trusted shop in Uíge, Angola.' ?></p>
    </div>
    <div class="fs">
        <h3><?= t('login') ?></h3>
        <p class="sub"><?= $lang==='pt'?'Clientes: telemóvel | Admin: email':'Customers: phone | Admin: email' ?></p>
        <?php if ($err): ?><div class="ale ale-e"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <form method="POST">
            <div class="fld" style="margin-bottom:13px">
                <label><?= $lang==='pt'?'Telemóvel (ou email se admin)':'Phone (or email if admin)' ?></label>
                <input type="text" name="u" required autofocus placeholder="<?= $lang==='pt'?'Ex: 949148512':'e.g. 949148512' ?>">
            </div>
            <div class="fld" style="margin-bottom:18px">
                <label><?= $lang==='pt'?'Palavra-passe':'Password' ?></label>
                <input type="password" name="p" required>
            </div>
            <button class="btn-p" style="width:100%" type="submit"><?= t('login') ?></button>
        </form>
        <div class="lnk">
            <a href="register.php"><?= $lang==='pt'?'Não tens conta? Regista-te':"Don't have an account? Register" ?></a>
            <a href="recuperar.php"><?= $lang==='pt'?'Esqueceste a palavra-passe?':'Forgot your password?' ?></a>
        </div>
    </div>
</div>
</body></html>
