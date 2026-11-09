<?php $topProducts = $products ?? [];
$featured = $featured ?? [];
$offers = $offers ?? [];
$bestSellers = $bestSellers ?? [];
$categories = $categories ?? [];
?>
<main class="page-shell">
    <section class="hero-section container">
        <div class="hero-copy">
            <span class="eyebrow">Farmácia + perfumaria</span>
            <h1>Seu cuidado, beleza e bem-estar em um só lugar.</h1>
            <p>Descubra uma loja digital moderna, segura e completa, com produtos de qualidade, ofertas exclusivas e atendimento rápido pelo WhatsApp.</p>
            <div class="cta-row">
                <a href="#destaques" class="btn-primary">Conhecer produtos</a>
                <a href="https://wa.me/5511999999999?text=Ol%C3%A1%2C%20gostaria%20de%20consultar%20meus%20produtos" target="_blank" rel="noreferrer" class="btn-secondary">Atendimento WhatsApp</a>
            </div>
        </div>
        <div class="hero-card">
            <div class="mini-tag">Oferta da semana</div>
            <img src="https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=900&q=80" alt="Oferta da semana">
            <div class="hero-card-meta">
                <strong>Kit de skincare</strong>
                <span>até 30% off</span>
            </div>
        </div>
    </section>

    <section class="categories-strip container" aria-label="Categorias">
        <?php foreach ($categories as $category): ?>
            <a href="/perfumaria?category=<?php echo rawurlencode((string) $category); ?>" class="category-pill">
                <?php echo safe_text((string) $category); ?>
            </a>
        <?php endforeach; ?>
    </section>

    <section id="destaques" class="container section-block">
        <div class="section-header">
            <div>
                <span class="eyebrow">Produtos</span>
                <h2>Em destaque</h2>
            </div>
            <a href="/perfumaria">Ver tudo</a>
        </div>

        <div class="product-grid">
            <?php foreach (array_slice($featured, 0, 4) as $product): ?>
                <?php
                $price = !empty($product['promotional_price']) ? (float) $product['promotional_price'] : (float) $product['price'];
                $originalPrice = (float) $product['price'];
                ?>
                <article class="product-card">
                    <div class="product-image-wrap">
                        <img src="<?php echo safe_text((string) $product['image_url']); ?>" alt="<?php echo safe_text((string) $product['name']); ?>">
                        <?php if (!empty($product['promotional_price'])): ?>
                            <span class="discount-badge">Promo</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-body">
                        <div class="meta-row">
                            <span class="brand-tag"><?php echo safe_text((string) $product['brand']); ?></span>
                            <span class="stock-tag"><?php echo (int) $product['stock']; ?> em estoque</span>
                        </div>
                        <h3><?php echo safe_text((string) $product['name']); ?></h3>
                        <p><?php echo safe_text((string) $product['description']); ?></p>
                        <div class="price-row">
                            <strong><?php echo format_money($price); ?></strong>
                            <?php if (!empty($product['promotional_price'])): ?>
                                <small><?php echo format_money($originalPrice); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <a href="/cart?add=<?php echo (int) $product['id']; ?>" class="btn-primary small">Adicionar</a>
                            <a href="https://wa.me/5511999999999?text=<?php echo rawurlencode('Quero comprar ' . $product['name']); ?>" target="_blank" class="btn-secondary small">WhatsApp</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="ofertas" class="container section-block">
        <div class="section-header">
            <div>
                <span class="eyebrow">Especial</span>
                <h2>Ofertas da semana</h2>
            </div>
        </div>
        <div class="offer-banner">
            <div>
                <span class="eyebrow">Até 40% off</span>
                <h3>Confira a seleção da perfumaria com fragrâncias premium.</h3>
            </div>
            <a href="/perfumaria" class="btn-primary">Ver ofertas</a>
        </div>
    </section>

    <section class="container section-block">
        <div class="section-header">
            <div>
                <span class="eyebrow">Mais vendidos</span>
                <h2>Melhores escolhas</h2>
            </div>
        </div>
        <div class="product-grid compact-grid">
            <?php foreach (array_slice($bestSellers, 0, 4) as $product): ?>
                <article class="product-card small-card">
                    <img src="<?php echo safe_text((string) $product['image_url']); ?>" alt="<?php echo safe_text((string) $product['name']); ?>">
                    <div class="product-body">
                        <h3><?php echo safe_text((string) $product['name']); ?></h3>
                        <div class="price-row">
                            <strong><?php echo format_money((float) ($product['promotional_price'] ?? $product['price'])); ?></strong>
                        </div>
                        <a href="/cart?add=<?php echo (int) $product['id']; ?>" class="btn-primary small">Adicionar</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

