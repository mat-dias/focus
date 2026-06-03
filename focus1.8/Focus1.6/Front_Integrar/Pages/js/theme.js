// ══════════════════════════════════════
//  THEME MANAGER — Focus Study
//  theme.js  |  Carregue no <head> de todas as páginas
// ══════════════════════════════════════

(function () {
    'use strict';

    const CORES = {
        cyan:   '#06b6d4',
        pink:   '#ec4899',
        violet: '#8b5cf6',
        green:  '#10b981',
        orange: '#f59e0b'
    };

    // ── Aplica tema e cor no elemento raiz ──
    function aplicar(s) {
        const hex = CORES[s.accentColor] || CORES.cyan;

        document.documentElement.style.setProperty('--cyan',         hex);
        document.documentElement.style.setProperty('--accent',       hex);
        document.documentElement.style.setProperty('--accent-glow',  hex + '80');
        document.documentElement.style.setProperty('--pink',         '#ec4899'); // garante --pink

        // Tema claro / escuro
        const isLight = s.theme === 'light';
        document.documentElement.classList.toggle('light-mode', isLight);
        if (document.body) document.body.classList.toggle('light-mode', isLight);
    }

    // ── Leitura do localStorage ──
    function lerSettings() {
        try { return JSON.parse(localStorage.getItem('fs_settings') || '{}'); }
        catch { return {}; }
    }

    // Aplica imediatamente (antes do body) para evitar flash de tema errado
    aplicar(lerSettings());

    // Quando o DOM estiver pronto: re-aplica no body e registra listeners
    document.addEventListener('DOMContentLoaded', function () {
        const s = lerSettings();
        aplicar(s);

        // Avatar sincronizado com perfil
        try {
            const profile = JSON.parse(localStorage.getItem('fs_profile') || '{}');
            if (profile.avatar) {
                document.querySelectorAll('#nav-avatar').forEach(function (img) {
                    img.src = profile.avatar;
                });
            }
            if (profile.name) {
                document.querySelectorAll('#nav-avatar').forEach(function (img) {
                    const url = new URL(img.src, location.href);
                    if (url.hostname === 'ui-avatars.com') {
                        img.src = 'https://ui-avatars.com/api/?name=' +
                            encodeURIComponent(profile.name) +
                            '&background=06b6d4&color=fff';
                    }
                });
            }
        } catch (_) {}

        // Mudança de configurações na mesma aba (evento personalizado)
        window.addEventListener('settingsUpdated', function (e) {
            aplicar(e.detail || lerSettings());
        });

        // Mudança em outra aba (storage event)
        window.addEventListener('storage', function (e) {
            if (e.key === 'fs_settings' && e.newValue) {
                try { aplicar(JSON.parse(e.newValue)); } catch (_) {}
            }
        });
    });
})();
