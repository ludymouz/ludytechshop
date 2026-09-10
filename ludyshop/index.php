<?php
require_once 'lang.php';
require_once 'db.php';
header('Cache-Control: public, max-age=60');
$uid    = $_SESSION['user_id'] ?? null;
$uname  = $_SESSION['username'] ?? '';
$adm    = isAdmin();
$q      = trim($_GET['q'] ?? '');
$cat    = (int)($_GET['cat'] ?? 0);

// Produtos
$w = "WHERE 1=1"; $p2 = []; $ty = "";
if ($q)   { $w .= " AND p.nome LIKE ?"; $p2[] = "%$q%"; $ty .= "s"; }
if ($cat) { $w .= " AND p.categoria_id=?"; $p2[] = $cat; $ty .= "i"; }
$sql = "SELECT p.id,p.nome,p.preco,p.preco_promo,p.em_promocao,p.stock,p.categoria_id,
        COALESCE(AVG(a.estrelas),0) AS media, COUNT(DISTINCT a.id) AS nrev
        FROM produtos p LEFT JOIN avaliacoes a ON a.produto_id=p.id
        $w GROUP BY p.id ORDER BY p.id DESC";
$st = $conn->prepare($sql);
if ($p2) $st->bind_param($ty, ...$p2);
$st->execute();
$prods = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$cats    = $conn->query("SELECT * FROM categorias ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$banners = $conn->query("SELECT * FROM banners WHERE ativo=1 LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$favs = [];
if ($uid) {
    $f = $conn->prepare("SELECT produto_id FROM favoritos WHERE user_id=?");
    $f->bind_param("i",$uid); $f->execute();
    foreach ($f->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $favs[] = $r['produto_id'];
    $f->close();
}
$ncount = 0;
if ($uid) {
    $n = $conn->prepare("SELECT COUNT(*) c FROM notificacoes WHERE user_id=? AND lida=0");
    $n->bind_param("i",$uid); $n->execute();
    $ncount = $n->get_result()->fetch_assoc()['c']; $n->close();
}
$pay     = payOpen();
$payOpen = $pay['open'] ? 'true' : 'false';
$payCl   = $pay['close'];
$payAt   = $pay['open_at'];
$delCost = DEL_COST;
$phone   = PHONE;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= t('shop') ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div id="spl"><div class="sl">LTS</div><p><?= t('shop') ?></p></div>
<div class="ov" id="ov"></div>

<header class="hdr">
    <button class="ham" id="ham"><span></span><span></span><span></span></button>
    <a href="index.php" class="logo">LUDY <span>TECH</span> SHOP</a>
    <form class="hs" method="GET">
        <?php if ($cat): ?><input type="hidden" name="cat" value="<?= $cat ?>"><?php endif; ?>
        <input type="text" name="q" placeholder="<?= t('search') ?>" value="<?= htmlspecialchars($q) ?>">
        <button type="submit">🔍</button>
    </form>
    <div class="ha">
        <a href="?lang=<?= $lang==='pt'?'en':'pt' ?><?= $cat?"&cat=$cat":'' ?><?= $q?"&q=".urlencode($q):'' ?>" class="lbtn"><?= $lang==='pt'?'EN':'PT' ?></a>
        <?php if ($uid || $adm): ?>
            <a href="perfil.php" class="hb sm">👤 <?= htmlspecialchars(explode(' ',$uname)[0]) ?><?php if ($ncount): ?> <span class="hbdg"><?= $ncount ?></span><?php endif; ?></a>
            <?php if ($adm): ?><a href="admin.php" class="hb sm">🛠</a><?php endif; ?>
            <a href="logout.php" class="hb">⏻</a>
        <?php else: ?>
            <a href="login.php"   class="hb sm"><?= t('login') ?></a>
            <a href="register.php" class="hb sm"><?= t('register') ?></a>
        <?php endif; ?>
        <button class="hb" id="cartBtn">🛒 <span class="hbdg" id="cc">0</span></button>
    </div>
</header>

<div class="lay">
    <nav class="sb" id="sb">
        <p class="sb-tit"><?= t('categories') ?></p>
        <button class="cb <?= $cat===0?'on':'' ?>" onclick="gocat(0)"><?= t('all') ?></button>
        <?php foreach ($cats as $c): ?>
        <button class="cb <?= $cat===$c['id']?'on':'' ?>" onclick="gocat(<?= $c['id'] ?>)"><?= htmlspecialchars($c['nome']) ?></button>
        <?php endforeach; ?>
    </nav>

    <main class="mn">
        <!-- BANNER -->
        <div class="ban">
            <?php if ($banners): foreach ($banners as $i => $b): ?>
            <div class="bsl <?= $i===0?'on':'' ?>">
                <h2><?= htmlspecialchars($b['titulo']) ?></h2>
                <p><?= htmlspecialchars($b['subtitulo']) ?></p>
                <a href="<?= htmlspecialchars($b['link']) ?>" class="bcta"><?= $lang==='pt'?'Ver mais':'See more' ?></a>
            </div>
            <?php endforeach; else: ?>
            <div class="bsl on">
                <h2>Bem-vindo à Ludy Tech Shop! 🔥</h2>
                <p>Os melhores produtos em Uíge, Angola.</p>
                <a href="#prods" class="bcta">Ver produtos</a>
            </div>
            <?php endif; ?>
            <?php if (count($banners) > 1): ?>
            <button class="barr bprev" onclick="mvb(-1)">‹</button>
            <button class="barr bnext" onclick="mvb(1)">›</button>
            <div class="bdt" id="bdt">
                <?php foreach ($banners as $i => $b): ?>
                <span class="<?= $i===0?'on':'' ?>" onclick="gob(<?= $i ?>)"></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- PRODUTOS -->
        <div class="stit" id="prods"><?= t('products') ?> <small>(<?= count($prods) ?>)</small></div>
        <div class="pg">
        <?php if (empty($prods)): ?>
            <p style="color:var(--muted);grid-column:1/-1;padding:26px 0"><?= $lang==='pt'?'Nenhum produto encontrado.':'No products found.' ?></p>
        <?php else: foreach ($prods as $p): ?>
        <?php
            $esg   = $p['stock'] <= 0;
            $preco = ($p['em_promocao'] && $p['preco_promo']) ? (float)$p['preco_promo'] : (float)$p['preco'];
            $isFav = in_array($p['id'], $favs);
            $ns    = round((float)$p['media']);
            $pj    = json_encode($p['nome']);
        ?>
        <div class="pc">
            <div class="pi">
                <img src="img.php?id=<?= $p['id'] ?>" alt="<?= htmlspecialchars($p['nome']) ?>" loading="lazy"
                     onerror="this.src='https://placehold.co/400x300/111/FF5A1F?text=LTS'">
                <?php if ($p['em_promocao']): ?><span class="tpr"><?= t('promo') ?></span><?php endif; ?>
                <?php if (!$esg && $p['stock'] < 5): ?><span class="tst">⚠ <?= tr('stock_low',['n'=>$p['stock']]) ?></span><?php endif; ?>
                <button class="fbtn <?= $isFav?'on':'' ?>" onclick="doFav(<?= $p['id'] ?>,this)"><?= $isFav?'♥':'♡' ?></button>
            </div>
            <div class="pb">
                <h3 title="<?= htmlspecialchars($p['nome']) ?>"><?= htmlspecialchars($p['nome']) ?></h3>
                <div class="pst">
                    <?php for ($s=1;$s<=5;$s++) echo $s<=$ns?'★':'☆'; ?>
                    <small>(<?= $p['nrev'] ?>)</small>
                </div>
                <div class="pp">
                    <span class="now"><?= number_format($preco,0,'.',',') ?> Kz</span>
                    <?php if ($p['em_promocao'] && $p['preco_promo']): ?>
                    <span class="old"><?= number_format((float)$p['preco'],0,'.',',') ?> Kz</span>
                    <?php endif; ?>
                </div>
                <?php if (!$esg): ?>
                <div class="qr">
                    <button type="button" onclick="aq(<?= $p['id'] ?>,-1)">−</button>
                    <input type="number" id="q<?= $p['id'] ?>" value="1" min="1" max="<?= $p['stock'] ?>" onchange="clq(<?= $p['id'] ?>,<?= $p['stock'] ?>)">
                    <button type="button" onclick="aq(<?= $p['id'] ?>,1,<?= $p['stock'] ?>)">+</button>
                    <span class="qmax">máx <?= $p['stock'] ?></span>
                </div>
                <button class="btn-add"
                    data-id="<?= $p['id'] ?>"
                    data-name="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>"
                    data-price="<?= $preco ?>"
                    data-stock="<?= $p['stock'] ?>"
                    data-img="img.php?id=<?= $p['id'] ?>"
                    onclick="addC(this)"><?= t('add') ?></button>
                <?php else: ?>
                <button class="btn-add" disabled><?= t('esgotado') ?></button>
                <?php endif; ?>
                <button class="btn-view" onclick="location.href='produto.php?id=<?= $p['id'] ?>'"><?= t('details') ?></button>
            </div>
        </div>
        <?php endforeach; endif; ?>
        </div>
    </main>
</div>

<!-- CART PANEL -->
<div class="crt" id="crt">
    <div class="chd">
        <h3>🛒 <?= t('cart') ?></h3>
        <button onclick="closeC()">✕</button>
    </div>
    <div class="cbd" id="cbd"></div>
    <div class="cft">
        <div class="csum">
            <div class="crow"><span><?= t('subtotal') ?></span><span id="csub">0 Kz</span></div>
            <div class="crow" id="cdrow" style="display:none"><span>🚚 <?= t('del_fee') ?></span><span style="color:var(--or);font-weight:700"><?= $delCost ?> Kz</span></div>
            <div class="crow"><span><?= t('total') ?></span><span id="ctot">0 Kz</span></div>
        </div>
        <div class="dwarn" id="dwarn"><?= t('del_warn') ?></div>

        <span class="plbl"><?= t('pay_method') ?></span>
        <div class="og">
            <div class="ro"><input type="radio" name="pay" id="p1" value="Dinheiro vivo" checked><label for="p1">💵 <?= t('pay_cash') ?></label></div>
            <div class="ro"><input type="radio" name="pay" id="p2" value="Multicaixa Express"><label for="p2">📱 Express</label></div>
            <div class="ro"><input type="radio" name="pay" id="p3" value="Transferência BAI"><label for="p3">🏦 BAI</label></div>
            <div class="ro"><input type="radio" name="pay" id="p4" value="Transferência BFA"><label for="p4">🏦 BFA</label></div>
        </div>
        <div class="og" style="margin-top:5px">
            <div class="ro" style="grid-column:1/-1"><input type="radio" name="pay" id="p5" value="Transferência Atlântico"><label for="p5">🏦 Atlântico</label></div>
        </div>

        <span class="plbl"><?= $lang==='pt'?'Tipo de entrega':'Delivery type' ?></span>
        <div class="og">
            <div class="ro">
                <input type="radio" name="del" id="d1" value="entrega" checked onchange="updD('entrega')">
                <label for="d1">🚚 <?= t('delivery') ?><small style="display:block;font-size:9px;opacity:.7">+<?= $delCost ?> Kz</small></label>
            </div>
            <div class="ro">
                <input type="radio" name="del" id="d2" value="levantamento" onchange="updD('levantamento')">
                <label for="d2">🏪 <?= t('pickup') ?><small style="display:block;font-size:9px;opacity:.7"><?= $lang==='pt'?'Grátis':'Free' ?></small></label>
            </div>
        </div>

        <div class="pclosed" id="pcl">🕐 <?= t('pay_closed') ?> <?= $payAt ?> <span id="ptmr"></span></div>
        <button class="btn-co" id="btnco" onclick="doChk()">💬 <?= t('checkout') ?></button>
    </div>
</div>

<!-- FLOATS -->
<div class="fls">
    <a class="fl f-wa" href="https://wa.me/<?= $phone ?>?text=<?= urlencode($lang==='pt'?'Olá, preciso de ajuda!':'Hello, I need help!') ?>" target="_blank" title="WhatsApp">
        <img src="whatsapp.png" alt="WhatsApp" style="width:30px;height:30px;object-fit:contain">
    </a>
    <a class="fl f-cl" href="tel:+<?= $phone ?>">📞</a>
</div>

<!-- FOOTER -->
<footer class="ftr">
    <div class="fg">
        <div class="fc">
            <h5>Ludy Tech Shop</h5>
            <p>📍 <?= t('address') ?></p>
            <p>📞 +<?= $phone ?></p>
        </div>
        <div class="fc">
            <h5><?= $lang==='pt'?'Links':'Links' ?></h5>
            <a href="index.php"><?= $lang==='pt'?'Loja':'Shop' ?></a>
            <a href="perfil.php"><?= t('profile') ?></a>
            <a href="login.php"><?= t('login') ?></a>
            <a href="register.php"><?= t('register') ?></a>
        </div>
        <div class="fc">
            <h5><?= $lang==='pt'?'Horário':'Hours' ?></h5>
            <p><?= $lang==='pt'?'Seg–Sex: 08h–16h':'Mon–Fri: 8am–4pm' ?></p>
            <p><?= $lang==='pt'?'Sáb/Dom: até 14h':'Sat/Sun: until 2pm' ?></p>
            <div class="soc">
                <a href="#" title="Instagram" class="soc-ig">
                    <img src="instagram.svg" alt="Instagram">
                </a>
                <a href="#" title="Facebook" class="soc-fb">
                    <img src="facebook.svg" alt="Facebook">
                </a>
                <a href="#" title="TikTok" class="soc-tt">
                    <img src="tiktok.svg" alt="TikTok">
                </a>
                <a href="#" title="YouTube" class="soc-yt">
                    <img src="youtube.svg" alt="YouTube">
                </a>
            </div>
        </div>
    </div>
    <div class="fbot">© <?= date('Y') ?> Ludy Tech Shop. <?= t('rights') ?></div>
</footer>

<script>
// SPLASH
window.addEventListener('load',function(){setTimeout(function(){var s=document.getElementById('spl');s.style.opacity='0';setTimeout(function(){s.remove();},500);},900);});

// SIDEBAR
var sb=document.getElementById('sb'),ov=document.getElementById('ov'),crt=document.getElementById('crt');
document.getElementById('ham').addEventListener('click',function(){sb.classList.toggle('open');ov.classList.toggle('show');});
ov.addEventListener('click',function(){sb.classList.remove('open');crt.classList.remove('open');ov.classList.remove('show');});
function gocat(id){var u=new URL(window.location.href);if(id)u.searchParams.set('cat',id);else u.searchParams.delete('cat');window.location.href=u.href;}

// QTY
function aq(id,d,mx){var e=document.getElementById('q'+id);if(!e)return;var v=parseInt(e.value)+d;e.value=Math.max(1,mx?Math.min(v,mx):Math.max(v,1));}
function clq(id,mx){var e=document.getElementById('q'+id);if(!e)return;e.value=Math.max(1,Math.min(parseInt(e.value)||1,mx));}

// CART
var DC=<?= $delCost ?>;
var cart=[];
try{cart=JSON.parse(localStorage.getItem('lts_cart')||'[]');if(!Array.isArray(cart))cart=[];}catch(e){cart=[];}

document.getElementById('cartBtn').addEventListener('click',function(){crt.classList.toggle('open');ov.classList.toggle('show');});
function closeC(){crt.classList.remove('open');ov.classList.remove('show');}

function addC(btn){
    var id=parseInt(btn.getAttribute('data-id'));
    var name=btn.getAttribute('data-name');
    var price=parseFloat(btn.getAttribute('data-price'));
    var stock=parseInt(btn.getAttribute('data-stock'));
    var img=btn.getAttribute('data-img');
    var qe=document.getElementById('q'+id);
    var qty=qe?Math.max(1,Math.min(parseInt(qe.value)||1,stock)):1;
    var idx=-1;
    for(var i=0;i<cart.length;i++){if(cart[i].id===id){idx=i;break;}}
    if(idx>-1){cart[idx].qty=Math.min(cart[idx].qty+qty,stock);}
    else{cart.push({id:id,name:name,price:price,stock:stock,img:img,qty:qty});}
    saveC();renderC();crt.classList.add('open');ov.classList.add('show');
}
function saveC(){try{localStorage.setItem('lts_cart',JSON.stringify(cart));}catch(e){}}
function getD(){var e=document.querySelector('input[name="del"]:checked');return e?e.value:'entrega';}

function renderC(){
    var bd=document.getElementById('cbd'),cc=document.getElementById('cc');
    var cs=document.getElementById('csub'),ct=document.getElementById('ctot');
    if(!bd)return;
    if(!cart.length){
        bd.innerHTML='<div class="cempty"><?= t('empty_cart') ?></div>';
        if(cc)cc.textContent='0';if(cs)cs.textContent='0 Kz';if(ct)ct.textContent='0 Kz';return;
    }
    var isD=getD()==='entrega',sub=0,cnt=0,h='';
    for(var i=0;i<cart.length;i++){
        var it=cart[i];sub+=it.price*it.qty;cnt+=it.qty;
        h+='<div class="ci">'
          +'<img src="'+it.img+'" onerror="this.src=\'https://placehold.co/54x54/111/FF5A1F?text=LTS\'" alt="'+it.name+'">'
          +'<div class="cii"><h4>'+it.name+'</h4>'
          +'<div class="cip">'+(it.price*it.qty).toFixed(0)+' Kz</div>'
          +'<div class="ciq"><button onclick="cq('+i+',-1)">−</button><span>'+it.qty+'</span><button onclick="cq('+i+',1)">+</button></div>'
          +'</div><button class="crm" onclick="rm('+i+')">🗑</button></div>';
    }
    bd.innerHTML=h;
    var tot=sub+(isD?DC:0);
    if(cc)cc.textContent=cnt;if(cs)cs.textContent=sub.toFixed(0)+' Kz';if(ct)ct.textContent=tot.toFixed(0)+' Kz';
}
function cq(i,d){if(!cart[i])return;cart[i].qty=Math.max(1,Math.min(cart[i].qty+d,cart[i].stock));saveC();renderC();}
function rm(i){cart.splice(i,1);saveC();renderC();}

function updD(v){
    var r=document.getElementById('cdrow'),w=document.getElementById('dwarn');
    var isD=v==='entrega';
    if(r)r.style.display=isD?'flex':'none';
    if(w)w.style.display=isD?'block':'none';
    renderC();
}

// PAYMENT HOURS
var PO=<?= $payOpen ?>,PCL='<?= $payCl ?>',PAT='<?= $payAt ?>';
(function(){
    var m=document.getElementById('pcl'),b=document.getElementById('btnco');
    if(!PO){
        if(m)m.style.display='block';
        if(b)b.disabled=true;
        var tmr=document.getElementById('ptmr');
        if(tmr)(function tick(){
            var now=new Date(),pts=PCL.split(':');
            var cl=new Date(now);cl.setHours(parseInt(pts[0]),parseInt(pts[1]),0,0);
            if(now>=cl)cl.setDate(cl.getDate()+1);
            var s=Math.max(0,Math.floor((cl-now)/1000));
            tmr.textContent='('+String(Math.floor(s/3600)).padStart(2,'0')+':'+String(Math.floor(s%3600/60)).padStart(2,'0')+':'+String(s%60).padStart(2,'0')+')';
            setTimeout(tick,1000);
        })();
    }
})();

// CHECKOUT
function doChk(){
    <?php if(!$uid): ?>alert('<?= $lang==='pt'?'Precisas de fazer login.':'Please login first.' ?>');window.location.href='login.php';return;<?php endif; ?>
    if(!cart.length){alert('<?= t('empty_cart') ?>');return;}
    if(!PO){alert('<?= $lang==='pt'?'Fora do horário de pagamento.':'Outside payment hours.' ?>');return;}
    var pe=document.querySelector('input[name="pay"]:checked');
    var de=document.querySelector('input[name="del"]:checked');
    if(!pe||!de){alert('<?= $lang==='pt'?'Selecciona pagamento e entrega.':'Select payment and delivery.' ?>');return;}
    var pay=pe.value,del=de.value,isD=del==='entrega';
    var sub=0,txt='';
    for(var i=0;i<cart.length;i++){sub+=cart[i].price*cart[i].qty;txt+=(txt?', ':'')+cart[i].name+' x'+cart[i].qty+' ('+(cart[i].price*cart[i].qty).toFixed(0)+' Kz)';}
    var tot=sub+(isD?DC:0);
    var fd=new FormData();fd.append('txt',txt);fd.append('tot',tot);fd.append('met',pay);fd.append('ent',del);
    fetch('save_order.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.ok){
            var m='<?= $lang==='pt'?'Olá! Quero fazer um pedido.':'Hello! I want to place an order.' ?>%0A';
            m+='Pedido+%23'+d.id+'%0A';
            m+='Produtos:+'+encodeURIComponent(txt)+'%0A';
            m+='Total:+'+tot.toFixed(0)+'+Kz%0A';
            m+='Pagamento:+'+encodeURIComponent(pay)+'%0A';
            m+='Entrega:+'+encodeURIComponent(isD?'<?= $lang==='pt'?'Domicílio':'Home delivery' ?>':'<?= $lang==='pt'?'Levantamento':'Pickup' ?>');
            window.open('https://wa.me/<?= $phone ?>?text='+m,'_blank');
            cart=[];saveC();renderC();closeC();
        }else{alert(d.err||'<?= $lang==='pt'?'Erro ao guardar pedido.':'Error saving order.' ?>');}
    })
    .catch(function(){alert('<?= $lang==='pt'?'Erro de ligação.':'Connection error.' ?>');});
}

// FAVOURITES
function doFav(id,btn){
    <?php if(!$uid): ?>window.location.href='login.php';return;<?php endif; ?>
    var fd=new FormData();fd.append('pid',id);
    fetch('fav.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){if(d.ok){btn.textContent=d.fav?'♥':'♡';btn.classList.toggle('on',d.fav);}});
}

// BANNER
var bi=0,bsl=document.querySelectorAll('.bsl'),bdt=document.querySelectorAll('#bdt span');
function gob(i){if(!bsl.length)return;bsl[bi].classList.remove('on');if(bdt[bi])bdt[bi].classList.remove('on');bi=(i+bsl.length)%bsl.length;bsl[bi].classList.add('on');if(bdt[bi])bdt[bi].classList.add('on');}
function mvb(d){gob(bi+d);}
if(bsl.length>1)setInterval(function(){mvb(1);},4500);

// INIT
renderC();
updD('entrega');
</script>
</body></html>
