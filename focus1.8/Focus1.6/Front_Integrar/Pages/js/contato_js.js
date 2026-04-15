/* PRIORIDADE */
/* corrigido */ 
const prioButtons = document.querySelectorAll('.priority-btn');
const prioInput = document.getElementById('prioridade');

prioButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    prioButtons.forEach(b => b.classList.remove('active-baixa', 'active-media', 'active-alta'));
    const p = btn.dataset.prio;
    btn.classList.add(`active-${p}`);
    prioInput.value = p;
  });
});

/* CONTADOR DE CARACTERES */
const textarea = document.getElementById('mensagem');
const charCount = document.getElementById('charCount');

if (textarea) {
  textarea.addEventListener('input', () => {
    const len = textarea.value.length;
    charCount.textContent = `${len} / 2000`;
    charCount.className = 'char-count' + (len > 1800 ? ' warn' : '') + (len >= 2000 ? ' over' : '');
  });
}

/* FAQ ACCORDION */
document.querySelectorAll('.faq-question').forEach(btn => {
  btn.addEventListener('click', () => {
    const item = btn.closest('.faq-item');
    const wasOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
    if (!wasOpen) item.classList.add('open');
  });
});

/* LOGICA DO PAINEL ADMIN */
function abrirModalLogin() {
  document.getElementById('modalSuporte').style.display = 'flex';
}

function fecharModal() {
  document.getElementById('modalSuporte').style.display = 'none';
  const erroDiv = document.getElementById('erroLogin');
  if(erroDiv) erroDiv.style.display = 'none';
}

// Evento de Login para o Admin
document.getElementById('formLoginAdmin').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = e.submitter;
  const erroDiv = document.getElementById('erroLogin');
  
  btn.disabled = true;
  const originalText = btn.innerHTML;
  btn.innerHTML = 'Verificando...';

  try {
    const res = await fetch('php/contato.php?acao=login', { 
      method: 'POST', 
      body: new FormData(this) 
    });

    const text = await res.text();
    // Limpa HTML ou erro que o PHP possa ter enviado antes do JSON
    const cleanJson = text.substring(text.indexOf('{'), text.lastIndexOf('}') + 1);
    const data = JSON.parse(cleanJson);

    if (data.sucesso && data.role === 'admin') {
      window.location.href = 'admin_painel.html'; 
    } else {
      erroDiv.textContent = data.mensagem || 'Acesso negado.';
      erroDiv.style.display = 'block';
    }
  } catch (err) {
    console.error("Erro no Login:", err);
    erroDiv.textContent = 'Erro de comunicação com o servidor.';
    erroDiv.style.display = 'block';
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

/* ENVIO DO FORMULÁRIO DE CONTATO */
document.getElementById('formContato').addEventListener('submit', async function (e) {
  e.preventDefault();
  const btn = document.getElementById('btnEnviar');
  btn.disabled = true;

  try {
    const res = await fetch('php/contato.php', { method: 'POST', body: new FormData(this) });
    const data = await res.json();

    mostrarAlerta(data.mensagem, data.sucesso ? 'sucesso' : 'erro');

    if (data.sucesso) {
      this.reset();
      carregarTickets();
    }
  } catch (err) {
    mostrarAlerta('Erro de conexão.', 'erro');
  } finally {
    btn.disabled = false;
  }
});

function mostrarAlerta(msg, tipo) {
  const el = document.getElementById('alerta');
  if (!el) return;
  el.textContent = msg;
  el.className = tipo;
  el.style.display = 'block';
  setTimeout(() => { el.style.display = 'none'; }, 8000);
}

/* CARREGAR TICKETS */
const statusLabel = { aberto: 'Aberto', andamento: 'Em andamento', resolvido: 'Resolvido' };
const prioLabel = { baixa: 'Baixa', media: 'Média', alta: 'Alta' };

async function carregarTickets() {
  const wrap = document.getElementById('tabelaTickets');
  if (!wrap) return;

  try {
    const res = await fetch('php/contato.php?listar=1');
    const data = await res.json();

    if (!data.sucesso || !data.tickets.length) {
      wrap.innerHTML = `<div class="tickets-empty">Nenhum chamado registrado.</div>`;
      return;
    }

    const rows = data.tickets.map(t => `
        <tr>
          <td><span class="ticket-id">#${String(t.id).padStart(4, '0')}</span></td>
          <td class="ticket-assunto">${escHtml(t.subject)}</td>
          <td><span class="badge-status badge-${t.status}">${statusLabel[t.status] || t.status}</span></td>
          <td><span class="badge-prioridade prio-${t.priority}">${prioLabel[t.priority] || t.priority}</span></td>
          <td style="color:var(--text-muted);font-size:12px;">${formatarData(t.created_at)}</td>
        </tr>`).join('');

    wrap.innerHTML = `<table><thead><tr><th>#ID</th><th>Assunto</th><th>Status</th><th>Prioridade</th><th>Data</th></tr></thead><tbody>${rows}</tbody></table>`;
  } catch (err) {
    wrap.innerHTML = `<div class="tickets-empty">Erro ao carregar.</div>`;
  }
}

function escHtml(str) {
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function formatarData(str) {
  const d = new Date(str);
  return isNaN(d) ? str : d.toLocaleDateString('pt-BR');
}

carregarTickets();