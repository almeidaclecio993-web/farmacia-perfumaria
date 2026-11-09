<?php $products = $products ?? [];
$categories = $categories ?? [];
$brands = $brands ?? [];
$filters = $filters ?? [];
?>
<main class="page-shell container perfumaria-page">
    <section class="section-header stacked-header">
        <div>
            <span class="eyebrow">Seção exclusiva</span>
            <h1>Perfumaria</h1>
        </div>
    </section>

    <aside class="filters-panel">
        <form method="get" action="/perfumaria" class="filter-form">
            <div class="field">
                <label for="search">Busca</label>
                <input id="search" name="search" value="<?php echo safe_text((string) ($filters['search'] ?? '')); ?>" placeholder="Buscar perfume ou marca">
            </div>

            <div class="field">
                <label for="category">Categoria</label>
                <select id="category" name="category">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo safe_text((string) $category); ?>" <?php echo (($filters['category'] ?? '') === (string) $category) ? 'selected' : ''; ?>>
                            <?php echo safe_text((string) $category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="brand">Marca</label>
                <select id="brand" name="brand">
                    <option value="">Todas</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo safe_text((string) $brand); ?>" <?php echo (($filters['brand'] ?? '') === (string) $brand) ? 'selected' : ''; ?>>
                            <?php echo safe_text((string) $brand); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="price_max">Preço máximo</label>
                <input id="price_max" name="price_max" type="number" min="0" step="0.01" value="<?php echo safe_text((string) ($filters['priceMax'] ?? '')); ?>">
            </div>

            <div class="field">
                <label for="sort">Ordenar por</label>
                <select id="sort" name="sort">
                    <option value="popularity" <?php echo (($filters['sort'] ?? '') === 'popularity' || empty($filters['sort'])) ? 'selected' : ''; ?>>Popularidade</option>
                    <option value="price_asc" <?php echo (($filters['sort'] ?? '') === 'price_asc') ? 'selected' : ''; ?>>Preço menor</option>
                    <option value="price_desc" <?php echo (($filters['sort'] ?? '') === 'price_desc') ? 'selected' : ''; ?>>Preço maior</option>
                </select>
            </div>

            <button type="submit" class="btn-primary">Filtrar</button>
        </form>
    </aside>

    <section class="perfumaria-results">
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <?php
                $price = !empty($product['promotional_price']) ? (float) $product['promotional_price'] : (float) $product['price'];
                ?>
                <article class="product-card">
                    <div class="product-image-wrap">
                        <img src="<?php echo safe_text((string) $product['image_url']); ?>" alt="<?php echo safe_text((string) $product['name']); ?>">
                        <?php if (!empty($product['promotional_price'])): ?>
                            <span class="discount-badge">Promoção</span>
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
                                <small><?php echo format_money((float) $product['price']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <a href="/cart?add=<?php echo (int) $product['id']; ?>" class="btn-primary small">Adicionar</a>
                            <a href="https://wa.me/5511999999999?text=<?php echo rawurlencode('Quero saber mais sobre ' . $product['name']); ?>" target="_blank" class="btn-secondary small">WhatsApp</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

