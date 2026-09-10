<?php
require_once 'lang.php';
require_once 'db.php';
requireLogin();
$uid = (int)$_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'perfil';
$err = $ok = '';

// Cancelar pedido
if (isset($_POST['cancelar'])) {
    $pid = (int)$_POST['cancelar'];
    $st  = $conn->prepare("UPDATE pedidos SET estado='Cancelado' WHERE id=? AND user_id=? AND estado='Pendente'");
    $st->bind_param("ii",$pid,$uid); $st->execute(); $st->close();
    notif($conn,$uid,"O teu pedido #$pid foi cancelado.");
    header("Location: perfil.php?tab=pedidos"); exit;
}

// Ler notificações
if (isset($_GET['rnot'])) {
    $conn->query("UPDATE notificacoes SET lida=1 WHERE user_id=$uid");
    header("Location: perfil.php?tab=notifs"); exit;
}

// Editar perfil
if (isset($_POST['edit'])) {
    $nome   = trim($_POST['nome']   ?? '');
    $tel    = trim($_POST['tel']    ?? '');
    $morada = trim($_POST['morada'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    list($fb,$ft) = readImg('foto');
    if ($fb) {
        $st  = $conn->prepare("UPDATE utilizadores SET nome_completo=?,telefone=?,morada=?,email=?,foto=?,foto_tipo=? WHERE id=?");
        $nul = null;
        $st->bind_param("ssssbsi", $nome,$tel,$morada,$email,$nul,$ft,$uid);
        $st->send_long_data(4, $fb);
    } else {
        $st = $conn->prepare("UPDATE utilizadores SET nome_completo=?,telefone=?,morada=?,email=? WHERE id=?");
        $st->bind_param("ssssi", $nome,$tel,$morada,$email,$uid);
    }
    if ($st->execute()) { $_SESSION['username']=$nome; $ok=$lang==='pt'?'Perfil actualizado!':'Profile updated!'; }
    else $err = $conn->error;
    $st->close();
}

// Alterar senha
if (isset($_POST['chpass'])) {
    $at = $_POST['atual'] ?? ''; $nv = $_POST['nova'] ?? ''; $cf = $_POST['conf'] ?? '';
    $st = $conn->prepare("SELECT senha FROM utilizadores WHERE id=? LIMIT 1");
    $st->bind_param("i",$uid); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    if (!password_verify($at,$row['senha']))          $err = $lang==='pt'?'Senha actual incorrecta.':'Wrong current password.';
    elseif ($nv !== $cf)                              $err = $lang==='pt'?'Senhas não coincidem.':'Passwords do not match.';
    elseif (strlen($nv) < 6)                          $err = $lang==='pt'?'Mínimo 6 caracteres.':'Min 6 chars.';
    else {
        $h=$password=password_hash($nv,PASSWORD_DEFAULT);
        $st=$conn->prepare("UPDATE utilizadores SET senha=? WHERE id=?");
        $st->bind_param("si",$h,$uid); $st->execute(); $st->close();
        $ok=$lang==='pt'?'Senha alterada!':'Password changed!';
    }
}

$user    = $conn->query("SELECT * FROM utilizadores WHERE id=$uid LIMIT 1")->fetch_assoc();
$pedidos = $conn->query("SELECT * FROM pedidos WHERE user_id=$uid ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$favs    = $conn->query("SELECT p.id,p.nome,p.preco,p.preco_promo,p.em_promocao FROM favoritos f JOIN produtos p ON p.id=f.produto_id WHERE f.user_id=$uid")->fetch_all(MYSQLI_ASSOC);
$notifs  = $conn->query("SELECT * FROM notificacoes WHERE user_id=$uid ORDER BY id DESC LIMIT 30")->fetch_all(MYSQLI_ASSOC);
$unread  = count(array_filter($notifs, function($n){ return !$n['lida']; }));
$tgasto  = array_sum(array_column($pedidos,'total'));
$ecores  = ['Pendente'=>'#e67e22','Confirmado'=>'#2980b9','Em entrega'=>'#8e44ad','Entregue'=>'#27ae60','Cancelado'=>'#e74c3c'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= t('profile') ?> — <?= t('shop') ?></title>
<link rel="stylesheet" href="style.css">
<style>
.pw{max-width:960px;margin:24px auto;padding:0 16px;display:grid;grid-template-columns:210px 1fr;gap:20px;align-items:start}
.psb{background:var(--card);border-radius:var(--rM);box-shadow:var(--sh);overflow:hidden}
.pav{background:linear-gradient(135deg,#1A1A1A,#3D1500);padding:24px;text-align:center}
.pavimg{width:74px;height:74px;border-radius:50%;object-fit:cover;border:3px solid var(--or);margin-bottom:9px}
.pavnm{color:#fff;font-weight:800;font-size:14px}
.pavsb{color:rgba(255,255,255,.6);font-size:12px;margin-top:2px}
.pnav a{display:flex;align-items:center;gap:8px;padding:11px 14px;font-size:13px;font-weight:600;color:var(--text);border-left:3px solid transparent;transition:background var(--tr),border-color var(--tr),color var(--tr)}
.pnav a:hover,.pnav a.on{background:var(--orf);border-left-color:var(--or);color:var(--or)}
.pnav .nb{margin-left:auto;background:var(--or);color:#fff;border-radius:20px;padding:1px 7px;font-size:10px;font-weight:900}
.pc{background:var(--card);border-radius:var(--rM);box-shadow:var(--sh);padding:24px}
.stts{display:grid;grid-template-columns:1fr 1fr;gap:11px;margin-bottom:20px}
.sb2{background:var(--bg2);border-radius:var(--r);padding:14px;text-align:center}
.sb2 .v{font-size:20px;font-weight:900;color:var(--or)}
.sb2 .l{font-size:11px;color:var(--muted);margin-top:3px}
.oc{border:1px solid var(--bdr);border-radius:var(--r);padding:12px;margin-bottom:9px}
.oh{display:flex;align-items:center;gap:8px;margin-bottom:7px;flex-wrap:wrap}
.obdg{padding:3px 9px;border-radius:20px;font-size:11px;font-weight:800;color:#fff;display:inline-block}
.otxt{font-size:13px;color:var(--text2);margin-bottom:7px}
.ofoot{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:7px}
.otot{font-weight:900;color:var(--or)}
.fgrd{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:11px}
.fcard2{background:var(--bg2);border-radius:var(--r);overflow:hidden;cursor:pointer;transition:transform var(--tr)}
.fcard2:hover{transform:translateY(-2px)}
.fcard2 img{width:100%;height:110px;object-fit:cover}
.fcard2 .fb{padding:9px}
.fcard2 .fb h4{font-size:12px;font-weight:700;margin-bottom:3px}
.ni{display:flex;gap:8px;padding:10px;border-bottom:1px solid var(--bdr);align-items:flex-start}
.nd{width:8px;height:8px;border-radius:50%;background:var(--or);margin-top:5px;flex-shrink:0}
.nd.r{background:var(--bdr)}
.tab h3{font-size:16px;font-weight:900;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--bdr)}
.av2{display:flex;align-items:center;gap:11px}
.avp2{width:50px;height:50px;border-radius:50%;object-fit:cover;border:2px solid var(--bdr)}
@media(max-width:660px){.pw{grid-template-columns:1fr}.fg2{grid-template-columns:1fr}}
</style>
</head>
<body>
<header class="hdr">
    <a href="index.php" class="logo">LUDY <span>TECH</span> SHOP</a>
    <div class="ha" style="margin-left:auto">
        <a href="?lang=<?= $lang==='pt'?'en':'pt' ?>&tab=<?= $tab ?>" class="lbtn"><?= $lang==='pt'?'EN':'PT' ?></a>
        <a href="logout.php" class="hb">⏻ <?= t('logout') ?></a>
        <a href="index.php" class="hb sm">← <?= $lang==='pt'?'Loja':'Shop' ?></a>
    </div>
</header>

<div class="pw">
    <div class="psb">
        <div class="pav">
            <img class="pavimg"
                 src="img.php?id=<?= $uid ?>&t=user"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['nome_completo']) ?>&background=FF5A1F&color=fff'"
                 alt="">
            <div class="pavnm"><?= htmlspecialchars($user['nome_completo']) ?></div>
            <div class="pavsb">@<?= htmlspecialchars($user['username']) ?></div>
        </div>
        <nav class="pnav">
            <a href="?tab=perfil"  class="<?= $tab==='perfil'?'on':'' ?>">👤 <?= t('profile') ?></a>
            <a href="?tab=pedidos" class="<?= $tab==='pedidos'?'on':'' ?>">📦 <?= t('orders') ?></a>
            <a href="?tab=favs"    class="<?= $tab==='favs'?'on':'' ?>">♥ <?= t('favorites') ?></a>
            <a href="?tab=notifs"  class="<?= $tab==='notifs'?'on':'' ?>">🔔 <?= t('notifs') ?><?php if($unread):?><span class="nb"><?=$unread?></span><?php endif;?></a>
            <a href="?tab=senha"   class="<?= $tab==='senha'?'on':'' ?>">🔐 <?= t('change_pass') ?></a>
        </nav>
    </div>

    <div class="pc">
        <?php if ($err): ?><div class="ale ale-e"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <?php if ($ok):  ?><div class="ale ale-o"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

        <?php if ($tab === 'perfil'): ?>
        <div class="tab">
            <h3><?= t('profile') ?></h3>
            <div class="stts">
                <div class="sb2"><div class="v"><?= count($pedidos) ?></div><div class="l"><?= t('num_orders') ?></div></div>
                <div class="sb2"><div class="v"><?= number_format($tgasto,0,'.',',') ?> Kz</div><div class="l"><?= t('total_spent') ?></div></div>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="fg2" style="gap:12px">
                    <div class="fld full">
                        <label><?= $lang==='pt'?'Foto de perfil':'Profile photo' ?></label>
                        <div class="av2">
                            <img id="avp" class="avp2"
                                 src="img.php?id=<?= $uid ?>&t=user"
                                 onerror="this.src='https://ui-avatars.com/api/?name=U&background=FF5A1F&color=fff'" alt="">
                            <input type="file" name="foto" accept="image/*" onchange="pv(this)">
                        </div>
                    </div>
                    <div class="fld"><label><?= $lang==='pt'?'Nome completo':'Full name' ?></label><input type="text" name="nome" required value="<?= htmlspecialchars($user['nome_completo']) ?>"></div>
                    <div class="fld"><label><?= $lang==='pt'?'Telemóvel':'Phone' ?></label><input type="tel" name="tel" value="<?= htmlspecialchars($user['telefone']) ?>"></div>
                    <div class="fld full"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']??'') ?>"></div>
                    <div class="fld full"><label><?= $lang==='pt'?'Morada':'Address' ?></label><textarea name="morada" rows="3"><?= htmlspecialchars($user['morada']) ?></textarea></div>
                </div>
                <button class="btn-p" name="edit" type="submit" style="margin-top:13px"><?= t('save') ?></button>
            </form>
        </div>

        <?php elseif ($tab === 'pedidos'): ?>
        <div class="tab">
            <h3><?= t('orders') ?></h3>
            <?php if (empty($pedidos)): ?>
            <p style="color:var(--muted);font-size:14px"><?= t('no_orders') ?></p>
            <?php else: foreach ($pedidos as $ped):
                $cor = $ecores[$ped['estado']] ?? '#888';
            ?>
            <div class="oc">
                <div class="oh">
                    <strong><?= t('orders') ?> #<?= $ped['id'] ?></strong>
                    <span class="obdg" style="background:<?= $cor ?>"><?= $ped['estado'] ?></span>
                    <span style="font-size:12px;color:var(--muted);margin-left:auto"><?= date('d/m/Y H:i',strtotime($ped['criado_em'])) ?></span>
                </div>
                <div class="otxt"><?= htmlspecialchars($ped['produtos_txt']) ?></div>
                <div class="ofoot">
                    <span style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($ped['metodo_pag']) ?> · <?= htmlspecialchars($ped['tipo_entrega']) ?></span>
                    <div style="display:flex;align-items:center;gap:9px">
                        <span class="otot"><?= number_format($ped['total'],0,'.',',') ?> Kz</span>
                        <?php if ($ped['estado']==='Pendente'): ?>
                        <form method="POST" onsubmit="return confirm('<?= $lang==='pt'?'Cancelar pedido?':'Cancel order?' ?>')">
                            <input type="hidden" name="cancelar" value="<?= $ped['id'] ?>">
                            <button class="btn-d" type="submit"><?= t('cancel') ?></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <?php elseif ($tab === 'favs'): ?>
        <div class="tab">
            <h3><?= t('favorites') ?></h3>
            <?php if (empty($favs)): ?>
            <p style="color:var(--muted);font-size:14px"><?= t('no_favs') ?></p>
            <?php else: ?>
            <div class="fgrd">
                <?php foreach ($favs as $fv):
                    $fp = ($fv['em_promocao']&&$fv['preco_promo']) ? (float)$fv['preco_promo'] : (float)$fv['preco'];
                ?>
                <div class="fcard2" onclick="location.href='produto.php?id=<?= $fv['id'] ?>'">
                    <img src="img.php?id=<?= $fv['id'] ?>" onerror="this.src='https://placehold.co/300x200/111/FF5A1F?text=LTS'" alt="<?= htmlspecialchars($fv['nome']) ?>">
                    <div class="fb">
                        <h4><?= htmlspecialchars($fv['nome']) ?></h4>
                        <span style="color:var(--or);font-weight:900;font-size:12px"><?= number_format($fp,0,'.',',') ?> Kz</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($tab === 'notifs'): ?>
        <div class="tab">
            <h3>🔔 <?= t('notifs') ?>
                <?php if ($unread): ?>
                <a href="?tab=notifs&rnot=1" style="font-size:12px;font-weight:400;color:var(--or);margin-left:8px"><?= $lang==='pt'?'Marcar todas lidas':'Mark all read' ?></a>
                <?php endif; ?>
            </h3>
            <?php if (empty($notifs)): ?>
            <p style="color:var(--muted);font-size:14px"><?= t('no_notifs') ?></p>
            <?php else: foreach ($notifs as $nf): ?>
            <div class="ni">
                <div class="nd <?= $nf['lida']?'r':'' ?>"></div>
                <div style="flex:1">
                    <div style="font-size:13px"><?= htmlspecialchars($nf['mensagem']) ?></div>
                    <div style="font-size:11px;color:var(--muted)"><?= date('d/m/Y H:i',strtotime($nf['criado_em'])) ?></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <?php elseif ($tab === 'senha'): ?>
        <div class="tab">
            <h3><?= t('change_pass') ?></h3>
            <form method="POST" style="max-width:340px">
                <div class="fld" style="margin-bottom:12px"><label><?= $lang==='pt'?'Senha actual':'Current password' ?></label><input type="password" name="atual" required></div>
                <div class="fld" style="margin-bottom:12px"><label><?= $lang==='pt'?'Nova senha':'New password' ?></label><input type="password" name="nova" required minlength="6"></div>
                <div class="fld" style="margin-bottom:16px"><label><?= $lang==='pt'?'Confirmar':'Confirm' ?></label><input type="password" name="conf" required></div>
                <button class="btn-p" name="chpass" type="submit"><?= t('save') ?></button>
            </form>
        </div>
        <?php endif; ?>
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
</body>
</html>
