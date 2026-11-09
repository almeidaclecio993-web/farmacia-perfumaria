document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('chat-toggle');
    const widget = document.getElementById('chat-widget');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const body = document.getElementById('chat-body');

    if (toggle && widget) {
        toggle.addEventListener('click', () => {
            widget.classList.toggle('open');
        });
    }

    if (form && input && body) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const message = input.value.trim();

            if (!message) {
                return;
            }

            body.insertAdjacentHTML('beforeend', '<div class="message user">' + escapeHtml(message) + '</div>');
            input.value = '';
            body.scrollTop = body.scrollHeight;

            try {
                const response = await fetch('/api/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    body: new URLSearchParams({ message }).toString(),
                });

                const data = await response.json();
                body.insertAdjacentHTML('beforeend', '<div class="message bot">' + escapeHtml(data.reply || 'Posso te ajudar!') + '</div>');
                body.scrollTop = body.scrollHeight;
            } catch (error) {
                body.insertAdjacentHTML('beforeend', '<div class="message bot">Não consegui responder agora. Tente novamente.</div>');
                body.scrollTop = body.scrollHeight;
            }
        });
    }
});

function escapeHtml(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
