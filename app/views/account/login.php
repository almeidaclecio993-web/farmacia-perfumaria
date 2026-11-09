<main class="page-shell auth-shell">
    <section class="auth-card">
        <span class="eyebrow">Área do cliente</span>
        <h1>Entre na sua conta</h1>
        <p class="auth-description">Acesse seus pedidos e conclua suas compras com segurança.</p>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo safe_text($error); ?></div>
        <?php endif; ?>

        <form method="post" action="/login/submit" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo safe_text(csrf_token()); ?>">
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="254" required>
            </div>
            <div class="field">
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" autocomplete="current-password" maxlength="200" required>
            </div>
            <button type="submit" class="btn-primary full-width">Entrar</button>
        </form>

        <?php if (!empty($googleEnabled)): ?>
            <div class="auth-divider"><span>ou</span></div>
            <a href="/auth/google" class="google-button">
                <span aria-hidden="true">G</span>
                Continuar com Google
            </a>
        <?php else: ?>
            <p class="google-setup-note">O login com Google ficará disponível após a configuração segura das credenciais OAuth.</p>
        <?php endif; ?>

        <p class="auth-switch">Ainda não tem conta? <a href="/register">Criar conta</a></p>
    </section>
</main>
