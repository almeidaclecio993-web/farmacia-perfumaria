<?php $items = $items ?? [];
$total = $total ?? 0.0;
$pixCode = $pixCode ?? 'FARMACIA';
?>
<main class="page-shell container">
    <?php if (!empty($_SESSION['account_error'])): ?>
        <div class="alert error"><?php echo safe_text((string) $_SESSION['account_error']); ?></div>
        <?php unset($_SESSION['account_error']); ?>
    <?php endif; ?>
    <section class="section-header">
        <div>
            <span class="eyebrow">Pagamento</span>
            <h1>Concluir compra</h1>
        </div>
    </section>

    <div class="checkout-grid">
        <form method="post" action="/finalize" class="checkout-form">
            <input type="hidden" name="csrf_token" value="<?php echo safe_text(csrf_token()); ?>">

            <div class="field">
                <label>Pedido para</label>
                <p><?php echo safe_text((string) ($customer['name'] ?? '')); ?> · <?php echo safe_text((string) ($customer['email'] ?? '')); ?></p>
            </div>

            <div class="field">
                <label>Forma de pagamento</label>
                <div class="payment-methods">
                    <label><input type="radio" name="payment_method" value="pix" checked> PIX</label>
                    <label><input type="radio" name="payment_method" value="credit"> Cartão de crédito</label>
                    <label><input type="radio" name="payment_method" value="debit"> Cartão de débito</label>
                </div>
            </div>

            <div class="pix-box">
                <h3>PIX</h3>
                <p>Código copia e cola: <strong><?php echo safe_text((string) $pixCode); ?></strong></p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?php echo rawurlencode((string) $pixCode); ?>" alt="QR code PIX">
            </div>

            <button type="submit" class="btn-primary full-width">Confirmar pagamento</button>
        </form>

        <aside class="summary-box">
            <h3>Pedido</h3>
            <ul>
                <?php foreach ($items as $entry): ?>
                    <?php $product = $entry['product']; ?>
                    <li>
                        <span><?php echo safe_text((string) $product['name']); ?> x <?php echo (int) $entry['quantity']; ?></span>
                        <strong><?php echo format_money((float) $entry['subtotal']); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="summary-total">
                <span>Total</span>
                <strong><?php echo format_money((float) $total); ?></strong>
            </div>
        </aside>
    </div>
</main>
