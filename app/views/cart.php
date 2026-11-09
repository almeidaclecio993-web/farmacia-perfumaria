<?php $items = $items ?? [];
$total = $total ?? 0.0;
?>
<main class="page-shell container">
    <section class="section-header">
        <div>
            <span class="eyebrow">Pedido</span>
            <h1>Carrinho de compras</h1>
        </div>
    </section>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert success">
            Pedido registrado com sucesso! A confirmação do pagamento foi enviada para processamento.
        </div>
    <?php endif; ?>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <h2>Seu carrinho está vazio.</h2>
            <a href="/" class="btn-primary">Explorar produtos</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <form method="post" action="/cart" class="cart-items">
                <input type="hidden" name="update_cart" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo safe_text(csrf_token()); ?>">
                <?php foreach ($items as $entry): ?>
                    <?php $product = $entry['product']; ?>
                    <article class="cart-item">
                        <img src="<?php echo safe_text((string) $product['image_url']); ?>" alt="<?php echo safe_text((string) $product['name']); ?>">
                        <div class="cart-item-info">
                            <h3><?php echo safe_text((string) $product['name']); ?></h3>
                            <p><?php echo safe_text((string) $product['brand']); ?> · <?php echo safe_text((string) $product['category']); ?></p>
                            <div class="cart-price-row">
                                <span><?php echo format_money((float) $entry['price']); ?></span>
                                <strong><?php echo format_money((float) $entry['subtotal']); ?></strong>
                            </div>
                        </div>
                        <div class="qty-box">
                            <label for="qty-<?php echo (int) $product['id']; ?>">Qtd</label>
                            <input id="qty-<?php echo (int) $product['id']; ?>" type="number" min="1" name="qty[<?php echo (int) $product['id']; ?>]" value="<?php echo (int) $entry['quantity']; ?>">
                        </div>
                        <a href="/cart?remove=<?php echo (int) $product['id']; ?>" class="remove-link">Excluir</a>
                    </article>
                <?php endforeach; ?>
                <button type="submit" class="btn-primary">Atualizar carrinho</button>
            </form>

            <aside class="summary-box">
                <h3>Resumo do pedido</h3>
                <ul>
                    <?php foreach ($items as $entry): ?>
                        <li>
                            <span><?php echo safe_text((string) $entry['product']['name']); ?> x <?php echo (int) $entry['quantity']; ?></span>
                            <strong><?php echo format_money((float) $entry['subtotal']); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="summary-total">
                    <span>Total</span>
                    <strong><?php echo format_money((float) $total); ?></strong>
                </div>
                <a href="/checkout" class="btn-primary full-width">Finalizar pedido</a>
                <a href="https://wa.me/5511999999999?text=<?php echo rawurlencode('Quero finalizar minha compra com os itens selecionados'); ?>" target="_blank" class="btn-secondary full-width">Finalizar pelo WhatsApp</a>
            </aside>
        </div>
    <?php endif; ?>
</main>

