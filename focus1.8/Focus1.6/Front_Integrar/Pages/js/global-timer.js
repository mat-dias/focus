// global-timer.js
// Responsável por vigiar o timer de Pomodoro e disparar a notificação globalmente
(function() {
    // Se estivermos na página inicial, o script 'inicialusuario.js' cuidará de tudo
    // com a atualização visual da tela. Portanto, não precisamos rodar este background lá.
    if (window.location.pathname.includes('inicialusuario.html') || window.location.pathname.endsWith('/')) {
        return;
    }

    // Solicita permissão de notificação se ainda não foi pedida
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }

    setInterval(() => {
        let isRunning = localStorage.getItem('fs_timer_running') === 'true';
        let endTime = parseInt(localStorage.getItem('fs_timer_end')) || 0;

        if (isRunning && endTime > 0) {
            let now = Date.now();
            let timeLeft = Math.round((endTime - now) / 1000);

            if (timeLeft <= 0) {
                // O Timer expirou enquanto o usuário estava nesta página (configurações, perfil, etc)
                localStorage.setItem('fs_timer_running', 'false');
                localStorage.removeItem('fs_timer_end');
                localStorage.setItem('fs_timer_left', '0');

                let currentLabel = localStorage.getItem('fs_timer_label') || 'Foco';
                let pomodoroCount = parseInt(localStorage.getItem('fs_pomo_count') || '0');

                if (currentLabel === 'Foco') {
                    pomodoroCount = Math.min(pomodoroCount + 1, 4);
                    if (pomodoroCount >= 4) pomodoroCount = 0;
                    localStorage.setItem('fs_pomo_count', pomodoroCount);
                }

                // Dispara a notificação
                if (Notification.permission === 'granted') {
                    new Notification('Focus Study ⏱️', {
                        body: `Sessão de ${currentLabel} concluída! Hora de ${currentLabel === 'Foco' ? 'descansar' : 'focar'}.`,
                        icon: '/Pages/images/poppy.png'
                    });
                }
            }
        }
    }, 1000);
})();
