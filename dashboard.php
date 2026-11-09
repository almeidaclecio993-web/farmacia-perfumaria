<?php $products = $products ?? [];
$totalProducts = $totalProducts ?? 0;
$stockTotal = $stockTotal ?? 0;
$promotions = $promotions ?? [];
$perfumes = $perfumes ?? [];
?>
<main class="page-shell admin-shell container">
    <div class="admin-header">
        <div>
            <span class="eyebrow">Área restrita</span>
            <h1>Painel administrativo</h1>
        </div>
        <form method="post" action="/admin/logout">
            <input type="hidden" name="csrf_token" value="<?php echo safe_text(csrf_token()); ?>">
            <button type="submit" class="btn-secondary">Sair</button>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span>Total de produtos</span>
            <strong><?php echo (int) $totalProducts; ?></strong>
        </div>
        <div class="stat-card">
            <span>Itens em estoque</span>
            <strong><?php echo (int) $stockTotal; ?></strong>
        </div>
        <div class="stat-card">
            <span>Promoções ativas</span>
            <strong><?php echo count($promotions); ?></strong>
        </div>
        <div class="stat-card">
            <span>Perfumaria</span>
            <strong><?php echo count($perfumes); ?></strong>
        </div>
    </div>

    <div class="admin-layout">
        <section class="admin-panel">
            <h2>Cadastro de produtos</h2>
            <form method="post" action="/admin/products/save" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo safe_text(csrf_token()); ?>">

                <div class="grid-two">
                    <div class="field">
                        <label for="name">Nome</label>
                        <input id="name" name="name" type="text" required>
                    </div>
                    <div class="field">
                        <label for="brand">Marca</label>
                        <input id="brand" name="brand" type="text" required>
                    </div>
                </div>

                <div class="grid-two">
                    <div class="field">
                        <label for="category">Categoria</label>
                        <input id="category" name="category" type="text" required>
                    </div>
                    <div class="field">
                        <label for="stock">Estoque</label>
                        <input id="stock" name="stock" type="number" min="0" value="0" required>
                    </div>
                </div>

                <div class="grid-two">
                    <div class="field">
                        <label for="price">Preço</label>
                        <input id="price" name="price" type="number" step="0.01" min="0" required>
                    </div>
                    <div class="field">
                        <label for="promotional_price">Preço promocional</label>
                        <input id="promotional_price" name="promotional_price" type="number" step="0.01" min="0">
                    </div>
                </div>

                <div class="grid-two">
                    <div class="field">
                        <label for="popularity">Popularidade</label>
                        <input id="popularity" name="popularity" type="number" min="0" step="1" value="0">
                    </div>
                    <div class="field checkbox-row">
                        <label><input type="checkbox" name="featured" value="1"> Destaque</label>
                        <label><input type="checkbox" name="is_perfume" value="1"> Perfumaria</label>
                    </div>
                </div>

                <div class="field">
                    <label for="image_url">Imagem (URL)</label>
                    <input id="image_url" name="image_url" type="url" placeholder="https://...">
                </div>

                <div class="field">
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>

                <button type="submit" class="btn-primary">Salvar produto</button>
            </form>
        </section>

        <section class="admin-panel">
            <h2>Produtos cadastrados</h2>
            <div class="admin-product-list">
                <?php foreach ($products as $product): ?>
                    <article class="admin-product-item">
                        <div class="info">
                            <strong><?php echo safe_text((string) $product['name']); ?></strong>
                            <span><?php echo safe_text((string) $product['category']); ?></span>
                        </div>
                        <div class="actions">
                            <a href="/admin/products/edit?id=<?php echo (int) $product['id']; ?>" class="btn-secondary small">Editar</a>
                            <form method="post" action="/admin/products/delete" onsubmit="return confirm('Excluir produto?')">
                                <input type="hidden" name="csrf_token" value="<?php echo safe_text(csrf_token()); ?>">
                                <input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>">
                                <button type="submit" class="danger-link">Excluir</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>
