<?php
$customer = $customer ?? [];
$orders = $orders ?? [];
?>
<main class="page-shell container account-page">
    <section class="account-heading">
        <div>
            <span class="eyebrow">Área do cliente</span>
            <h1>Olá, <?php echo safe_text((string) ($customer['name'] ?? 'cliente')); ?></h1>
            <p><?php echo safe_text((string) ($customer['email'] ?? '')); ?></p>
        </div>
        <form method="post" action="/account/logout">
            <input type="hidden" name="csrf_token" value="<?php echo safe_text(csrf_token()); ?>">
            <button class="btn-secondary" type="submit">Sair da conta</button>
        </form>
    </section>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?php echo safe_text($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['account_success'])): ?>
        <div class="alert success"><?php echo safe_text((string) $_SESSION['account_success']); ?></div>
        <?php unset($_SESSION['account_success']); ?>
    <?php endif; ?>

    <section class="account-security-card">
        <div>
            <h2>Login com Google</h2>
            <p><?php echo !empty($googleLinked) ? 'Sua conta Google está vinculada.' : 'Vincule uma conta Google com o mesmo e-mail deste perfil.'; ?></p>
        </div>
        <?php if (!empty($googleLinked)): ?>
            <span class="linked-status">Vinculada</span>
        <?php elseif (!empty($googleEnabled)): ?>
            <a href="/auth/google/link" class="google-button">
                <span aria-hidden="true">G</span>
                Vincular Google
            </a>
        <?php else: ?>
            <span class="google-setup-note">Disponível após configurar o OAuth do Google.</span>
        <?php endif; ?>
    </section>

    <section class="account-orders">
        <div class="section-header">
            <div>
                <span class="eyebrow">Histórico privado</span>
                <h2>Meus pedidos</h2>
            </div>
            <a href="/perfumaria">Continuar comprando</a>
        </div>

        <?php if ($orders === []): ?>
            <div class="empty-state">
                <h3>Você ainda não fez pedidos.</h3>
                <a href="/" class="btn-primary">Explorar produtos</a>
            </div>
        <?php else: ?>
            <div class="account-order-list">
                <?php foreach ($orders as $order): ?>
                    <article class="account-order-card">
                        <div class="account-order-heading">
                            <div>
                                <span>Pedido</span>
                                <strong><?php echo safe_text((string) $order['order_number']); ?></strong>
                            </div>
                            <div>
                                <span>Data</span>
                                <strong><?php echo safe_text(date('d/m/Y H:i', strtotime((string) $order['created_at']))); ?></strong>
                            </div>
                            <div>
                                <span>Status</span>
                                <strong><?php echo safe_text((string) $order['status']); ?></strong>
                            </div>
                            <div>
                                <span>Total</span>
                                <strong><?php echo format_money((float) $order['total']); ?></strong>
                            </div>
                        </div>
                        <ul>
                            <?php foreach ($order['items'] as $item): ?>
                                <li>
                                    <span><?php echo safe_text((string) ($item['product_name'] ?? 'Produto')); ?> × <?php echo (int) $item['quantity']; ?></span>
                                    <strong><?php echo format_money((float) $item['subtotal']); ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
