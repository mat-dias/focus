/* Alterna a visibilidade da senha entre asteriscos e texto plano*/
/* Corrigido */ 
function toggleSenha(id, btn) {
  const input = document.getElementById(id);
  const icon = btn.querySelector('i');
  // Lógica de alternância (Ternário)
  input.type = input.type === 'password' ? 'text' : 'password';
  icon.classList.toggle('fa-eye');
  icon.classList.toggle('fa-eye-slash');
}
// Extrai o token da URL
const token = new URLSearchParams(window.location.search).get('token');

if (!token) {
  mostrarAlerta('Link inválido. Solicite uma nova recuperação de senha.', 'erro');
  document.getElementById('btnSalvar').disabled = true;
}

const inputSenha = document.getElementById('senha');
const inputConfirma = document.getElementById('confirma');
const avisoSenha = document.getElementById('aviso-senha');

/*Verifica se os dois campos são iguais*/
function verificarSenhas() {
  if (!inputConfirma.value) { avisoSenha.textContent = ''; return; }
  avisoSenha.textContent = inputSenha.value !== inputConfirma.value
    ? 'As senhas não coincidem.' : '';
}
inputSenha.addEventListener('input', verificarSenhas);
inputConfirma.addEventListener('input', verificarSenhas);

/* Evento de envio do formulário */
document.getElementById('formNovaSenha').addEventListener('submit', async function (e) {
  e.preventDefault();

  // Validações básicas no Front
  if (inputSenha.value.length < 6) {
    mostrarAlerta('A senha deve ter pelo menos 6 caracteres.', 'erro');
    return;
  }

  if (inputSenha.value !== inputConfirma.value) {
    avisoSenha.textContent = 'As senhas não coincidem.';
    return;
  }

  const btn = document.getElementById('btnSalvar');
  const form = this;
  btn.disabled = true;
  btn.textContent = 'Salvando...';

  const fd = new FormData(form);
  fd.append('token', token);

  try {
    const res = await fetch('php/nova-senha.php', { method: 'POST', body: fd });

    // Verifica se a resposta é um JSON válido antes de converter
    if (!res.ok) {
      const errorData = await res.json();
      throw new Error(errorData.mensagem || 'Erro no servidor');
    }

    const data = await res.json();
    mostrarAlerta(data.mensagem, data.sucesso ? 'sucesso' : 'erro');

    if (data.sucesso) {
      form.style.display = 'none';
      setTimeout(() => window.location.href = 'login.html', 2500);
    }
  } catch (err) {
    mostrarAlerta(err.message || 'Erro de conexão com o servidor.', 'erro');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Salvar nova senha';
  }
});
function mostrarAlerta(msg, tipo) {
  const a = document.getElementById('alerta');
  a.textContent = msg;
  a.className = tipo;
  a.style.display = 'block';
}