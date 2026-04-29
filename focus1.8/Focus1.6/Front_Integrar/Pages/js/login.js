/* Alterna a visibilidade da senha (Original) */
function toggleSenha(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (!input || !icon) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

/* Lógica de Login com Persistência */
document.getElementById('formLogin')?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn = document.getElementById('btnLogin');
    const formData = new FormData(this);
    const originalText = btn.innerHTML;

    btn.disabled = true;

    // Função que insiste na conexão sem exceção
    const realizarTentativa = async () => {
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Conectando ao banco remoto...';
        
        try {
            const response = await fetch('php/login.php', { method: 'POST', body: formData });
            const rawText = await response.text();
            const data = JSON.parse(rawText);

            if (data.sucesso) {
                mostrarAlerta(`Bem-vindo, ${data.nome}!`, 'sucesso');
                setTimeout(() => { window.location.href = data.redirect; }, 1000);
            } else {
                // Se o erro for credenciais, paramos. Se for rede, continuamos.
                if (data.mensagem.includes("incorretos") || data.mensagem.includes("campos")) {
                    mostrarAlerta(data.mensagem, 'erro');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                } else {
                    // Erro de rede/banco: aguarda 2s e tenta de novo automaticamente
                    console.warn("Banco não respondeu. Re-tentando...");
                    setTimeout(realizarTentativa, 2000);
                }
            }
        } catch (error) {
            // Falha crítica de rede: insiste novamente
            console.error("Falha na comunicação. Nova tentativa em 2s...");
            setTimeout(realizarTentativa, 2000);
        }
    };

    realizarTentativa();
});

/* Função de Feedback Visual (Original) */
function mostrarAlerta(msg, tipo) {
    const alerta = document.getElementById('alerta');
    if (!alerta) return;
    alerta.textContent = msg;
    alerta.className = `alerta ${tipo}`; 
    alerta.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}