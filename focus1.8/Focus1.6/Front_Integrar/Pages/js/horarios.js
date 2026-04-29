// ══════════════════════════════════════
//  HORÁRIOS DA SEMANA — Focus Study
//  horarios.js  (corrigido)
// ══════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

  const DAYS = ['Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'];
  const CAT_LABELS = {
    study: '📚 Estudo', exercise: '🏋️ Exercício',
    meal: '🍽️ Refeição', work: '💼 Trabalho',
    personal: '🌟 Pessoal', rest: '😴 Descanso', other: '📌 Outro'
  };

  // ── Estado ──
  let weekOffset = 0;
  let activities = JSON.parse(localStorage.getItem('fs_schedule') || '{}');

  // ── Data atual ──
  const dateEl = document.getElementById('current-date');
  if (dateEl) {
    dateEl.innerText = new Date().toLocaleDateString('pt-BR', { weekday:'long', day:'numeric', month:'long' });
  }

  // ── Avatar ──
  const profile = JSON.parse(localStorage.getItem('fs_profile') || '{}');
  if (profile.avatar) {
    const navAv = document.getElementById('nav-avatar');
    if (navAv) navAv.src = profile.avatar;
  }

  // ── Calcular semana ──
  function getWeekKey(offset = 0) {
    const d = new Date();
    d.setDate(d.getDate() - d.getDay() + 1 + offset * 7);
    const year = d.getFullYear();
    const week = getWeekNumber(d);
    return `${year}-W${String(week).padStart(2,'0')}`;
  }

  function getWeekNumber(d) {
    const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    const dayNum = date.getUTCDay() || 7;
    date.setUTCDate(date.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
    return Math.ceil((((date - yearStart) / 86400000) + 1) / 7);
  }

  function getWeekDates(offset = 0) {
    const d = new Date();
    d.setDate(d.getDate() - d.getDay() + 1 + offset * 7);
    return Array.from({ length: 7 }, (_, i) => {
      const day = new Date(d);
      day.setDate(d.getDate() + i);
      return day;
    });
  }

  function formatDate(d) {
    return d.toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' });
  }

  function isToday(d) {
    const t = new Date();
    return d.getDate() === t.getDate() && d.getMonth() === t.getMonth() && d.getFullYear() === t.getFullYear();
  }

  function dayKey(weekKey, dayIdx) {
    return `${weekKey}-D${dayIdx}`;
  }

  function getDayActivities(weekKey, dayIdx) {
    return activities[dayKey(weekKey, dayIdx)] || [];
  }

  function setDayActivities(weekKey, dayIdx, arr) {
    activities[dayKey(weekKey, dayIdx)] = arr;
    localStorage.setItem('fs_schedule', JSON.stringify(activities));
  }

  // ── Render ──
  function render() {
    const wk = getWeekKey(weekOffset);
    const dates = getWeekDates(weekOffset);
    const grid = document.getElementById('schedule-grid');
    if (!grid) return;

    const wlabel = document.getElementById('week-label');
    if (wlabel) {
      wlabel.textContent = `${formatDate(dates[0])} – ${formatDate(dates[6])}`;
    }

    grid.innerHTML = '';

    let totalActs = 0, doneActs = 0, totalMins = 0;

    dates.forEach((date, dayIdx) => {
      const acts = getDayActivities(wk, dayIdx);
      acts.sort((a, b) => a.start.localeCompare(b.start));

      const done = acts.filter(a => a.done).length;
      const pct = acts.length ? Math.round(done / acts.length * 100) : 0;

      totalActs += acts.length;
      doneActs += done;
      acts.forEach(a => {
        const [sh, sm] = a.start.split(':').map(Number);
        const [eh, em] = a.end.split(':').map(Number);
        const diff = (eh * 60 + em) - (sh * 60 + sm);
        if (diff > 0) totalMins += diff;
      });

      const col = document.createElement('div');
      col.className = 'day-column';

      col.innerHTML = `
        <div class="day-header">
          <p class="day-name${isToday(date) ? ' today' : ''}">${DAYS[dayIdx]}</p>
          <span class="day-date-label">${formatDate(date)}</span>
          <p class="day-progress${acts.length ? ' has-tasks' : ''}">${acts.length ? pct + '%' : '--'}</p>
          <div class="day-progress-bar"><div class="day-progress-fill" style="width:${pct}%"></div></div>
        </div>
        <div class="day-activities" id="day-acts-${dayIdx}"></div>
      `;
      grid.appendChild(col);

      const actsEl = col.querySelector(`#day-acts-${dayIdx}`);
      if (!acts.length) {
        actsEl.innerHTML = '<p style="font-size:11px;color:var(--text-muted);text-align:center;padding:16px 4px">Sem atividades</p>';
      }
      acts.forEach(act => {
        const item = document.createElement('div');
        item.className = `activity-item${act.done ? ' done' : ''}`;
        item.dataset.cat = act.cat;
        item.dataset.id = act.id;
        item.innerHTML = `
          <div class="act-time">${act.start} – ${act.end}</div>
          <div class="act-title">${act.title}</div>
          <div class="act-cat-badge">${CAT_LABELS[act.cat] || act.cat}</div>
          <div class="act-actions">
            <button class="act-btn check" title="Concluir">✓</button>
            <button class="act-btn del" title="Remover">✕</button>
          </div>
        `;
        actsEl.appendChild(item);

        item.querySelector('.act-btn.check').addEventListener('click', e => {
          e.stopPropagation();
          const list = getDayActivities(wk, dayIdx);
          const idx = list.findIndex(a => a.id === act.id);
          if (idx > -1) list[idx].done = !list[idx].done;
          setDayActivities(wk, dayIdx, list);
          render();
        });

        item.querySelector('.act-btn.del').addEventListener('click', e => {
          e.stopPropagation();
          const list = getDayActivities(wk, dayIdx).filter(a => a.id !== act.id);
          setDayActivities(wk, dayIdx, list);
          render();
        });
      });
    });

    // Update summary
    const weekPct = totalActs ? Math.round(doneActs / totalActs * 100) : 0;
    document.getElementById('week-progress').textContent = weekPct + '%';
    document.getElementById('total-activities').textContent = totalActs;
    document.getElementById('done-activities').textContent = doneActs;
    const totalH = Math.floor(totalMins / 60);
    const totalM = totalMins % 60;
    document.getElementById('planned-hours').textContent = totalM ? `${totalH}h${totalM}m` : `${totalH}h`;
  }

  // ── Modal ──
  let selectedDay = 0;

  function openModal(dayIdx) {
    selectedDay = dayIdx;
    document.getElementById('act-day').value = dayIdx;
    document.getElementById('act-title').value = '';
    document.getElementById('act-notes').value = '';
    document.getElementById('act-start').value = '08:00';
    document.getElementById('act-end').value = '09:00';
    document.getElementById('modal-activity').classList.add('open');
    setTimeout(() => document.getElementById('act-title').focus(), 100);
  }

  // Main "Adicionar Atividade" button — day defaults to today's weekday (Mon=0)
  document.getElementById('btn-add-activity').addEventListener('click', () => {
    const todayDow = new Date().getDay(); // 0=Sun
    // Convert Sun=0..Sat=6 to Mon=0..Sun=6
    const mapped = todayDow === 0 ? 6 : todayDow - 1;
    openModal(mapped);
  });

  document.getElementById('modal-act-cancel').addEventListener('click', () => {
    document.getElementById('modal-activity').classList.remove('open');
  });
  document.getElementById('modal-activity').addEventListener('click', e => {
    if (e.target === document.getElementById('modal-activity'))
      document.getElementById('modal-activity').classList.remove('open');
  });

  document.getElementById('modal-act-confirm').addEventListener('click', () => {
    const title = document.getElementById('act-title').value.trim();
    if (!title) { document.getElementById('act-title').focus(); return; }
    const day = parseInt(document.getElementById('act-day').value);
    const start = document.getElementById('act-start').value;
    const end = document.getElementById('act-end').value;
    const cat = document.getElementById('act-category').value;
    const notes = document.getElementById('act-notes').value.trim();

    const wk = getWeekKey(weekOffset);
    const list = getDayActivities(wk, day);
    list.push({ id: Date.now().toString(), title, start, end, cat, notes, done: false });
    setDayActivities(wk, day, list);
    document.getElementById('modal-activity').classList.remove('open');
    render();
  });

  // ── Limpeza ──
  document.getElementById('btn-clear-week').addEventListener('click', () => {
    document.getElementById('modal-clear').classList.add('open');
  });
  document.getElementById('clear-cancel').addEventListener('click', () => {
    document.getElementById('modal-clear').classList.remove('open');
  });
  document.getElementById('clear-confirm').addEventListener('click', () => {
    const wk = getWeekKey(weekOffset);
    for (let i = 0; i < 7; i++) {
      delete activities[dayKey(wk, i)];
    }
    localStorage.setItem('fs_schedule', JSON.stringify(activities));
    document.getElementById('modal-clear').classList.remove('open');
    render();
  });

  // ── Navegação de semana ──
  document.getElementById('prev-week').addEventListener('click', () => { weekOffset--; render(); });
  document.getElementById('next-week').addEventListener('click', () => { weekOffset++; render(); });

  render();
});
