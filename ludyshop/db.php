<?php
if (session_status() === PHP_SESSION_NONE) session_start();

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'ludyshop');
define('ADMIN_EMAIL','ludymouzinho@gmail.com');
define('ADMIN_PASS', 'angola2008**949148512');
define('PHONE',      '244949148512');
define('PAY_OPEN',   '08:00');
define('PAY_CLOSE',  '16:00');
define('PAY_CLOSE_WE','14:00');
define('DEL_COST',   600);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');
if ($conn->connect_error) die('Erro BD: ' . $conn->connect_error);

// Tabelas
$conn->query("CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS produtos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(150) NOT NULL,
    descricao    TEXT,
    preco        DECIMAL(10,2) NOT NULL DEFAULT 0,
    preco_promo  DECIMAL(10,2) DEFAULT NULL,
    em_promocao  TINYINT(1) DEFAULT 0,
    stock        INT DEFAULT 0,
    imagem       LONGBLOB,
    imagem_tipo  VARCHAR(30) DEFAULT 'image/jpeg',
    categoria_id INT DEFAULT NULL,
    criado_em    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS utilizadores (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(120) NOT NULL,
    username      VARCHAR(60)  NOT NULL UNIQUE,
    email         VARCHAR(120) DEFAULT NULL,
    telefone      VARCHAR(20)  NOT NULL UNIQUE,
    morada        TEXT NOT NULL,
    genero        ENUM('Masculino','Feminino','Outro') NOT NULL DEFAULT 'Outro',
    idade         TINYINT UNSIGNED NOT NULL DEFAULT 18,
    foto          LONGBLOB,
    foto_tipo     VARCHAR(30) DEFAULT 'image/jpeg',
    senha         VARCHAR(255) NOT NULL,
    criado_em     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS pedidos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    produtos_txt TEXT NOT NULL,
    total        DECIMAL(10,2) NOT NULL DEFAULT 0,
    metodo_pag   VARCHAR(60) NOT NULL,
    tipo_entrega ENUM('entrega','levantamento') NOT NULL DEFAULT 'levantamento',
    estado       ENUM('Pendente','Confirmado','Em entrega','Entregue','Cancelado') DEFAULT 'Pendente',
    criado_em    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS avaliacoes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    produto_id INT NOT NULL,
    estrelas   TINYINT NOT NULL DEFAULT 5,
    comentario TEXT,
    criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_av (user_id, produto_id),
    FOREIGN KEY (user_id)    REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)    ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS favoritos (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    produto_id INT NOT NULL,
    UNIQUE KEY uq_fav (user_id, produto_id),
    FOREIGN KEY (user_id)    REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)    ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS notificacoes (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    mensagem  TEXT NOT NULL,
    lida      TINYINT(1) DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS banners (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    titulo    VARCHAR(120) NOT NULL,
    subtitulo VARCHAR(200) DEFAULT '',
    link      VARCHAR(255) DEFAULT '#',
    ativo     TINYINT(1) DEFAULT 1
) ENGINE=InnoDB CHARSET=utf8mb4");

// Dados iniciais
$np = $conn->query("SELECT COUNT(*) c FROM produtos")->fetch_assoc()['c'];
if ($np == 0) {
    $conn->query("INSERT IGNORE INTO categorias (nome) VALUES ('Acessório'),('Electrónico'),('Roupa'),('Calçado')");
    $ac = (int)$conn->query("SELECT id FROM categorias WHERE nome='Acessório' LIMIT 1")->fetch_assoc()['id'];
    $el = (int)$conn->query("SELECT id FROM categorias WHERE nome='Electrónico' LIMIT 1")->fetch_assoc()['id'];
    $prods = [
        ['Luvas Gamers',          'Nano fibra importada, leves e finas.',       76.00,  40, $el],
        ['Conectores RJ45',       'Banhados a ouro, pack de 10 unidades.',      12.68, 100, $el],
        ['Pulseira Casal Lunoza', 'Pulseira luminosa com estrela.',           1233.00,   4, $ac],
        ['Brincos de Prata 925',  'Brincos coração com corrente prata 925.',   668.00,   3, $ac],
        ['Fio de Prata 925 50cm', 'Fio tipo cobra 50cm prata 925.',          1818.00,   2, $ac],
        ['KIT M 2PCS',            'Kit acessórios M, 2 peças.',              2540.00,   1, $ac],
    ];
    $st = $conn->prepare("INSERT INTO produtos (nome,descricao,preco,stock,categoria_id) VALUES (?,?,?,?,?)");
    foreach ($prods as $p) {
        $st->bind_param("ssdii", $p[0], $p[1], $p[2], $p[3], $p[4]);
        $st->execute();
    }
    $st->close();
    $conn->query("INSERT INTO banners (titulo,subtitulo,link) VALUES
        ('Bem-vindo à Ludy Tech Shop! 🔥','Os melhores produtos em Uíge, Angola.','#prods'),
        ('Novas chegadas 🎉','Fique atento às nossas promoções exclusivas.','#prods')");
}

// Utilizador inicial
$nu = $conn->query("SELECT COUNT(*) c FROM utilizadores WHERE telefone='949148512'")->fetch_assoc()['c'];
if ($nu == 0) {
    $h = password_hash('angola2008**949148512', PASSWORD_DEFAULT);
    $st = $conn->prepare("INSERT INTO utilizadores (nome_completo,username,email,telefone,morada,genero,idade,senha) VALUES (?,?,?,?,?,?,?,?)");
    $nome='Ludy Mouzinho'; $user='ludymouzinho'; $email='ludymouzinho@gmail.com';
    $tel='949148512'; $mor='Uíge, Bairro Dunga, Rua B'; $gen='Masculino'; $idade=18;
    $st->bind_param("ssssssis", $nome,$user,$email,$tel,$mor,$gen,$idade,$h);
    $st->execute(); $st->close();
}

// Helpers
function isAdmin()      { return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true; }
function requireAdmin() { if (!isAdmin()) { header('Location: login.php'); exit; } }
function requireLogin() { if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; } }

function readImg($field) {
    if (empty($_FILES[$field]['tmp_name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return [null,'image/jpeg'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
    if (!isset($map[$ext])) return [null,'image/jpeg'];
    return [file_get_contents($_FILES[$field]['tmp_name']), $map[$ext]];
}

function payOpen() {
    $tz   = new DateTimeZone('Africa/Luanda');
    $now  = new DateTime('now', $tz);
    $dow  = (int)$now->format('N');
    $time = $now->format('H:i');
    $we   = ($dow >= 6);
    $cl   = $we ? PAY_CLOSE_WE : PAY_CLOSE;
    return ['open' => ($time >= PAY_OPEN && $time < $cl), 'close' => $cl, 'open_at' => PAY_OPEN];
}

function notif($conn, $uid, $msg) {
    $st = $conn->prepare("INSERT INTO notificacoes (user_id,mensagem) VALUES (?,?)");
    $st->bind_param("is", $uid, $msg);
    $st->execute(); $st->close();
}
?>
