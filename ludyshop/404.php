<?php require_once 'lang.php'; http_response_code(404); ?>
<!DOCTYPE html><html lang="<?= $lang ?>"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>404 — <?= t('shop') ?></title><link rel="stylesheet" href="style.css">
<style>body{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:20px}.n{font-size:88px;font-weight:900;color:var(--or);line-height:1}.l{width:68px;height:68px;background:var(--or);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;color:#fff;margin:0 auto 20px}</style>
</head><body>
<div class="l">LTS</div>
<div class="n">404</div>
<h2 style="margin:12px 0 8px"><?= $lang==='pt'?'Página não encontrada':'Page not found' ?></h2>
<p style="color:var(--muted);font-size:14px;max-width:280px"><?= $lang==='pt'?'A página que procuras não existe.':'The page you are looking for does not exist.' ?></p>
<a class="btn-p" style="margin-top:24px;display:inline-block" href="index.php"><?= t('back') ?></a>
</body></html>
