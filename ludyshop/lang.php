<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pt','en'])) $_SESSION['lang'] = $_GET['lang'];
$lang = $_SESSION['lang'] ?? 'pt';
$T = [
  'pt' => [
    'shop'=>'Ludy Tech Shop','login'=>'Entrar','logout'=>'Sair','register'=>'Registar',
    'profile'=>'Perfil','cart'=>'Carrinho','search'=>'Pesquisar produtos...','all'=>'Todos',
    'add'=>'Adicionar','checkout'=>'Finalizar via WhatsApp','total'=>'Total','subtotal'=>'Subtotal',
    'stock_low'=>'Apenas {n} em stock!','esgotado'=>'Esgotado','promo'=>'PROMOÇÃO',
    'related'=>'Produtos relacionados','reviews'=>'Avaliações','no_reviews'=>'Sem avaliações ainda.',
    'favorites'=>'Favoritos','orders'=>'Os meus pedidos','delivery'=>'Entrega ao domicílio',
    'pickup'=>'Levantamento na loja','pay_method'=>'Método de pagamento','pay_cash'=>'Dinheiro vivo',
    'pay_express'=>'Multicaixa Express','pay_closed'=>'Pagamentos encerrados. Abre às',
    'rights'=>'Todos os direitos reservados.','address'=>'Uíge, Bairro Dunga, Rua B',
    'save'=>'Guardar','cancel'=>'Cancelar','delete'=>'Apagar','edit'=>'Editar',
    'back'=>'Voltar à loja','no_orders'=>'Ainda não fizeste nenhum pedido.',
    'no_favs'=>'Ainda não tens favoritos.','details'=>'Ver detalhes','call'=>'Ligar',
    'notifs'=>'Notificações','no_notifs'=>'Sem notificações.','categories'=>'Categorias',
    'products'=>'Produtos','empty_cart'=>'O carrinho está vazio.',
    'del_fee'=>'Taxa de entrega','del_warn'=>'As entregas podem não estar disponíveis para todos os bairros do Uíge. Confirmaremos após o pedido.',
    'total_spent'=>'Total gasto','num_orders'=>'Pedidos feitos','change_pass'=>'Alterar palavra-passe',
  ],
  'en' => [
    'shop'=>'Ludy Tech Shop','login'=>'Login','logout'=>'Logout','register'=>'Register',
    'profile'=>'Profile','cart'=>'Cart','search'=>'Search products...','all'=>'All',
    'add'=>'Add to cart','checkout'=>'Checkout via WhatsApp','total'=>'Total','subtotal'=>'Subtotal',
    'stock_low'=>'Only {n} left!','esgotado'=>'Out of stock','promo'=>'SALE',
    'related'=>'Related products','reviews'=>'Reviews','no_reviews'=>'No reviews yet.',
    'favorites'=>'Wishlist','orders'=>'My orders','delivery'=>'Home delivery',
    'pickup'=>'Store pickup','pay_method'=>'Payment method','pay_cash'=>'Cash',
    'pay_express'=>'Multicaixa Express','pay_closed'=>'Payments closed. Opens at',
    'rights'=>'All rights reserved.','address'=>'Uíge, Dunga District, Street B',
    'save'=>'Save','cancel'=>'Cancel','delete'=>'Delete','edit'=>'Edit',
    'back'=>'Back to shop','no_orders'=>'You have no orders yet.',
    'no_favs'=>'No favourites yet.','details'=>'View details','call'=>'Call',
    'notifs'=>'Notifications','no_notifs'=>'No notifications.','categories'=>'Categories',
    'products'=>'Products','empty_cart'=>'Cart is empty.',
    'del_fee'=>'Delivery fee','del_warn'=>'Deliveries may not be available for all districts of Uíge. We will confirm after the order.',
    'total_spent'=>'Total spent','num_orders'=>'Orders placed','change_pass'=>'Change password',
  ],
];
function t($k,$v=[]) {
    global $T,$lang;
    $s = $T[$lang][$k] ?? $T['pt'][$k] ?? $k;
    foreach ($v as $kk=>$vv) $s = str_replace('{'.$kk.'}',$vv,$s);
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function tr($k,$v=[]) {
    global $T,$lang;
    $s = $T[$lang][$k] ?? $T['pt'][$k] ?? $k;
    foreach ($v as $kk=>$vv) $s = str_replace('{'.$kk.'}',$vv,$s);
    return $s;
}
?>
