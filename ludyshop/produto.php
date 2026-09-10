<?php
require_once 'lang.php';
require_once 'db.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$st = $conn->prepare("SELECT p.*,c.nome AS cnom FROM produtos p LEFT JOIN categorias c ON c.id=p.categoria_id WHERE p.id=? LIMIT 1");
$st->bind_param("i",$id); $st->execute();
$p = $st->get_result()->fetch_assoc(); $st->close();
if (!$p) { header('Location: 404.php'); exit; }

$uid   = $_SESSION['user_id'] ?? null;
$preco = ($p['em_promocao'] && $p['preco_promo']) ? (float)$p['preco_promo'] : (float)$p['preco'];

// Avaliações
$rs = $conn->prepare("SELECT a.*,u.nome_completo,u.id AS uid FROM avaliacoes a JOIN utilizadores u ON u.id=a.user_id WHERE a.produto_id=? ORDER BY a.id DESC");
$rs->bind_param("i",$id); $rs->execute();
$revs = $rs->get_result()->fetch_all(MYSQLI_ASSOC); $rs->close();
$media = $revs ? array_sum(array_column($revs,'estrelas'))/count($revs) : 0;

$jaRev = false;
if ($uid) {
    $cr = $conn->prepare("SELECT id FROM avaliacoes WHERE user_id=? AND produto_id=?");
    $cr->bind_param("ii",$uid,$id); $cr->execute();
    $jaRev = $cr->get_result()->num_rows > 0; $cr->close();
}

$rErr = '';
if ($uid && !$jaRev && isset($_POST['avr'])) {
    $stars = (int)($_POST['stars'] ?? 0);
    $com   = trim($_POST['com'] ?? '');
    if ($stars >= 1 && $stars <= 5) {
        $ins = $conn->prepare("INSERT INTO avaliacoes (user_id,produto_id,estrelas,comentario) VALUES (?,?,?,?)");
        $ins->bind_param("iiis",$uid,$id,$stars,$com);
        $ins->execute(); $ins->close();
        header("Location: produto.php?id=$id"); exit;
    } else {
        $rErr = $lang==='pt'?'Selecciona de 1 a 5 estrelas.':'Select 1 to 5 stars.';
    }
}

$isFav = false;
if ($uid) {
    $fv = $conn->prepare("SELECT id FROM favoritos WHERE user_id=? AND produto_id=?");
    $fv->bind_param("ii",$uid,$id); $fv->execute();
    $isFav = $fv->get_result()->num_rows > 0; $fv->close();
}

