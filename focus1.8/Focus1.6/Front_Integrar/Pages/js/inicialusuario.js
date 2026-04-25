async function fetchTarefas() { //corrigido
    try {
        const res = await fetch("php/tarefas.php");
        const dados = await res.json();

        const tarefas = Array.isArray(dados) ? dados : [];

        const taskList = document.querySelector(".js-task-list");
        if (!taskList) return;

        taskList.innerHTML = '';

        tarefas.forEach(tarefa => {
            const li = document.createElement('li');
            li.className = 'task-item';
            const isChecked = tarefa.done == 1 ? 'checked' : '';

            li.innerHTML = `
                <label class="custom-checkbox">
                    <input type="checkbox" ${isChecked} onchange="toggleStatus(${tarefa.task_id})">
                    <span class="checkmark"></span>
                    <span class="task-text">${tarefa.title}</span>
                </label>
            `;
            taskList.appendChild(li);
        });
    } catch (err) {
        console.log("Erro ao Listar Tarefas:", err);
    }
}

async function toggleStatus(id) {
    const fd = new FormData(); 
    fd.append('acao', 'toggle');
    fd.append('task_id', id);

    try {
        await fetch('php/tarefas.php', { method: "POST", body: fd });
    } catch (err) {
        console.error("Erro ao atualizar status:", err);
        fetchTarefas(); // Reverte visualmente se falhar
    }
}

async function addTarefa(texto) {
    const fd = new FormData();
    fd.append('acao', 'inserir');
    fd.append('titulo', texto);

    try {
        const res = await fetch('php/tarefas.php', { method: "POST", body: fd });
        const dados = await res.json();
        if (dados.sucesso) {
            fetchTarefas();
        }
    } catch (err) {
        console.error("Erro ao adicionar Tarefa:", err);
    }
}


document.addEventListener('DOMContentLoaded', () => {
    // FUNÇÃO PARA BUSCAR NOME DO PHP
    async function verificarSessaoERenderizar() {
        try {
            const response = await fetch('php/inicialusuario.php');
            const data = await response.json();

            if (data.logado) {
                const nomeEl = document.querySelector('.gradient-text');
                if (nomeEl) nomeEl.innerText = data.nome;

                const avatarImg = document.querySelector('.user-avatar img');
                if (avatarImg && data.foto) avatarImg.src = data.foto;

                if (data.profile_id) {
                    fetchTarefas();
                }
            } else {
                window.location.href = '../login.html';
            }
        } catch (error) {
            console.error("Erro na comunicação com o servidor:", error);
        }
    }
    verificarSessaoERenderizar();

    // DATA 
    const dateEl = document.getElementById('current-date');
    const options = { weekday: 'long', day: 'numeric', month: 'long' };
    dateEl.innerText = new Date().toLocaleDateString('pt-BR', options);

    // TIMER
    let timer;
    let timeLeft = 25 * 60;
    let running = false;
    const minEl = document.getElementById('minutes');
    const secEl = document.getElementById('seconds');
    const playBtn = document.querySelector('.js-play');
    const statusText = document.querySelector('.js-timer-status');

    function updateTimer() {
        const m = Math.floor(timeLeft / 60);
        const s = timeLeft % 60;
        minEl.innerText = m.toString().padStart(2, '0');
        secEl.innerText = s.toString().padStart(2, '0');
    }

    playBtn.addEventListener('click', () => {
        if (running) {
            clearInterval(timer);
            playBtn.innerText = 'Retomar';
            statusText.innerText = 'Pausado';
        } else {
            statusText.innerText = 'Foco total!';
            playBtn.innerText = 'Pausar';
            timer = setInterval(() => {
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    alert("Tempo esgotado!");
                } else {
                    timeLeft--;
                    updateTimer();
                }
            }, 1000);
        }
        running = !running;
    });

    document.querySelector('.js-reset').addEventListener('click', () => {
        clearInterval(timer);
        running = false;
        timeLeft = 25 * 60;
        updateTimer();
        playBtn.innerText = 'Iniciar';
        statusText.innerText = 'Pronto para começar';
    });

    //  TAREFAS 
    const taskList = document.querySelector('.js-task-list');
    const addBtn = document.querySelector('.js-add-task');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            const text = prompt("Qual a nova missão?");
            if (text) addTarefa(text);
        });
    }

});