// ══════════════════════════════════════
//  CONFIGURAÇÕES — Focus Study
//  configuracoes.js (Refatorado e Blindado)
// ══════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

    // 🛡️ CHECAGEM MASTER: Garante que o script só execute na tela que possui o contêiner de configurações ou o botão salvar
    const btnSaveSettings = document.getElementById('save-all-settings');
    if (!btnSaveSettings) return;

    // Atualiza a data na tela de forma segura
    const currentDateEl = document.getElementById('current-date');
    if (currentDateEl) {
        currentDateEl.innerText = new Date().toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' });
    }

    // ── Variáveis de Estado e Padrões ──
    const defaults = {
        focusDuration: 25, shortBreak: 5, longBreak: 15, sessionsLong: 4,
        autoBreak: false, autoFocus: false,
        notifBrowser: true, notifSound: true, notifAchievements: true, reminderTime: '08:00',
        accentColor: 'cyan', compact: false, animations: true, blur: true,
        alertVol: 70, musicVol: 40, defaultSound: 'none'
    };

    let settings = { ...defaults, ...JSON.parse(localStorage.getItem('fs_settings') || '{}') };
    let dirty = false;

    // ── Lógica Visual Dinâmica ──
    function aplicarConfiguracoesVisuais(s) {
        if (!s) return;

        // A. Cores de Destaque
        const cores = {
            'cyan': '#06b6d4',
            'pink': '#ec4899',
            'violet': '#8b5cf6',
            'green': '#10b981',
            'orange': '#f59e0b'
        };
        const hex = cores[s.accentColor] || cores['cyan'];
        document.documentElement.style.setProperty('--cyan', hex); // Mantém compatibilidade com o CSS base
        document.documentElement.style.setProperty('--accent', hex); // Vincula ao novo sistema
        document.documentElement.style.setProperty('--accent-glow', hex + '80');

        // B. Modo Compacto
        document.body.classList.toggle('compact-mode', s.compact);
        document.body.classList.toggle('layout-compact', s.compact);

        // C. Animações e Blur (Classes de controle)
        document.body.classList.toggle('no-animations', !s.animations);
        document.body.classList.toggle('no-anims', !s.animations);
        document.body.classList.toggle('no-blur', !s.blur);
    }

    // ── Load avatar (Sincronizado com Perfil) ──
    const profile = JSON.parse(localStorage.getItem('fs_profile') || '{}');
    if (profile.avatar) {
        const navAv = document.getElementById('nav-avatar');
        if (navAv) navAv.src = profile.avatar;
    }

    // ── Tab switching (Lógica das Abas) ──
    const tabs = document.querySelectorAll('.cfg-tab');
    const panels = document.querySelectorAll('.cfg-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.dataset.tab;
            if (!targetTab) return;

            // Remove classes ativas de todas as abas e painéis existentes
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => {
                p.classList.remove('active');
                p.style.display = 'none'; // Garante compatibilidade visual com o display block do CSS
            });

            tab.classList.add('active');
            
            const targetPanel = document.getElementById('tab-' + targetTab);
            if (targetPanel) {
                targetPanel.classList.add('active');
                targetPanel.style.display = 'block';
            }
        });
    });

    // Força a primeira aba ('appearance') a iniciar aberta de forma visível caso nenhuma esteja ativa
    const firstTab = document.querySelector('.cfg-tab[data-tab="appearance"]');
    if (firstTab) firstTab.click();

    // ── Sincronizar UI com Estado ──
    function loadUI() {
        // Aparência
        const compactEl = document.getElementById('cfg-compact');
        if (compactEl) compactEl.checked = settings.compact;

        const animsEl = document.getElementById('cfg-animations');
        if (animsEl) animsEl.checked = settings.animations;

        const blurEl = document.getElementById('cfg-blur');
        if (blurEl) blurEl.checked = settings.blur;

        // Sons
        const alertVolEl = document.getElementById('cfg-alert-vol');
        if (alertVolEl) alertVolEl.value = settings.alertVol;

        const alertTxtEl = document.getElementById('alert-vol-val');
        if (alertTxtEl) alertTxtEl.textContent = settings.alertVol + '%';

        const musicVolEl = document.getElementById('cfg-music-vol');
        if (musicVolEl) musicVolEl.value = settings.musicVol;

        const musicTxtEl = document.getElementById('music-vol-val');
        if (musicTxtEl) musicTxtEl.textContent = settings.musicVol + '%';

        const soundEl = document.getElementById('cfg-default-sound');
        if (soundEl) soundEl.value = settings.defaultSound;

        // Swatches
        document.querySelectorAll('.swatch').forEach(s => {
            s.classList.toggle('active', s.dataset.color === settings.accentColor);
        });

        // Aplicar visual imediatamente ao carregar
        aplicarConfiguracoesVisuais(settings);
        updateSaveBarState(false);
    }

    // ── Gerenciamento da Barra de Salvar ──
    function updateSaveBarState(isDirty) {
        dirty = isDirty;
        const saveBar = document.getElementById('save-bar');
        if (saveBar) {
            if (dirty) {
                saveBar.classList.add('visible');
                saveBar.classList.add('show');
                saveBar.style.display = 'flex';
            } else {
                saveBar.classList.remove('visible');
                saveBar.classList.remove('show');
                saveBar.style.display = 'none';
            }
        }
    }

    function markDirty() {
        updateSaveBarState(true);
        collectSettings();
        aplicarConfiguracoesVisuais(settings);
    }

    function collectSettings() {
        settings.compact = document.getElementById('cfg-compact')?.checked ?? settings.compact;
        settings.animations = document.getElementById('cfg-animations')?.checked ?? settings.animations;
        settings.blur = document.getElementById('cfg-blur')?.checked ?? settings.blur;

        // Sons
        const alertVolEl = document.getElementById('cfg-alert-vol');
        if (alertVolEl) settings.alertVol = parseInt(alertVolEl.value);

        const musicVolEl = document.getElementById('cfg-music-vol');
        if (musicVolEl) settings.musicVol = parseInt(musicVolEl.value);

        const soundEl = document.getElementById('cfg-default-sound');
        if (soundEl) settings.defaultSound = soundEl.value;
        
        const activeSwatch = document.querySelector('.swatch.active');
        if (activeSwatch) settings.accentColor = activeSwatch.dataset.color;
    }

    // Listeners para mudanças dinâmicas apenas nos inputs que realmente existem na tela
    document.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('change', markDirty);
    });

    // Update real-time com proteção para os labels de volume
    const alertVolInput = document.getElementById('cfg-alert-vol');
    if (alertVolInput) {
        alertVolInput.addEventListener('input', e => {
            const txt = document.getElementById('alert-vol-val');
            if (txt) txt.textContent = e.target.value + '%';
            markDirty();
        });
    }

    const musicVolInput = document.getElementById('cfg-music-vol');
    if (musicVolInput) {
        musicVolInput.addEventListener('input', e => {
            const txt = document.getElementById('music-vol-val');
            if (txt) txt.textContent = e.target.value + '%';
            markDirty();
        });
    }

    // Color swatches logic
    document.querySelectorAll('.swatch').forEach(s => {
        s.addEventListener('click', () => {
            document.querySelectorAll('.swatch').forEach(x => x.classList.remove('active'));
            s.classList.add('active');
            markDirty();
        });
    });

    // ── Salvar e Descartar ──
    btnSaveSettings.addEventListener('click', () => {
        collectSettings();
        localStorage.setItem('fs_settings', JSON.stringify(settings));
        updateSaveBarState(false);
        showToast('Configurações salvas com sucesso!');
        
        // Notifica outras abas ou componentes que as configurações mudaram
        window.dispatchEvent(new CustomEvent('settingsUpdated', { detail: settings }));
    });

    const btnDiscard = document.getElementById('discard-changes');
    if (btnDiscard) {
        btnDiscard.addEventListener('click', () => {
            settings = { ...defaults, ...JSON.parse(localStorage.getItem('fs_settings') || '{}') };
            loadUI();
            updateSaveBarState(false);
            showToast('Alterações descartadas.');
        });
    }

    // ── Exportação e Gerenciamento de Dados ──
    const btnExport = document.getElementById('btn-export');
    if (btnExport) {
        btnExport.addEventListener('click', () => {
            const data = {
                profile: JSON.parse(localStorage.getItem('fs_profile') || '{}'),
                habits: JSON.parse(localStorage.getItem('fs_habits') || '[]'),
                schedule: JSON.parse(localStorage.getItem('fs_schedule') || '{}'),
                tasks: JSON.parse(localStorage.getItem('fs_tasks') || '[]'),
                settings: settings,
                timestamp: new Date().toISOString()
            };
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `focusstudy-backup-${new Date().toISOString().slice(0, 10)}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Backup gerado e baixado!');
        });
    }

    const btnClearHabits = document.getElementById('btn-clear-habits');
    if (btnClearHabits) {
        btnClearHabits.addEventListener('click', () => {
            if (confirm('Atenção: Isso removerá permanentemente todos os seus hábitos. Continuar?')) {
                localStorage.removeItem('fs_habits');
                showToast('Dados de hábitos removidos.');
            }
        });
    }

    const btnClearTasks = document.getElementById('btn-clear-tasks');
    if (btnClearTasks) {
        btnClearTasks.addEventListener('click', () => {
            if (confirm('Atenção: Isso removerá permanentemente todas as suas missões. Continuar?')) {
                localStorage.removeItem('fs_tasks');
                showToast('Dados de missões removidos.');
            }
        });
    }

    // ── Reset Total ──
    const btnResetAll = document.getElementById('btn-reset-all');
    const modalReset = document.getElementById('modal-reset');
    const resetCancel = document.getElementById('reset-cancel');
    const resetConfirm = document.getElementById('reset-confirm');

    if (btnResetAll) {
        btnResetAll.addEventListener('click', () => {
            if (modalReset) modalReset.classList.add('open');
        });
    }

    if (resetCancel) {
        resetCancel.addEventListener('click', () => {
            if (modalReset) modalReset.classList.remove('open');
        });
    }

    if (resetConfirm) {
        resetConfirm.addEventListener('click', () => {
            const keys = ['fs_habits', 'fs_schedule', 'fs_tasks', 'fs_notes', 'fs_settings', 'fs_pomo_count', 'fs_pomo_total', 'fs_streak', 'fs_profile'];
            keys.forEach(k => localStorage.removeItem(k));
            
            if (modalReset) modalReset.classList.remove('open');
            showToast('Todos os dados foram resetados!');
            setTimeout(() => location.reload(), 1200);
        });
    }

    function showToast(msg) {
        const t = document.getElementById('toast');
        if (t) {
            t.textContent = msg;
            t.className = 'toast success show';
            setTimeout(() => { t.className = 'toast'; }, 3000);
        }
    }

    // Inicialização da UI resgatando o estado atual seguro
    loadUI();
});