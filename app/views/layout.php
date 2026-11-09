<?php /** @var string $contentView */
$appConfig = app_config();
$cartCount = array_sum(current_cart());
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo safe_text($pageTitle ?? $appConfig['app_name']); ?></title>
    <meta name="description" content="Vitrine digital da farmácia e perfumaria com catálogo completo, carrinho e WhatsApp.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
</head>
<body>
    <header class="site-header">
        <div class="container nav-wrap">
            <a href="/" class="brand" aria-label="Página inicial">
                <span class="brand-mark">F</span>
                <span><?php echo safe_text($appConfig['app_name']); ?></span>
            </a>

            <nav class="main-nav" aria-label="Menu principal">
                <a href="/">Início</a>
                <a href="/perfumaria">Perfumaria</a>
                <a href="#destaques">Destaques</a>
                <a href="#ofertas">Ofertas</a>
                <a href="/cart">Carrinho</a>
                <a href="<?php echo current_customer() !== null ? '/account' : '/login'; ?>">
                    <?php echo current_customer() !== null ? 'Minha conta' : 'Entrar'; ?>
                </a>
            </nav>

            <div class="header-tools">
                <form class="compact-search" action="/perfumaria" method="get">
                    <input type="search" name="search" value="<?php echo safe_text($_GET['search'] ?? ''); ?>" placeholder="Buscar produtos">
                    <button type="submit">Buscar</button>
                </form>
                <a class="cart-link" href="/cart" aria-label="Abrir carrinho">
                    Carrinho <span><?php echo (int) $cartCount; ?></span>
                </a>
                <?php if (current_customer() !== null): ?>
                    <a class="account-shortcut" href="/account">Minha conta</a>
                <?php else: ?>
                    <a class="account-shortcut" href="/login">Entrar</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php require $contentView; ?>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <h3>Farmácia e Perfumaria</h3>
                <p>Atendimento especializado, soluções para beleza, saúde e bem-estar.</p>
            </div>
            <div>
                <h4>Atendimento</h4>
                <ul>
                    <li>WhatsApp: (11) 99999-9999</li>
                    <li>Seg - Sáb: 08h às 20h</li>
                    <li>Rua das Flores, 125, Centro</li>
                </ul>
            </div>
            <div>
                <h4>Navegação</h4>
                <ul>
                    <li><a href="/">Início</a></li>
                    <li><a href="/perfumaria">Perfumaria</a></li>
                    <li><a href="/admin/login">Admin</a></li>
                </ul>
            </div>
        </div>
        <div class="bottom-bar">© <?php echo date('Y'); ?> Farmácia e Perfumaria. Todos os direitos reservados.</div>
    </footer>

    <div class="chat-widget" id="chat-widget">
        <button class="chat-toggle" id="chat-toggle" type="button" aria-label="Abrir chat">💬</button>
        <div class="chat-box" id="chat-box">
            <div class="chat-header">
                <strong>Agente virtual</strong>
                <span>online</span>
            </div>
            <div class="chat-body" id="chat-body">
                <div class="message bot">Olá! Posso te ajudar com produtos, preços e perfumaria.</div>
            </div>
            <form id="chat-form" class="chat-form">
                <input id="chat-input" type="text" placeholder="Pergunte sobre um produto" aria-label="Mensagem do agente virtual">
                <button type="submit">Enviar</button>
            </form>
        </div>
    </div>

    <script src="<?php echo asset_url('js/app.js'); ?>"></script>
</body>
</html>
