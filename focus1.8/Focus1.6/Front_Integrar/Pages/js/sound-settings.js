/* ══════════════════════════════════════
   SOUND SETTINGS MANAGER — Focus Study
   sound-settings.js

   ① Volume dos Alertas
       → Chime ao fim de cada sessão Pomodoro
       → Detecta via MutationObserver o texto
         "✅ Sessão concluída!" que finishSession()
         escreve em .js-timer-status
   ② Volume da Música
       → Aplica fs_settings.musicVol ao slider
         #ambient-volume quando a página carrega
         (o player já escuta o evento 'input')
══════════════════════════════════════ */

(function () {
    'use strict';

    // ─────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────
    function getSettings() {
        const defaults = { alertVol: 70, musicVol: 40 };
        try {
            return Object.assign(defaults, JSON.parse(localStorage.getItem('fs_settings') || '{}'));
        } catch (_) {
            return defaults;
        }
    }

    // ─────────────────────────────────────
    // ① Web Audio — Chime de alerta
    //    5 notas: C5 → E5 → G5 → E5 → C5
    //    Volume controlado por fs_settings.alertVol (0-100)
    // ─────────────────────────────────────
    var _ctx = null;

    function getCtx() {
        if (!_ctx) _ctx = new (window.AudioContext || window.webkitAudioContext)();
        return _ctx;
    }

    function playChime(volume) {
        var vol = typeof volume === 'number' ? volume : getSettings().alertVol;
        try {
            var ctx = getCtx();
            if (ctx.state === 'suspended') ctx.resume();

            var gain = Math.max(0, Math.min(100, vol)) / 100;
            var notes = [523.25, 659.25, 783.99, 659.25, 523.25];
            var t = ctx.currentTime;

            notes.forEach(function (freq) {
                var osc = ctx.createOscillator();
                var env = ctx.createGain();
                osc.connect(env);
                env.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.value = freq;
                env.gain.setValueAtTime(0, t);
                env.gain.linearRampToValueAtTime(gain * 0.45, t + 0.04);
                env.gain.exponentialRampToValueAtTime(0.001, t + 0.55);
                osc.start(t);
                osc.stop(t + 0.6);
                t += 0.28;
            });
        } catch (err) {
            console.warn('[SoundSettings] Chime falhou:', err);
        }
    }

    // ─────────────────────────────────────
    // ② Aplicar volume de música ao player
    //    O player em inicialusuario.js lê o
    //    slider #ambient-volume via evento 'input'
    //    → basta setar o value e disparar o evento
    // ─────────────────────────────────────
    function applyMusicVolume(volPercent) {
        var slider = document.getElementById('ambient-volume');
        if (!slider) return;
        slider.value = Math.max(0, Math.min(100, volPercent)) / 100;
        slider.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // ─────────────────────────────────────
    // Desbloquear AudioContext
    //    Navegadores exigem gesto do usuário
    //    antes de criar/resumir AudioContext
    // ─────────────────────────────────────
    function unlockCtx() {
        function unlock() {
            getCtx();
            if (_ctx && _ctx.state === 'suspended') _ctx.resume();
        }
        document.addEventListener('click',      unlock, { once: true });
        document.addEventListener('keydown',    unlock, { once: true });
        document.addEventListener('touchstart', unlock, { once: true });
    }

    // ─────────────────────────────────────
    // Observar .js-timer-status
    //    finishSession() escreve exatamente:
    //    "✅ Sessão concluída!"
    //    → é o gancho mais confiável possível
    // ─────────────────────────────────────
    function watchTimerStatus() {
        var statusEl = document.querySelector('.js-timer-status');
        if (!statusEl) return;

        var observer = new MutationObserver(function () {
            if (statusEl.textContent.indexOf('Sessão concluída') !== -1) {
                playChime(getSettings().alertVol);
            }
        });

        observer.observe(statusEl, {
            childList: true,
            characterData: true,
            subtree: true
        });
    }

    // ─────────────────────────────────────
    // Init
    // ─────────────────────────────────────
    function init() {
        var s = getSettings();

        // Aplica volume de música salvo (pequeno delay para o player inicializar)
        setTimeout(function () { applyMusicVolume(s.musicVol); }, 150);

        // Observa o status do timer para disparar o chime
        watchTimerStatus();

        // Garante que AudioContext pode ser criado
        unlockCtx();

        // Atualização em tempo real ao salvar nas Configurações
        window.addEventListener('settingsUpdated', function (e) {
            var updated = (e && e.detail) || {};
            if (updated.musicVol !== undefined) applyMusicVolume(updated.musicVol);
        });

        // Sincroniza quando outra aba salva configurações
        window.addEventListener('storage', function (e) {
            if (e.key === 'fs_settings') applyMusicVolume(getSettings().musicVol);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
