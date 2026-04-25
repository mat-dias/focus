/* Alternar visualização da senha */
function toggleSenha(id, btn) { //corrigido
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (!input || !icon) return;
    
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById('formCadastro');
    const btnEnviar = document.getElementById('btnEnviar');
    const alerta = document.getElementById('alerta');

    /* LÓGICA DA FOTO DE PERFIL */
    const inputFoto = document.getElementById('foto');
    const fotoWrapper = document.getElementById('fotoWrapper');
    const fotoPreview = document.getElementById('fotoPreview');
    const fotoPlaceholder = document.getElementById('fotoPlaceholder');

    fotoWrapper?.addEventListener('click', () => inputFoto.click());

    inputFoto?.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                fotoPreview.src = e.target.result;
                fotoPreview.style.display = 'block';
                if (fotoPlaceholder) fotoPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    /* ENVIO DO FORMULÁRIO */
    form?.addEventListener('submit', async function (e) {
        e.preventDefault();

        btnEnviar.disabled = true;
        const originalText = btnEnviar.textContent;
        btnEnviar.textContent = 'Processando...';
        if (alerta) alerta.style.display = 'none';

        try {
            const formData = new FormData(this);
            const response = await fetch('php/cadastro.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.sucesso) {
                alerta.className = 'alerta sucesso';
                alerta.textContent = result.mensagem;
                alerta.style.display = 'block';
                form.reset();
                // Redireciona após 2 segundos
                setTimeout(() => window.location.href = 'login.html', 2000);
            } else {
                throw new Error(result.mensagem);
            }

        } catch (error) {
            alerta.className = 'alerta erro';
            alerta.textContent = error.message || "Erro ao conectar com o servidor.";
            alerta.style.display = 'block';
        } finally {
            btnEnviar.disabled = false;
            btnEnviar.textContent = originalText;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
});