// Relacionados
$rl = $conn->prepare("SELECT id,nome,preco,preco_promo,em_promocao FROM produtos WHERE categoria_id=? AND id!=? AND stock>0 LIMIT 4");
$rl->bind_param("ii",$p['categoria_id'],$id); $rl->execute();
$rel = $rl->get_result()->fetch_all(MYSQLI_ASSOC); $rl->close();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($p['nome']) ?> — <?= t('shop') ?></title>
<link rel="stylesheet" href="style.css">
<style>
.pd{max-width:960px;margin:24px auto;padding:0 16px}
.pdg{display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start}
.pdg img{width:100%;border-radius:var(--rM);box-shadow:var(--sh)}
.pdi h1{font-size:20px;font-weight:900;margin-bottom:8px}
.pdst{color:#FFB800;font-size:15px;margin-bottom:12px}
.pdpr{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.pdpr .now{font-size:24px;font-weight:900;color:var(--or)}
.pdpr .old{font-size:14px;color:var(--muted);text-decoration:line-through}
.pdds{color:var(--text2);font-size:14px;line-height:1.7;margin-bottom:14px}
.pdsk{font-size:13px;font-weight:700;margin-bottom:14px}
.pdqt{display:flex;align-items:center;gap:7px;margin-bottom:13px}
.pdqt button{width:32px;height:32px;border:1.5px solid var(--bdr);background:var(--bg);color:var(--text);border-radius:var(--r);font-size:16px;font-weight:700}
.pdqt button:hover{background:var(--or);color:#fff;border-color:var(--or)}
.pdqt input{width:50px;text-align:center;border:1.5px solid var(--bdr);border-radius:var(--r);padding:5px;background:var(--bg);color:var(--text);font-size:14px;font-weight:700}
.pdat{display:flex;gap:9px;flex-wrap:wrap}
.pdat .btn-p{flex:1}
.brd{font-size:12px;color:var(--muted);margin-bottom:18px}
.brd a{color:var(--or)}
.rvc{background:var(--card);border-radius:var(--r);box-shadow:var(--sh);padding:13px;margin-bottom:10px}
.rvh{display:flex;align-items:center;gap:8px;margin-bottom:5px}
.rvav{width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid var(--bdr)}
.rvnm{font-weight:700;font-size:13px}
.rvdt{font-size:11px;color:var(--muted);margin-left:auto}
.rvst{color:#FFB800;font-size:13px;margin-bottom:3px}
.rvtx{font-size:13px;color:var(--text2)}
.sst{display:flex;gap:4px;margin-bottom:10px}
.sst span{font-size:25px;cursor:pointer;color:var(--bdr);transition:color .15s}
.sst span.on{color:#FFB800}
.rlg{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:13px}
@media(max-width:660px){.pdg{grid-template-columns:1fr}.pdat{flex-direction:column}}
</style>
</head>
<body>
<header class="hdr">
    <a href="index.php" class="logo">LUDY <span>TECH</span> SHOP</a>
    <div class="ha" style="margin-left:auto">
        <a href="?lang=<?= $lang==='pt'?'en':'pt' ?>&id=<?= $id ?>" class="lbtn"><?= $lang==='pt'?'EN':'PT' ?></a>
        <?php if ($uid || isAdmin()): ?>
        <a href="perfil.php" class="hb sm">👤</a>
        <a href="logout.php" class="hb">⏻</a>
        <?php else: ?>
        <a href="login.php" class="hb sm"><?= t('login') ?></a>
        <?php endif; ?>
        <a href="index.php" class="hb">← <?= t('back') ?></a>
    </div>
</header>

<div class="pd">
    <div class="brd">
        <a href="index.php"><?= $lang==='pt'?'Loja':'Shop' ?></a>
        › <?= htmlspecialchars($p['cnom'] ?? '') ?>
        › <?= htmlspecialchars($p['nome']) ?>
    </div>

    <div class="pdg">
        <div>
            <img src="img.php?id=<?= $p['id'] ?>" alt="<?= htmlspecialchars($p['nome']) ?>"
                 onerror="this.src='https://placehold.co/600x500/111/FF5A1F?text=LTS'">
        </div>
        <div class="pdi">
            <?php if ($p['em_promocao']): ?>
            <span class="tpr" style="position:relative;top:0;left:0;display:inline-block;margin-bottom:9px"><?= t('promo') ?></span>
            <?php endif; ?>
            <h1><?= htmlspecialchars($p['nome']) ?></h1>
            <div class="pdst">
                <?php $ns=round($media); for($s=1;$s<=5;$s++) echo $s<=$ns?'★':'☆'; ?>
                <small style="color:var(--muted);font-size:13px">(<?= count($revs) ?> <?= t('reviews') ?>)</small>
            </div>
            <div class="pdpr">
                <span class="now"><?= number_format($preco,0,'.',',') ?> Kz</span>
                <?php if ($p['em_promocao'] && $p['preco_promo']): ?>
                <span class="old"><?= number_format((float)$p['preco'],0,'.',',') ?> Kz</span>
                <?php endif; ?>
            </div>
            <?php if ($p['descricao']): ?>
            <p class="pdds"><?= nl2br(htmlspecialchars($p['descricao'])) ?></p>
            <?php endif; ?>
            <p class="pdsk" style="color:<?= $p['stock']<=0?'#e74c3c':($p['stock']<5?'#e67e22':'#27ae60') ?>">
                <?php
                if ($p['stock'] <= 0)    echo '❌ '.t('esgotado');
                elseif ($p['stock'] < 5) echo '⚠ '.tr('stock_low',['n'=>$p['stock']]);
                else                     echo '✔ '.($lang==='pt'?'Em stock':'In stock');
                ?>
            </p>
            <?php if ($p['stock'] > 0): ?>
            <div class="pdqt">
                <button type="button" onclick="aq(-1)">−</button>
                <input type="number" id="pdq" value="1" min="1" max="<?= $p['stock'] ?>">
                <button type="button" onclick="aq(1)">+</button>
                <span style="font-size:11px;color:var(--muted)">máx <?= $p['stock'] ?></span>
            </div>
            <?php endif; ?>
            <div class="pdat">
                <?php if ($p['stock'] > 0): ?>
                <button class="btn-p" onclick="addAndGo(<?= $p['id'] ?>,<?= json_encode($p['nome']) ?>,<?= $preco ?>,<?= $p['stock'] ?>)"><?= t('add') ?></button>
                <?php else: ?>
                <button class="btn-p" disabled style="opacity:.5"><?= t('esgotado') ?></button>
                <?php endif; ?>
                <button class="btn-s" id="favbtn" onclick="doFav(<?= $p['id'] ?>,this)"><?= $isFav?'♥ '.($lang==='pt'?'Guardado':'Saved'):'♡ '.($lang==='pt'?'Guardar':'Save') ?></button>
            </div>
        </div>
    </div>

    <!-- AVALIAÇÕES -->
    <div class="stit" style="margin-top:34px"><?= t('reviews') ?></div>

    <?php if ($uid && !$jaRev): ?>
    <div class="fcard" style="margin-bottom:20px">
        <h4 style="margin-bottom:11px;font-size:14px"><?= $lang==='pt'?'Escreve a tua avaliação':'Write your review' ?></h4>
        <?php if ($rErr): ?><div class="ale ale-e"><?= htmlspecialchars($rErr) ?></div><?php endif; ?>
        <form method="POST">
            <div class="sst" id="sst">
                <?php for ($s=1;$s<=5;$s++): ?>
                <span onclick="setStar(<?= $s ?>)">★</span>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="stars" id="stars" value="0">
            <div class="fld" style="margin-bottom:11px">
                <label><?= $lang==='pt'?'Comentário (opcional)':'Comment (optional)' ?></label>
                <textarea name="com" rows="3"></textarea>
            </div>
            <button class="btn-p" name="avr" type="submit"><?= $lang==='pt'?'Enviar':'Submit' ?></button>
        </form>
    </div>
    <?php elseif ($uid && $jaRev): ?>
    <div class="ale ale-i" style="margin-bottom:18px"><?= $lang==='pt'?'Já avaliaste este produto.':'You already reviewed this product.' ?></div>
    <?php elseif (!$uid): ?>
    <div class="ale ale-i" style="margin-bottom:18px">
        <a href="login.php" style="color:var(--or)"><?= $lang==='pt'?'Faz login para avaliar':'Login to review' ?></a>
    </div>
    <?php endif; ?>

    <?php if (empty($revs)): ?>
    <p style="color:var(--muted);font-size:14px"><?= t('no_reviews') ?></p>
    <?php else: foreach ($revs as $r): ?>
    <div class="rvc">
        <div class="rvh">
            <img class="rvav" src="img.php?id=<?= $r['uid'] ?>&t=user"
                 onerror="this.src='https://ui-avatars.com/api/?name=U&background=FF5A1F&color=fff'" alt="">
            <span class="rvnm"><?= htmlspecialchars($r['nome_completo']) ?></span>
            <span class="rvdt"><?= date('d/m/Y', strtotime($r['criado_em'])) ?></span>
        </div>
        <div class="rvst"><?= str_repeat('★',$r['estrelas']).str_repeat('☆',5-$r['estrelas']) ?></div>
        <?php if ($r['comentario']): ?>
        <p class="rvtx"><?= htmlspecialchars($r['comentario']) ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; endif; ?>

    <!-- RELACIONADOS -->
    <?php if ($rel): ?>
    <div class="stit" style="margin-top:34px"><?= t('related') ?></div>
    <div class="rlg">
        <?php foreach ($rel as $rp):
            $rpr = ($rp['em_promocao'] && $rp['preco_promo']) ? (float)$rp['preco_promo'] : (float)$rp['preco'];
        ?>
        <div class="pc" style="cursor:pointer" onclick="location.href='produto.php?id=<?= $rp['id'] ?>'">
            <div class="pi">
                <img src="img.php?id=<?= $rp['id'] ?>" loading="lazy"
                     onerror="this.src='https://placehold.co/300x200/111/FF5A1F?text=LTS'"
                     alt="<?= htmlspecialchars($rp['nome']) ?>">
            </div>
            <div class="pb">
                <h3><?= htmlspecialchars($rp['nome']) ?></h3>
                <div class="pp"><span class="now"><?= number_format($rpr,0,'.',',') ?> Kz</span></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="fls">
    <a class="fl f-wa" href="https://wa.me/<?= PHONE ?>?text=<?= urlencode($lang==='pt'?'Olá! Preciso de ajuda.':'Hello! Need help.') ?>" target="_blank"><img src="whatsapp.png" alt="WhatsApp" style="width:28px;height:28px;object-fit:contain"></a>
    <a class="fl f-cl" href="tel:+<?= PHONE ?>">📞</a>
</div>

<script>
var MX=<?= $p['stock'] ?>;
function aq(d){var e=document.getElementById('pdq');if(!e)return;e.value=Math.max(1,Math.min(parseInt(e.value)+d,MX));}

function addAndGo(id,name,price,stock){
    var q=parseInt(document.getElementById('pdq').value)||1;
    var c=[];try{c=JSON.parse(localStorage.getItem('lts_cart')||'[]');if(!Array.isArray(c))c=[];}catch(e){c=[];}
    var idx=-1;for(var i=0;i<c.length;i++){if(c[i].id===id){idx=i;break;}}
    if(idx>-1){c[idx].qty=Math.min(c[idx].qty+q,stock);}
    else{c.push({id:id,name:name,price:price,stock:stock,img:'img.php?id='+id,qty:q});}
    localStorage.setItem('lts_cart',JSON.stringify(c));
    window.location.href='index.php';
}

function setStar(n){
    document.getElementById('stars').value=n;
    var s=document.querySelectorAll('#sst span');
    for(var i=0;i<s.length;i++) s[i].classList.toggle('on',i<n);
}

function doFav(id,btn){
    <?php if(!$uid): ?>window.location.href='login.php';return;<?php endif; ?>
    var fd=new FormData();fd.append('pid',id);
    fetch('fav.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){if(d.ok){btn.textContent=d.fav?'♥ <?= $lang==='pt'?'Guardado':'Saved' ?>':'♡ <?= $lang==='pt'?'Guardar':'Save' ?>';}});
}
</script>
</body>
</html>
