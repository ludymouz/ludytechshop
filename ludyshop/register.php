<?php
require_once 'lang.php';
require_once 'db.php';
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome   = trim($_POST['nome']   ?? '');
    $user   = trim($_POST['user']   ?? '');
    $email  = trim($_POST['email']  ?? '');
    $tel    = trim($_POST['tel']    ?? '');
    $morada = trim($_POST['morada'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $idade  = (int)($_POST['idade'] ?? 0);
    $pass   = $_POST['pass']  ?? '';
    $conf   = $_POST['conf']  ?? '';

    if (!$nome || !$user || !$tel || !$morada || !$genero || !$pass)
        $err = $lang==='pt' ? 'Preenche todos os campos obrigatórios.' : 'Fill all required fields.';
    elseif ($idade < 18)
        $err = $lang==='pt' ? 'Tens de ter pelo menos 18 anos.' : 'You must be at least 18.';
    elseif (strlen($pass) < 6)
        $err = $lang==='pt' ? 'Senha mínima 6 caracteres.' : 'Password min 6 chars.';
    elseif ($pass !== $conf)
        $err = $lang==='pt' ? 'Senhas não coincidem.' : 'Passwords do not match.';
    else {
        $st = $conn->prepare("SELECT id FROM utilizadores WHERE username=? OR telefone=?");
        $st->bind_param("ss", $user, $tel);
        $st->execute();
        $dup = $st->get_result()->num_rows > 0;
        $st->close();
        if ($dup) {
            $err = $lang==='pt' ? 'Utilizador ou telemóvel já registado.' : 'Username or phone already registered.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            list($fb, $ft) = readImg('foto');
            if ($fb) {
                $st  = $conn->prepare("INSERT INTO utilizadores (nome_completo,username,email,telefone,morada,genero,idade,foto,foto_tipo,senha) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $nul = null;
                $st->bind_param("ssssssibss", $nome,$user,$email,$tel,$morada,$genero,$idade,$nul,$ft,$hash);
                $st->send_long_data(7, $fb);
            } else {
                $st = $conn->prepare("INSERT INTO utilizadores (nome_completo,username,email,telefone,morada,genero,idade,senha) VALUES (?,?,?,?,?,?,?,?)");
                $st->bind_param("ssssssis", $nome,$user,$email,$tel,$morada,$genero,$idade,$hash);
            }
            if ($st->execute()) {
                $_SESSION['user_id']  = $st->insert_id;
                $_SESSION['username'] = $nome;
                $_SESSION['is_admin'] = false;
                $st->close();
                header('Location: index.php'); exit;
            }
            $err = 'Erro: ' . $conn->error;
            $st->close();
        }
    }
}
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= t('register') ?> — <?= t('shop') ?></title>
<link rel="stylesheet" href="style.css">
<style>
body{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:22px}
.rw{width:100%;max-width:540px}
.rt{display:flex;align-items:center;gap:12px;margin-bottom:22px}
.rl{width:42px;height:42px;background:var(--or);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:12px;flex-shrink:0}
.lb{display:flex;gap:5px;margin-left:auto}
.lb a{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:800;border:1.5px solid var(--bdr);color:var(--text);background:var(--card)}
.lb a.on{background:var(--or);color:#fff;border-color:var(--or)}
.av{display:flex;align-items:center;gap:12px}
.avp{width:50px;height:50px;border-radius:50%;object-fit:cover;border:2px solid var(--bdr)}
.opt{font-size:11px;color:var(--muted)}
.fa{display:flex;flex-direction:column;gap:9px;margin-top:13px}
.fa a{text-align:center;color:var(--or);font-size:13px}
.fa a:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="rw">
    <div class="rt">
        <a href="index.php" class="rl">LTS</a>
        <div>
            <h2 style="font-size:18px;font-weight:900"><?= t('register') ?></h2>
            <p style="color:var(--muted);font-size:12px"><?= $lang==='pt'?'Cria a tua conta':'Create your account' ?></p>
        </div>
        <div class="lb">
            <a href="?lang=pt" class="<?= $lang==='pt'?'on':'' ?>">PT</a>
            <a href="?lang=en" class="<?= $lang==='en'?'on':'' ?>">EN</a>
        </div>
    </div>
    <div class="fcard">
        <?php if ($err): ?><div class="ale ale-e"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="fg2" style="gap:12px">
                <div class="fld full">
                    <label><?= $lang==='pt'?'Foto de perfil':'Profile photo' ?> <span class="opt">(<?= $lang==='pt'?'opcional':'optional' ?>)</span></label>
                    <div class="av">
                        <img id="avp" class="avp" src="https://ui-avatars.com/api/?name=LTS&background=FF5A1F&color=fff" alt="">
                        <input type="file" name="foto" accept="image/*" onchange="pv(this)">
                    </div>
                </div>
                <div class="fld">
                    <label><?= $lang==='pt'?'Nome completo *':'Full name *' ?></label>
                    <input type="text" name="nome" required value="<?= htmlspecialchars($_POST['nome']??'') ?>">
                </div>
                <div class="fld">
                    <label><?= $lang==='pt'?'Nome de utilizador *':'Username *' ?></label>
                    <input type="text" name="user" required value="<?= htmlspecialchars($_POST['user']??'') ?>">
                </div>
                <div class="fld">
                    <label><?= $lang==='pt'?'Telemóvel *':'Phone *' ?></label>
                    <input type="tel" name="tel" required placeholder="9XXXXXXXX" value="<?= htmlspecialchars($_POST['tel']??'') ?>">
                </div>
                <div class="fld">
                    <label>Email <span class="opt">(<?= $lang==='pt'?'opcional':'optional' ?>)</span></label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>">
                </div>
                <div class="fld">
                    <label><?= $lang==='pt'?'Género *':'Gender *' ?></label>
                    <select name="genero" required>
                        <option value=""><?= $lang==='pt'?'Selecciona':'Select' ?></option>
                        <option value="Masculino" <?= ($_POST['genero']??'')==='Masculino'?'selected':'' ?>><?= $lang==='pt'?'Masculino':'Male' ?></option>
                        <option value="Feminino"  <?= ($_POST['genero']??'')==='Feminino'?'selected':'' ?>><?= $lang==='pt'?'Feminino':'Female' ?></option>
                        <option value="Outro"     <?= ($_POST['genero']??'')==='Outro'?'selected':'' ?>><?= $lang==='pt'?'Outro':'Other' ?></option>
                    </select>
                </div>
                <div class="fld">
                    <label><?= $lang==='pt'?'Idade * (mín. 18)':'Age * (min. 18)' ?></label>
                    <input type="number" name="idade" min="18" max="120" required value="<?= htmlspecialchars($_POST['idade']??'') ?>">
                </div>
                <div class="fld full">
                    <label><?= $lang==='pt'?'Morada completa *':'Full address *' ?></label>
                    <textarea name="morada" required><?= htmlspecialchars($_POST['morada']??'') ?></textarea>
                </div>
                <div class="fld">
                    <label><?= $lang==='pt'?'Palavra-passe *':'Password *' ?></label>
                    <input type="password" name="pass" required minlength="6">
                </div>
                <div class="fld">
                    <label><?= $lang==='pt'?'Confirmar *':'Confirm *' ?></label>
                    <input type="password" name="conf" required>
                </div>
            </div>
            <div class="fa">
                <button class="btn-p" type="submit"><?= t('register') ?></button>
                <a href="login.php"><?= $lang==='pt'?'Já tens conta? Entra aqui':'Already have an account? Login' ?></a>
            </div>
        </form>
    </div>
</div>
<script>
function pv(i){
    if(i.files&&i.files[0]){
        var r=new FileReader();
        r.onload=function(e){document.getElementById('avp').src=e.target.result;};
        r.readAsDataURL(i.files[0]);
    }
}
</script>
</body></html>
