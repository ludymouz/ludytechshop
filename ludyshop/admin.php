<?php
require_once 'lang.php';
require_once 'db.php';
requireAdmin();

$tab = $_GET['tab'] ?? 'dash';
$msg = $err = '';

// ── PRODUTOS ──────────────────────────────────
if (isset($_POST['nprod'])) {
    $nome  = trim($_POST['nome']  ?? '');
    $desc  = trim($_POST['desc']  ?? '');
    $preco = (float)($_POST['preco'] ?? 0);
    $promoP= ($_POST['promoP'] ?? '') !== '' ? (float)$_POST['promoP'] : 0.0;
    $emP   = isset($_POST['emP']) ? 1 : 0;
    $stock = (int)($_POST['stock'] ?? 0);
    $catId = (int)($_POST['catId'] ?? 0);
    list($img,$imgT) = readImg('img');
    if ($img) {
        $st  = $conn->prepare("INSERT INTO produtos (nome,descricao,preco,preco_promo,em_promocao,stock,imagem,imagem_tipo,categoria_id) VALUES (?,?,?,?,?,?,?,?,?)");
        $nul = null;
        $st->bind_param("ssddiibsi", $nome,$desc,$preco,$promoP,$emP,$stock,$nul,$imgT,$catId);
        $st->send_long_data(6, $img);
    } else {
        $st = $conn->prepare("INSERT INTO produtos (nome,descricao,preco,preco_promo,em_promocao,stock,categoria_id) VALUES (?,?,?,?,?,?,?)");
        $st->bind_param("ssddiii", $nome,$desc,$preco,$promoP,$emP,$stock,$catId);
    }
    $st->execute() ? $msg='Produto criado!' : $err=$conn->error;
    $st->close(); $tab='produtos';
}

if (isset($_POST['eprod'])) {
    $pid   = (int)($_POST['pid']   ?? 0);
    $nome  = trim($_POST['nome']   ?? '');
    $desc  = trim($_POST['desc']   ?? '');
    $preco = (float)($_POST['preco'] ?? 0);
    $promoP= ($_POST['promoP'] ?? '') !== '' ? (float)$_POST['promoP'] : 0.0;
    $emP   = isset($_POST['emP']) ? 1 : 0;
    $stock = (int)($_POST['stock'] ?? 0);
    $catId = (int)($_POST['catId'] ?? 0);
    list($img,$imgT) = readImg('img');
    if ($img) {
        $st  = $conn->prepare("UPDATE produtos SET nome=?,descricao=?,preco=?,preco_promo=?,em_promocao=?,stock=?,imagem=?,imagem_tipo=?,categoria_id=? WHERE id=?");
        $nul = null;
        $st->bind_param("ssddiibsii", $nome,$desc,$preco,$promoP,$emP,$stock,$nul,$imgT,$catId,$pid);
        $st->send_long_data(6, $img);
    } else {
        $st = $conn->prepare("UPDATE produtos SET nome=?,descricao=?,preco=?,preco_promo=?,em_promocao=?,stock=?,categoria_id=? WHERE id=?");
        $st->bind_param("ssddiiii", $nome,$desc,$preco,$promoP,$emP,$stock,$catId,$pid);
    }
    $st->execute() ? $msg='Produto actualizado!' : $err=$conn->error;
    $st->close(); $tab='produtos';
}

if (isset($_POST['dprod'])) {
    $conn->query("DELETE FROM produtos WHERE id=".(int)$_POST['dprod']);
    $msg='Produto apagado.'; $tab='produtos';
}

// ── CATEGORIAS ────────────────────────────────
if (isset($_POST['ncat'])) {
    $nm = trim($_POST['cnome'] ?? '');
    $st = $conn->prepare("INSERT IGNORE INTO categorias (nome) VALUES (?)");
    $st->bind_param("s",$nm); $st->execute(); $st->close();
    $msg='Categoria criada!'; $tab='cats';
}
if (isset($_POST['dcat'])) {
    $conn->query("DELETE FROM categorias WHERE id=".(int)$_POST['dcat']);
    $msg='Categoria apagada.'; $tab='cats';
}

