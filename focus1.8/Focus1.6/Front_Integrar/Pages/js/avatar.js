function updateGlobalNavAvatar() {
    const navAvatar = document.getElementById('nav-avatar');
    if (!navAvatar) return;

    const cachedPhoto = sessionStorage.getItem('user_photo');
    const userName = sessionStorage.getItem('user_name') || 'Usuario';

    if (cachedPhoto) {
        navAvatar.src = cachedPhoto;
    }

    fetch('php/api_perfil.php')
        .then(async res => {
            if (!res.ok) {
                const errData = await res.json().catch(() => ({}));
                throw new Error(errData.error || "Erro na requisição de perfil");
            }
            return res.json();
        })
        .then(result => {
            if (result.success && result.data) {
                const p = result.data;
                const imgSrc = p.photo
                    ? 'php/uploads/' + p.photo
                    : `https://ui-avatars.com/api/?name=${encodeURIComponent(p.username)}&background=06b6d4&color=fff`;

                navAvatar.src = imgSrc;
                sessionStorage.setItem('user_photo', imgSrc);
                sessionStorage.setItem('user_name', p.username);
            }
        })
        .catch(err => {
            console.warn("Aviso Controlador de perfil:", err.message);
            
            // 🛡️ SE DER ERRO (Ex: Perfil Não Encontrado), gera um Fallback seguro para não quebrar a tela
            if (!navAvatar.src || navAvatar.src.includes('undefined')) {
                navAvatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=06b6d4&color=fff`;
            }
        });
}

document.addEventListener('DOMContentLoaded', updateGlobalNavAvatar);