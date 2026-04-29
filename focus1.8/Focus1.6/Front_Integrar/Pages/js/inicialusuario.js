// ══════════════════════════════════════════
//  PAINEL DO USUÁRIO — Focus Study
//  inicialusuario.js
// ══════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

  // ── Frases motivacionais ─────────────────
  const QUOTES = [
    "O foco de hoje é o sucesso de amanhã.",
    "Uma sessão de cada vez. Você consegue.",
    "Pequenos passos constantes vencem grandes saltos esporádicos.",
    "Sua atenção é seu ativo mais valioso. Proteja-a.",
    "Feito é melhor que perfeito. Comece agora.",
    "Disciplina é liberdade em forma de escolha.",
    "O melhor momento para começar era ontem. O segundo melhor é agora.",
    "Foco total gera resultados extraordinários.",
  ];
  const quoteEl = document.getElementById('daily-quote')?.querySelector('.quote-text');
  if (quoteEl) {
    const idx = new Date().getDate() % QUOTES.length;
    quoteEl.textContent = `"${QUOTES[idx]}"`;
  }

  // ── Data atual ───────────────────────────
  const dateEl = document.getElementById('current-date');
  if (dateEl) {
    const opts = { weekday: 'long', day: 'numeric', month: 'long' };
    dateEl.innerText = new Date().toLocaleDateString('pt-BR', opts);
  }

  // ══════════════════════════════════════════
  //  TIMER POMODORO
  // ══════════════════════════════════════════
  let timer, timeLeft = 25 * 60, running = false;
  let pomodoroCount = parseInt(localStorage.getItem('fs_pomo_count') || '0');
  let currentLabel = 'Foco';

  const minEl     = document.getElementById('minutes');
  const secEl     = document.getElementById('seconds');
  const playBtn   = document.querySelector('.js-play');
  const resetBtn  = document.querySelector('.js-reset');
  const statusEl  = document.querySelector('.js-timer-status');
  const dotsEl    = document.getElementById('pomo-dots');

  function updateTimerDisplay() {
    const m = Math.floor(timeLeft / 60);
    const s = timeLeft % 60;
    minEl.innerText = m.toString().padStart(2, '0');
    secEl.innerText = s.toString().padStart(2, '0');
    document.title = `${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')} — Focus Study`;
  }

  function renderDots() {
    if (!dotsEl) return;
    dotsEl.innerHTML = '';
    for (let i = 0; i < 4; i++) {
      const d = document.createElement('span');
      d.className = 'pomo-dot' + (i < pomodoroCount ? ' done' : '');
      dotsEl.appendChild(d);
    }
  }

  function finishSession() {
    clearInterval(timer);
    running = false;
    playBtn.innerHTML = `<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Iniciar`;
    statusEl.innerText = '✅ Sessão concluída!';

    if (currentLabel === 'Foco') {
      pomodoroCount = Math.min(pomodoroCount + 1, 4);
      if (pomodoroCount >= 4) pomodoroCount = 0;
      localStorage.setItem('fs_pomo_count', pomodoroCount);
      renderDots();
    }

    // Notificação do browser
    if (Notification.permission === 'granted') {
      new Notification('Focus Study ⏱️', {
        body: `Sessão de ${currentLabel} concluída! Hora de ${currentLabel === 'Foco' ? 'descansar' : 'focar'}.`,
        icon: '/Pages/images/poppy.png'
      });
    }
  }

  playBtn?.addEventListener('click', () => {
    if (running) {
      clearInterval(timer);
      playBtn.innerHTML = `<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Retomar`;
      statusEl.innerText = '⏸ Pausado';
    } else {
      if (Notification.permission === 'default') Notification.requestPermission();
      statusEl.innerText = currentLabel === 'Foco' ? '🎯 Foco total!' : '☕ Descansando...';
      playBtn.innerHTML = `<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg> Pausar`;
      timer = setInterval(() => {
        if (timeLeft <= 0) { finishSession(); }
        else { timeLeft--; updateTimerDisplay(); }
      }, 1000);
    }
    running = !running;
  });

  resetBtn?.addEventListener('click', () => {
    clearInterval(timer);
    running = false;
    const activeMode = document.querySelector('.mode-btn.active');
    timeLeft = (parseInt(activeMode?.dataset.minutes) || 25) * 60;
    updateTimerDisplay();
    playBtn.innerHTML = `<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Iniciar`;
    statusEl.innerText = 'Pronto para começar';
    document.title = 'Focus Study';
  });

  // Botões de modo
  document.querySelectorAll('.mode-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      clearInterval(timer);
      running = false;
      currentLabel = btn.dataset.label;
      timeLeft = parseInt(btn.dataset.minutes) * 60;
      updateTimerDisplay();
      playBtn.innerHTML = `<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Iniciar`;
      statusEl.innerText = 'Pronto para começar';
    });
  });

  renderDots();
  updateTimerDisplay();

  // ══════════════════════════════════════════
  //  MISSÕES
  // ══════════════════════════════════════════
  const taskList     = document.querySelector('.js-task-list');
  const addTaskBtn   = document.querySelector('.js-add-task');
  const tasksEmpty   = document.getElementById('tasks-empty');
  const tasksDone    = document.getElementById('tasks-done-count');
  const tasksFill    = document.getElementById('tasks-progress-fill');

  // Modal adicionar
  const modalAdd     = document.getElementById('modal-add-task');
  const taskInput    = document.getElementById('task-input');
  const cancelAdd    = document.getElementById('cancel-add-task');
  const confirmAdd   = document.getElementById('confirm-add-task');

  // Modal deletar
  const modalDel     = document.getElementById('modal-delete-task');
  const delName      = document.getElementById('delete-task-name');
  const cancelDel    = document.getElementById('cancel-delete-task');
  const confirmDel   = document.getElementById('confirm-delete-task');

  let pendingDeleteItem = null;

  // Carrega tarefas salvas
  let tasks = loadTasks();
  renderTasks();

  function loadTasks() {
    try {
      const raw = localStorage.getItem('fs_tasks');
      return raw ? JSON.parse(raw) : [{ id: 'default-1', text: 'Finalizar projeto de Front-end', done: false }];
    } catch { return []; }
  }

  function saveTasks() {
    try { localStorage.setItem('fs_tasks', JSON.stringify(tasks)); } catch {}
  }

  function renderTasks() {
    taskList.innerHTML = '';
    const total = tasks.length;
    const done  = tasks.filter(t => t.done).length;

    if (total === 0) {
      tasksEmpty?.style && (tasksEmpty.style.display = 'flex');
    } else {
      tasksEmpty?.style && (tasksEmpty.style.display = 'none');
    }

    tasks.forEach(task => {
      const li = document.createElement('li');
      li.className = 'task-item';
      li.dataset.id = task.id;
      li.innerHTML = `
        <label class="custom-checkbox">
          <input type="checkbox" ${task.done ? 'checked' : ''}>
          <span class="checkmark"></span>
          <span class="task-text">${escapeHtml(task.text)}</span>
        </label>
        <button class="btn-delete-task" title="Remover missão">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
            <path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
          </svg>
        </button>
      `;

      // Toggle done
      li.querySelector('input').addEventListener('change', e => {
        const t = tasks.find(t => t.id === task.id);
        if (t) t.done = e.target.checked;
        saveTasks();
        updateTasksFooter();
        // Tachado instantâneo sem re-render completo
        const txt = li.querySelector('.task-text');
        if (e.target.checked) { txt.style.textDecoration = 'line-through'; txt.style.color = 'var(--text-muted)'; }
        else { txt.style.textDecoration = ''; txt.style.color = ''; }
      });

      // Botão deletar → abre modal
      li.querySelector('.btn-delete-task').addEventListener('click', () => {
        pendingDeleteItem = task.id;
        delName.textContent = `"${task.text}"`;
        modalDel.classList.add('open');
      });

      taskList.appendChild(li);
    });

    updateTasksFooter();
  }

  function updateTasksFooter() {
    const total = tasks.length;
    const done  = tasks.filter(t => t.done).length;
    const pct   = total > 0 ? Math.round((done / total) * 100) : 0;
    if (tasksDone) tasksDone.textContent = `${done} de ${total} concluída${total !== 1 ? 's' : ''}`;
    if (tasksFill) tasksFill.style.width = `${pct}%`;
  }

  // Abrir modal adicionar
  addTaskBtn?.addEventListener('click', () => {
    taskInput.value = '';
    modalAdd.classList.add('open');
    setTimeout(() => taskInput.focus(), 100);
  });

  cancelAdd?.addEventListener('click', () => modalAdd.classList.remove('open'));
  modalAdd?.addEventListener('click', e => { if (e.target === modalAdd) modalAdd.classList.remove('open'); });

  confirmAdd?.addEventListener('click', () => {
    const text = taskInput.value.trim();
    if (!text) { taskInput.focus(); return; }
    tasks.push({ id: `task-${Date.now()}`, text, done: false });
    saveTasks();
    renderTasks();
    modalAdd.classList.remove('open');
  });

  taskInput?.addEventListener('keydown', e => { if (e.key === 'Enter') confirmAdd.click(); });
  taskInput?.addEventListener('keydown', e => { if (e.key === 'Escape') cancelAdd.click(); });

  // Confirmar exclusão
  cancelDel?.addEventListener('click', () => { modalDel.classList.remove('open'); pendingDeleteItem = null; });
  modalDel?.addEventListener('click', e => { if (e.target === modalDel) { modalDel.classList.remove('open'); pendingDeleteItem = null; } });

  confirmDel?.addEventListener('click', () => {
    if (pendingDeleteItem) {
      tasks = tasks.filter(t => t.id !== pendingDeleteItem);
      saveTasks();
      renderTasks();
    }
    modalDel.classList.remove('open');
    pendingDeleteItem = null;
  });

  // ══════════════════════════════════════════
  //  PLAYER DE MÚSICA
  // ══════════════════════════════════════════
  const trackItems    = document.querySelectorAll('.track-item');
  const playerPlay    = document.getElementById('player-play');
  const playerPrev    = document.getElementById('player-prev');
  const playerNext    = document.getElementById('player-next');
  const playerLoop    = document.getElementById('player-loop');
  const playerSeek    = document.getElementById('player-seek');
  const playerCurrent = document.getElementById('player-current');
  const playerDur     = document.getElementById('player-duration');
  const playerName    = document.getElementById('player-track-name');
  const playerGenre   = document.getElementById('player-track-genre');
  const playerIcon    = document.getElementById('player-track-icon');
  const playerBadge   = document.getElementById('player-badge');
  const volumeSlider  = document.getElementById('ambient-volume');

  let audio         = new Audio();
  let currentIndex  = -1;
  let isLooping     = false;
  let seekDragging  = false;
  const tracks      = Array.from(trackItems);

  audio.volume = parseFloat(volumeSlider?.value || 0.5);
  audio.loop   = false;

  function fmtTime(s) {
    if (!s || isNaN(s)) return '0:00';
    const m = Math.floor(s / 60);
    const sec = Math.floor(s % 60);
    return `${m}:${sec.toString().padStart(2, '0')}`;
  }

  function setPlayingUI(playing) {
    const iconPlay  = playerPlay?.querySelector('.icon-play');
    const iconPause = playerPlay?.querySelector('.icon-pause');
    if (iconPlay)  iconPlay.style.display  = playing ? 'none'  : 'inline';
    if (iconPause) iconPause.style.display = playing ? 'inline': 'none';
    if (playerBadge) playerBadge.style.display = playing ? 'inline-flex' : 'none';
  }

  function loadTrack(index, autoplay = true) {
    if (index < 0 || index >= tracks.length) return;
    currentIndex = index;

    // Atualiza UI das faixas
    tracks.forEach((t, i) => t.classList.toggle('active', i === index));

    const item   = tracks[index];
    const src    = item.dataset.src;
    const name   = item.dataset.track;
    const icon   = item.querySelector('.track-icon')?.textContent || '🎵';
    const genre  = item.querySelector('.track-genre')?.textContent || '';

    audio.pause();
    audio.src = src;
    audio.load();

    if (playerName)  playerName.textContent  = name;
    if (playerGenre) playerGenre.textContent  = genre;
    if (playerIcon)  playerIcon.textContent   = icon;
    if (playerSeek)  playerSeek.value         = 0;
    if (playerCurrent) playerCurrent.textContent = '0:00';
    if (playerDur)   playerDur.textContent    = '0:00';

    if (autoplay) {
      audio.play().catch(() => {});
      setPlayingUI(true);
    } else {
      setPlayingUI(false);
    }
  }

  // Click na faixa
  tracks.forEach((item, i) => {
    item.addEventListener('click', () => {
      if (currentIndex === i && !audio.paused) {
        audio.pause();
        setPlayingUI(false);
      } else if (currentIndex === i && audio.paused) {
        audio.play().catch(() => {});
        setPlayingUI(true);
      } else {
        loadTrack(i, true);
      }
    });
  });

  // Play/Pause
  playerPlay?.addEventListener('click', () => {
    if (currentIndex === -1) { loadTrack(0, true); return; }
    if (audio.paused) {
      audio.play().catch(() => {});
      setPlayingUI(true);
    } else {
      audio.pause();
      setPlayingUI(false);
    }
  });

  // Anterior
  playerPrev?.addEventListener('click', () => {
    if (audio.currentTime > 3) {
      audio.currentTime = 0;
    } else {
      const prev = (currentIndex - 1 + tracks.length) % tracks.length;
      loadTrack(prev, !audio.paused);
    }
  });

  // Próxima
  playerNext?.addEventListener('click', () => {
    const next = (currentIndex + 1) % tracks.length;
    loadTrack(next, !audio.paused);
  });

  // Loop
  playerLoop?.addEventListener('click', () => {
    isLooping = !isLooping;
    audio.loop = isLooping;
    playerLoop?.classList.toggle('active', isLooping);
  });

  // Ao terminar a faixa
  audio.addEventListener('ended', () => {
    if (!isLooping) {
      const next = (currentIndex + 1) % tracks.length;
      loadTrack(next, true);
    }
  });

  // Progress bar
  audio.addEventListener('timeupdate', () => {
    if (seekDragging || !audio.duration) return;
    const pct = (audio.currentTime / audio.duration) * 100;
    if (playerSeek) playerSeek.value = pct;
    if (playerCurrent) playerCurrent.textContent = fmtTime(audio.currentTime);
  });

  audio.addEventListener('loadedmetadata', () => {
    if (playerDur) playerDur.textContent = fmtTime(audio.duration);
  });

  // Seek drag
  playerSeek?.addEventListener('mousedown', () => { seekDragging = true; });
  playerSeek?.addEventListener('touchstart', () => { seekDragging = true; });
  playerSeek?.addEventListener('input', () => {
    if (!audio.duration) return;
    const t = (parseFloat(playerSeek.value) / 100) * audio.duration;
    if (playerCurrent) playerCurrent.textContent = fmtTime(t);
  });
  playerSeek?.addEventListener('change', () => {
    seekDragging = false;
    if (!audio.duration) return;
    audio.currentTime = (parseFloat(playerSeek.value) / 100) * audio.duration;
  });

  // Volume
  volumeSlider?.addEventListener('input', () => {
    audio.volume = parseFloat(volumeSlider.value);
  });

  // ══════════════════════════════════════════
  //  NOTAS RÁPIDAS + HISTÓRICO
  // ══════════════════════════════════════════
  const noteEl        = document.getElementById('quick-note');
  const noteChars     = document.getElementById('note-chars');
  const noteSaved     = document.getElementById('note-saved');
  const clearNote     = document.getElementById('btn-clear-note');
  const saveNoteBtn   = document.getElementById('btn-save-note');
  const historyList   = document.getElementById('note-history-list');
  const historyEmpty  = document.getElementById('note-history-empty');
  const historyCount  = document.getElementById('note-history-count');
  let noteSaveTimer;

  // Carrega rascunho atual
  if (noteEl) {
    noteEl.value = localStorage.getItem('fs_note') || '';
    updateNoteChars();

    noteEl.addEventListener('input', () => {
      updateNoteChars();
      clearTimeout(noteSaveTimer);
      noteSaveTimer = setTimeout(() => {
        localStorage.setItem('fs_note', noteEl.value);
        noteSaved.classList.add('visible');
        setTimeout(() => noteSaved.classList.remove('visible'), 2000);
      }, 800);
    });
  }

  clearNote?.addEventListener('click', () => {
    if (noteEl) { noteEl.value = ''; updateNoteChars(); localStorage.removeItem('fs_note'); }
  });

  function updateNoteChars() {
    if (noteChars && noteEl) noteChars.textContent = `${noteEl.value.length}/500`;
  }

  // ── Histórico ────────────────────────────
  function loadHistory() {
    try { return JSON.parse(localStorage.getItem('fs_note_history') || '[]'); }
    catch { return []; }
  }

  function saveHistory(history) {
    localStorage.setItem('fs_note_history', JSON.stringify(history));
  }

  function fmtDate(iso) {
    const d = new Date(iso);
    const hoje = new Date();
    const diff = Math.floor((hoje - d) / 86400000);
    if (diff === 0) return 'Hoje, ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    if (diff === 1) return 'Ontem, ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' }) + ' · ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  }

  function renderHistory() {
    const history = loadHistory();
    historyCount.textContent = `${history.length} nota${history.length !== 1 ? 's' : ''}`;

    if (history.length === 0) {
      historyList.innerHTML = '';
      historyEmpty.style.display = 'flex';
      return;
    }
    historyEmpty.style.display = 'none';

    historyList.innerHTML = history.map((item, i) => `
      <li class="note-history-item" data-index="${i}">
        <div class="note-history-top">
          <span class="note-history-date">${fmtDate(item.date)}</span>
          <div class="note-history-actions">
            <button class="note-hist-btn note-hist-restore" data-index="${i}" title="Restaurar no editor">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
            </button>
            <button class="note-hist-btn note-hist-delete" data-index="${i}" title="Remover nota">
              <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
        </div>
        <p class="note-history-text">${escapeHtml(item.text)}</p>
      </li>
    `).join('');

    // Restaurar
    historyList.querySelectorAll('.note-hist-restore').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = parseInt(btn.dataset.index);
        const history = loadHistory();
        if (noteEl) {
          noteEl.value = history[idx].text;
          updateNoteChars();
          localStorage.setItem('fs_note', history[idx].text);
          noteSaved.classList.add('visible');
          setTimeout(() => noteSaved.classList.remove('visible'), 2000);
        }
      });
    });

    // Deletar
    historyList.querySelectorAll('.note-hist-delete').forEach(btn => {
      btn.addEventListener('click', () => {
        const idx = parseInt(btn.dataset.index);
        const history = loadHistory();
        history.splice(idx, 1);
        saveHistory(history);
        renderHistory();
      });
    });
  }

  // Guardar nota no histórico
  saveNoteBtn?.addEventListener('click', () => {
    const text = noteEl?.value.trim();
    if (!text) return;
    const history = loadHistory();
    history.unshift({ text, date: new Date().toISOString() });
    if (history.length > 20) history.splice(20); // limita a 20 entradas
    saveHistory(history);
    renderHistory();
    // Feedback visual no botão
    saveNoteBtn.textContent = '✓ Guardado!';
    setTimeout(() => {
      saveNoteBtn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar`;
    }, 1500);
  });

  renderHistory();

  // ── Utils ────────────────────────────────
  function escapeHtml(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
});