// ── PEDIDOS ───────────────────────────────────
if (isset($_POST['upest'])) {
    $pid  = (int)($_POST['pid']  ?? 0);
    $uidP = (int)($_POST['uidP'] ?? 0);
    $est  = $_POST['est'] ?? '';
    $ok2  = ['Pendente','Confirmado','Em entrega','Entregue','Cancelado'];
    if (in_array($est,$ok2)) {
        $st=$conn->prepare("UPDATE pedidos SET estado=? WHERE id=?");
        $st->bind_param("si",$est,$pid); $st->execute(); $st->close();
        notif($conn,$uidP,"O teu pedido #$pid mudou para: $est.");
        $msg='Estado actualizado!';
    }
    $tab='pedidos';
}
if (isset($_POST['dped'])) {
    $conn->query("DELETE FROM pedidos WHERE id=".(int)$_POST['dped']);
    $msg='Pedido apagado.'; $tab='pedidos';
}

// ── UTILIZADORES ──────────────────────────────
if (isset($_POST['duser'])) {
    $conn->query("DELETE FROM utilizadores WHERE id=".(int)$_POST['duser']);
    $msg='Utilizador apagado.'; $tab='users';
}

// ── BANNERS ───────────────────────────────────
if (isset($_POST['nbanner'])) {
    $t2=trim($_POST['btit']??''); $s2=trim($_POST['bsub']??''); $l2=trim($_POST['blink']??'#');
    $st=$conn->prepare("INSERT INTO banners (titulo,subtitulo,link) VALUES (?,?,?)");
    $st->bind_param("sss",$t2,$s2,$l2); $st->execute(); $st->close();
    $msg='Banner criado!'; $tab='banners';
}
if (isset($_POST['dbanner'])) {
    $conn->query("DELETE FROM banners WHERE id=".(int)$_POST['dbanner']);
    $msg='Banner apagado.'; $tab='banners';
}

