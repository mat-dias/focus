/* instrucoes.js
 *
 * Offsets de scroll — devem ser consistentes com o CSS:
 *   Header fixo  : 65px
 *   Tab-nav sticky: 53px
 *   Total + respiro: 140px  (usado em scroll-margin-top e aqui)
 */

function switchTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + id).classList.add('active');
    btn.classList.add('active');

    /* Rola até o início de .inst-body, abaixo dos dois elementos fixos/sticky */
    const instBody = document.querySelector('.inst-body');
    const targetY = instBody.getBoundingClientRect().top + window.scrollY - 118;
    window.scrollTo({ top: targetY, behavior: 'smooth' });
}

/* Scroll suave para âncoras internas dos painéis (links da sidebar) */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.inst-body')?.addEventListener('click', (e) => {
        const anchor = e.target.closest('a[href^="#"]');
        if (!anchor) return;

        const href = anchor.getAttribute('href');
        if (!href || href === '#') return;

        const target = document.querySelector(href);
        if (!target) return;
        e.preventDefault();
        e.stopImmediatePropagation();

        /* Mesmo valor de scroll-margin-top definido no CSS */
        const OFFSET = 65 + 53 + 22; /* header + tab-nav + respiro = 140px */
        const top = target.getBoundingClientRect().top + window.scrollY - OFFSET;
        window.scrollTo({ top, behavior: 'smooth' });

    }, true);
});

function switchTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + id).classList.add('active');
    btn.classList.add('active');
    const instBody = document.querySelector('.inst-body');
    const targetY = instBody.getBoundingClientRect().top + window.scrollY - 118;
    window.scrollTo({ top: targetY, behavior: 'smooth' });
}