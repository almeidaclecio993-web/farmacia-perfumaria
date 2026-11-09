<main class="page-shell auth-shell">
    <div class="auth-card">
        <span class="eyebrow">Painel administrativo</span>
        <h1>Login</h1>

        <?php if (!empty($_SESSION['admin_error'])): ?>
            <div class="alert error"><?php echo safe_text((string) $_SESSION['admin_error']); ?></div>
            <?php unset($_SESSION['admin_error']); ?>
        <?php endif; ?>

        <form method="post" action="/admin/auth" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo safe_text(csrf_token()); ?>">

            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" required>
            </div>

            <div class="field">
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" required>
            </div>

            <button type="submit" class="btn-primary full-width">Entrar</button>
        </form>
    </div>
</main>