// ── DADOS ────────────────────────────────────
$cats    = $conn->query("SELECT * FROM categorias ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$prods   = $conn->query("SELECT p.*,c.nome cn FROM produtos p LEFT JOIN categorias c ON c.id=p.categoria_id ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$pedidos = $conn->query("SELECT pd.*,u.nome_completo,u.telefone FROM pedidos pd JOIN utilizadores u ON u.id=pd.user_id ORDER BY pd.id DESC")->fetch_all(MYSQLI_ASSOC);
$users   = $conn->query("SELECT * FROM utilizadores ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$banners = $conn->query("SELECT * FROM banners ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$editP   = isset($_GET['ep']) ? $conn->query("SELECT * FROM produtos WHERE id=".(int)$_GET['ep']." LIMIT 1")->fetch_assoc() : null;
$tRec    = array_sum(array_column($pedidos,'total'));
$ecores  = ['Pendente'=>'#e67e22','Confirmado'=>'#2980b9','Em entrega'=>'#ad5b44','Entregue'=>'#27ae60','Cancelado'=>'#e74c3c'];

// Gráfico 7 dias
$cl=[]; $cd=[];
for ($i=6;$i>=0;$i--) {
    $d=date('Y-m-d',strtotime("-{$i} days"));
    $cl[]=date('d/m',strtotime($d));
    $r=$conn->query("SELECT COALESCE(SUM(total),0) t FROM pedidos WHERE DATE(criado_em)='$d'")->fetch_assoc();
    $cd[]=(float)$r['t'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — <?= t('shop') ?></title>
<link rel="stylesheet" href="style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
body{display:flex;min-height:100vh}
.asb{width:205px;flex-shrink:0;background:var(--blk);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto}
.asb-logo{padding:18px 14px;border-bottom:1px solid rgba(255,255,255,.08)}
.asb-logo .lt{font-size:16px;font-weight:900;color:#fff}
.asb-logo .lt span{color:var(--or)}
.asb-logo small{display:block;color:rgba(255,255,255,.4);font-size:10px;margin-top:2px}
.asb-nav{flex:1;padding:10px 0}
.asb-nav a{display:flex;align-items:center;gap:8px;padding:10px 14px;color:rgba(255,255,255,.65);font-size:13px;font-weight:600;border-left:3px solid transparent;transition:background var(--tr),color var(--tr),border-color var(--tr)}
.asb-nav a:hover,.asb-nav a.on{background:rgba(255,90,31,.12);color:#fff;border-left-color:var(--or)}
.asb-bot{padding:11px 14px;border-top:1px solid rgba(255,255,255,.08)}
.asb-bot a{display:flex;align-items:center;gap:7px;color:rgba(255,255,255,.5);font-size:12px}
.asb-bot a:hover{color:var(--or)}
.amn{flex:1;display:flex;flex-direction:column;min-width:0}
.atop{height:52px;background:var(--card);border-bottom:1px solid var(--bdr);display:flex;align-items:center;padding:0 20px;gap:10px;position:sticky;top:0;z-index:100}
.atop h1{font-size:15px;font-weight:800}
.aham{display:none;background:none;border:none;color:var(--text);font-size:22px;padding:4px}
.ac{flex:1;padding:20px}
.sgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:13px;margin-bottom:22px}
.sc{background:var(--card);border-radius:var(--rM);box-shadow:var(--sh);padding:17px 18px;display:flex;align-items:center;gap:13px}
.si{width:42px;height:42px;border-radius:var(--r);background:var(--orf);display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0}
.sv{font-size:19px;font-weight:900;color:var(--or)}
.sl2{font-size:12px;color:var(--muted);margin-top:2px}
.cc{background:var(--card);border-radius:var(--rM);box-shadow:var(--sh);padding:18px;margin-bottom:20px}
.cc h3{font-size:14px;font-weight:800;margin-bottom:13px}
.atw{background:var(--card);border-radius:var(--rM);box-shadow:var(--sh);overflow:hidden;margin-bottom:20px}
.ath{padding:13px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--bdr)}
.ath h3{font-size:14px;font-weight:800}
table{width:100%;border-collapse:collapse}
th{background:var(--bg2);padding:9px 12px;text-align:left;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)}
td{padding:9px 12px;font-size:13px;border-bottom:1px solid var(--bdr);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--bg2)}
.tdimg{width:42px;height:42px;object-fit:cover;border-radius:var(--r)}
.fp{background:var(--card);border-radius:var(--rM);box-shadow:var(--sh);padding:20px;margin-bottom:20px}
.fp h3{font-size:14px;font-weight:800;margin-bottom:14px;padding-bottom:9px;border-bottom:1px solid var(--bdr)}
.fpg{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px}
.fpg .full{grid-column:1/-1}
.eb{padding:3px 8px;border-radius:20px;font-size:11px;font-weight:800;color:#fff;display:inline-block}
.pimgprev{width:64px;height:64px;object-fit:cover;border-radius:var(--r);border:2px solid var(--bdr);margin-top:5px}
@media(max-width:760px){
    .asb{position:fixed;left:-205px;top:0;z-index:500;height:100vh;transition:left .3s}
    .asb.open{left:0}
    .aham{display:block}
    .fpg{grid-template-columns:1fr}
    .sgrid{grid-template-columns:1fr 1fr}
}
@media(max-width:440px){.sgrid{grid-template-columns:1fr}}
</style>
</head>
<body>

<aside class="asb" id="asb">
    <div class="asb-logo"><div class="lt">LUDY <span>TECH</span></div><small>Painel de Administração</small></div>
    <nav class="asb-nav">
        <a href="?tab=dash"     class="<?= $tab==='dash'?'on':'' ?>">📊 Dashboard</a>
        <a href="?tab=produtos" class="<?= $tab==='produtos'?'on':'' ?>">📦 Produtos</a>
        <a href="?tab=cats"     class="<?= $tab==='cats'?'on':'' ?>">🏷 Categorias</a>
        <a href="?tab=pedidos"  class="<?= $tab==='pedidos'?'on':'' ?>">🛒 Pedidos</a>
        <a href="?tab=users"    class="<?= $tab==='users'?'on':'' ?>">👥 Utilizadores</a>
        <a href="?tab=banners"  class="<?= $tab==='banners'?'on':'' ?>">🖼 Banners</a>
    </nav>
    <div class="asb-bot"><a href="index.php">🏪 Ver loja</a></div>
</aside>

<div class="amn">
    <div class="atop">
        <button class="aham" onclick="document.getElementById('asb').classList.toggle('open')">☰</button>
        <h1><?= ucfirst($tab) ?></h1>
        <div style="flex:1"></div>
        <?php if ($msg): ?><div class="ale ale-o" style="margin:0;padding:6px 12px"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="ale ale-e" style="margin:0;padding:6px 12px"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <a href="logout.php" class="btn-d" style="padding:7px 12px">⏻ Sair</a>
    </div>

    <div class="ac">

    <?php if ($tab === 'dash'): ?>
    <div class="sgrid">
        <div class="sc"><div class="si">📦</div><div><div class="sv"><?= count($prods) ?></div><div class="sl2">Produtos</div></div></div>
        <div class="sc"><div class="si">🛒</div><div><div class="sv"><?= count($pedidos) ?></div><div class="sl2">Pedidos</div></div></div>
        <div class="sc"><div class="si">👥</div><div><div class="sv"><?= count($users) ?></div><div class="sl2">Clientes</div></div></div>
        <div class="sc"><div class="si">💰</div><div><div class="sv"><?= number_format($tRec,0,'.',',') ?> Kz</div><div class="sl2">Receita total</div></div></div>
    </div>
    <div class="cc"><h3>📈 Vendas — últimos 7 dias</h3><canvas id="chart" height="90"></canvas></div>
    <div class="atw">
        <div class="ath"><h3>🕐 Últimos pedidos</h3><a href="?tab=pedidos" style="color:var(--or);font-size:13px">Ver todos</a></div>
        <table>
            <tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Data</th></tr>
            <?php foreach (array_slice($pedidos,0,5) as $pe): ?>
            <tr>
                <td><?= $pe['id'] ?></td>
                <td><?= htmlspecialchars($pe['nome_completo']) ?></td>
                <td style="color:var(--or);font-weight:700"><?= number_format($pe['total'],0,'.',',') ?> Kz</td>
                <td><span class="eb" style="background:<?= $ecores[$pe['estado']]??'#888' ?>"><?= $pe['estado'] ?></span></td>
                <td style="font-size:12px;color:var(--muted)"><?= date('d/m/Y H:i',strtotime($pe['criado_em'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php elseif ($tab === 'produtos'): ?>
    <div class="fp">
        <h3><?= $editP?'✏ Editar':'➕ Novo' ?> Produto</h3>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($editP): ?><input type="hidden" name="pid" value="<?= $editP['id'] ?>"><?php endif; ?>
            <div class="fpg">
                <div class="fld"><label>Nome *</label><input type="text" name="nome" required value="<?= htmlspecialchars($editP['nome']??'') ?>"></div>
                <div class="fld"><label>Categoria</label>
                    <select name="catId">
                        <option value="0">— Sem categoria —</option>
                        <?php foreach($cats as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($editP['categoria_id']??0)==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fld"><label>Preço (Kz) *</label><input type="number" step="0.01" name="preco" required value="<?= $editP['preco']??'' ?>"></div>
                <div class="fld"><label>Stock *</label><input type="number" name="stock" required value="<?= $editP['stock']??0 ?>"></div>
                <div class="fld"><label>Preço Promoção (Kz)</label><input type="number" step="0.01" name="promoP" value="<?= $editP['preco_promo']??'' ?>"></div>
                <div class="fld" style="flex-direction:row;align-items:center;gap:8px;padding-top:20px">
                    <input type="checkbox" name="emP" id="emP" <?= ($editP['em_promocao']??0)?'checked':'' ?>>
                    <label for="emP" style="text-transform:none;font-size:13px;font-weight:600">Em promoção</label>
                </div>
                <div class="fld full"><label>Descrição</label><textarea name="desc" rows="3"><?= htmlspecialchars($editP['descricao']??'') ?></textarea></div>
                <div class="fld full">
                    <label>Imagem do produto</label>
                    <input type="file" name="img" accept="image/*" onchange="pprev(this)">
                    <img id="pprev" class="pimgprev"
                         src="<?= $editP?'img.php?id='.$editP['id']:'https://placehold.co/64x64/111/FF5A1F?text=LTS' ?>"
                         style="<?= $editP?'':'display:none' ?>" alt="">
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button class="btn-p" type="submit" name="<?= $editP?'eprod':'nprod' ?>"><?= $editP?'💾 Guardar':'➕ Criar produto' ?></button>
                <?php if($editP): ?><a href="?tab=produtos" class="btn-s">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </div>
    <div class="atw">
        <div class="ath"><h3>📦 Produtos (<?= count($prods) ?>)</h3></div>
        <table>
            <tr><th>Img</th><th>Nome</th><th>Cat.</th><th>Preço</th><th>Stock</th><th>Promo</th><th>Ações</th></tr>
            <?php foreach($prods as $p2): ?>
            <tr>
                <td><img class="tdimg" src="img.php?id=<?= $p2['id'] ?>" onerror="this.src='https://placehold.co/42x42/111/FF5A1F?text=LTS'" alt=""></td>
                <td><strong><?= htmlspecialchars($p2['nome']) ?></strong></td>
                <td><?= htmlspecialchars($p2['cn']??'—') ?></td>
                <td><?= number_format($p2['preco'],0,'.',',') ?> Kz</td>
                <td style="color:<?= $p2['stock']<=0?'#e74c3c':($p2['stock']<5?'#e67e22':'#27ae60') ?>;font-weight:700"><?= $p2['stock'] ?></td>
                <td><?= $p2['em_promocao']?'<span class="eb" style="background:var(--or)">SIM</span>':'—' ?></td>
                <td style="display:flex;gap:5px;flex-wrap:wrap">
                    <a href="?tab=produtos&ep=<?= $p2['id'] ?>" class="btn-p" style="padding:5px 10px;font-size:11px">✏</a>
                    <form method="POST" onsubmit="return confirm('Apagar produto?')">
                        <input type="hidden" name="dprod" value="<?= $p2['id'] ?>">
                        <button class="btn-d" type="submit">🗑</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php elseif ($tab === 'cats'): ?>
    <div class="fp">
        <h3>➕ Nova Categoria</h3>
        <form method="POST" style="display:flex;gap:8px;align-items:flex-end">
            <div class="fld" style="flex:1"><label>Nome</label><input type="text" name="cnome" required></div>
            <button class="btn-p" name="ncat" type="submit">Criar</button>
        </form>
    </div>
    <div class="atw">
        <div class="ath"><h3>🏷 Categorias (<?= count($cats) ?>)</h3></div>
        <table>
            <tr><th>#</th><th>Nome</th><th>Produtos</th><th>Ação</th></tr>
            <?php foreach($cats as $c):
                $np=$conn->query("SELECT COUNT(*) c FROM produtos WHERE categoria_id={$c['id']}")->fetch_assoc()['c'];
            ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                <td><?= $np ?></td>
                <td><form method="POST" onsubmit="return confirm('Apagar categoria?')">
                    <input type="hidden" name="dcat" value="<?= $c['id'] ?>">
                    <button class="btn-d" type="submit">🗑 Apagar</button>
                </form></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php elseif ($tab === 'pedidos'): ?>
    <div class="atw">
        <div class="ath">
            <h3>🛒 Pedidos (<?= count($pedidos) ?>)</h3>
            <span style="font-weight:800;color:var(--or)"><?= number_format($tRec,0,'.',',') ?> Kz receita</span>
        </div>
        <table>
            <tr><th>#</th><th>Cliente</th><th>Produtos</th><th>Total</th><th>Pag.</th><th>Entrega</th><th>Estado</th><th>Ação</th></tr>
            <?php foreach($pedidos as $pe): ?>
            <tr>
                <td><?= $pe['id'] ?></td>
                <td><strong><?= htmlspecialchars($pe['nome_completo']) ?></strong><br><small style="color:var(--muted)"><?= htmlspecialchars($pe['telefone']) ?></small></td>
                <td style="max-width:175px;font-size:12px;color:var(--text2)"><?= htmlspecialchars(mb_substr($pe['produtos_txt'],0,70)) ?><?= mb_strlen($pe['produtos_txt'])>70?'...':'' ?></td>
                <td style="color:var(--or);font-weight:800"><?= number_format($pe['total'],0,'.',',') ?> Kz</td>
                <td style="font-size:12px"><?= htmlspecialchars($pe['metodo_pag']) ?></td>
                <td style="font-size:12px"><?= htmlspecialchars($pe['tipo_entrega']) ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:4px;align-items:center">
                        <input type="hidden" name="pid" value="<?= $pe['id'] ?>">
                        <input type="hidden" name="uidP" value="<?= $pe['user_id'] ?>">
                        <select name="est" style="font-size:11px;padding:4px;border:1px solid var(--bdr);border-radius:var(--r);background:var(--bg);color:var(--text)">
                            <?php foreach(['Pendente','Confirmado','Em entrega','Entregue','Cancelado'] as $es): ?>
                            <option value="<?= $es ?>" <?= $pe['estado']===$es?'selected':'' ?>><?= $es ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn-p" style="padding:4px 8px;font-size:11px" name="upest" type="submit">✔</button>
                    </form>
                </td>
                <td>
                    <form method="POST" onsubmit="return confirm('Apagar pedido?')">
                        <input type="hidden" name="dped" value="<?= $pe['id'] ?>">
                        <button class="btn-d" type="submit">🗑</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php elseif ($tab === 'users'): ?>
    <div class="atw">
        <div class="ath"><h3>👥 Utilizadores (<?= count($users) ?>)</h3></div>
        <table>
            <tr><th>Foto</th><th>Nome</th><th>Username</th><th>Telef.</th><th>Género</th><th>Idade</th><th>Data</th><th>Ação</th></tr>
            <?php foreach($users as $u): ?>
            <tr>
                <td><img class="tdimg" style="border-radius:50%" src="img.php?id=<?= $u['id'] ?>&t=user" onerror="this.src='https://ui-avatars.com/api/?name=U&background=FF5A1F&color=fff'" alt=""></td>
                <td><strong><?= htmlspecialchars($u['nome_completo']) ?></strong><br><small style="color:var(--muted)"><?= htmlspecialchars($u['email']??'') ?></small></td>
                <td>@<?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['telefone']) ?></td>
                <td><?= htmlspecialchars($u['genero']) ?></td>
                <td><?= $u['idade'] ?></td>
                <td style="font-size:12px;color:var(--muted)"><?= date('d/m/Y',strtotime($u['criado_em'])) ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Apagar utilizador e todos os seus dados?')">
                        <input type="hidden" name="duser" value="<?= $u['id'] ?>">
                        <button class="btn-d" type="submit">🗑</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php elseif ($tab === 'banners'): ?>
    <div class="fp">
        <h3>➕ Novo Banner</h3>
        <form method="POST">
            <div class="fpg">
                <div class="fld"><label>Título *</label><input type="text" name="btit" required></div>
                <div class="fld"><label>Link</label><input type="text" name="blink" value="#prods"></div>
                <div class="fld full"><label>Subtítulo</label><input type="text" name="bsub"></div>
            </div>
            <button class="btn-p" name="nbanner" type="submit">➕ Criar</button>
        </form>
    </div>
    <div class="atw">
        <div class="ath"><h3>🖼 Banners (<?= count($banners) ?>)</h3></div>
        <table>
            <tr><th>#</th><th>Título</th><th>Subtítulo</th><th>Link</th><th>Ativo</th><th>Ação</th></tr>
            <?php foreach($banners as $b): ?>
            <tr>
                <td><?= $b['id'] ?></td>
                <td><strong><?= htmlspecialchars($b['titulo']) ?></strong></td>
                <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($b['subtitulo']) ?></td>
                <td style="font-size:12px"><?= htmlspecialchars($b['link']) ?></td>
                <td><?= $b['ativo']?'✅':'❌' ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Apagar banner?')">
                        <input type="hidden" name="dbanner" value="<?= $b['id'] ?>">
                        <button class="btn-d" type="submit">🗑</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    </div><!-- /ac -->
</div><!-- /amn -->

<script>
<?php if ($tab === 'dash'): ?>
var isDark=window.matchMedia('(prefers-color-scheme:dark)').matches;
new Chart(document.getElementById('chart').getContext('2d'),{
    type:'bar',
    data:{labels:<?= json_encode($cl) ?>,datasets:[{label:'Kz',data:<?= json_encode($cd) ?>,backgroundColor:'rgba(255,90,31,.75)',borderColor:'#FF5A1F',borderWidth:2,borderRadius:4}]},
    options:{responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return c.parsed.y.toFixed(0)+' Kz';}}}},
    scales:{y:{ticks:{color:isDark?'#999':'#666',callback:function(v){return v.toLocaleString()+' Kz';}},grid:{color:isDark?'#2A2A2A':'#eee'}},x:{ticks:{color:isDark?'#999':'#666'},grid:{display:false}}}}
});
<?php endif; ?>
document.addEventListener('click',function(e){
    var a=document.getElementById('asb');
    if(a&&!a.contains(e.target)&&!e.target.classList.contains('aham'))a.classList.remove('open');
});
function pprev(i){
    if(i.files&&i.files[0]){
        var r=new FileReader();
        r.onload=function(e){var img=document.getElementById('pprev');img.src=e.target.result;img.style.display='block';};
        r.readAsDataURL(i.files[0]);
    }
}
</script>
</body>
</html>
