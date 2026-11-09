<main class="page-shell auth-shell">
    <section class="auth-card">
        <span class="eyebrow">Área do cliente</span>
        <h1>Criar sua conta</h1>
        <p class="auth-description">Seus dados ficam associados apenas aos seus pedidos.</p>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo safe_text($error); ?></div>
        <?php endif; ?>

        <form method="post" action="/register/submit" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo safe_text(csrf_token()); ?>">
            <div class="field">
                <label for="name">Nome</label>
                <input id="name" name="name" type="text" autocomplete="name" maxlength="100" required>
            </div>
            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="254" required>
            </div>
            <div class="field">
                <label for="password">Senha (8 a 11 caracteres)</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="11" pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[^A-Za-z0-9])[\x21-\x7E]{8,11}" aria-describedby="password-help" required>
                <small id="password-help">Use letras maiúsculas e minúsculas e ao menos um caractere especial.</small>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirme a senha</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="11" required>
            </div>
            <button type="submit" class="btn-primary full-width">Criar conta</button>
        </form>

        <p class="auth-switch">Já tem uma conta? <a href="/login">Entrar</a></p>
    </section>
</main>
