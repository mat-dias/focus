// ══════════════════════════════════════
//  PERFIL — Focus Study
//  perfil.js
// ══════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

  document.getElementById('current-date').innerText = new Date().toLocaleDateString('pt-BR', { weekday:'long', day:'numeric', month:'long' });

  // ── Load profile ──
  const profile = JSON.parse(localStorage.getItem('fs_profile') || '{}');
  const defaults = { name:'Usuário Focus', tag:'focado', bio:'Focado em crescer todos os dias.', goal:'', tz:'America/Sao_Paulo', email:'', avatar:'' };
  const p = { ...defaults, ...profile };

  function applyProfile() {
    document.getElementById('display-name').textContent = p.name;
    document.getElementById('display-tag').textContent = '@' + p.tag;
    document.getElementById('display-bio').textContent = p.bio;
    document.getElementById('inp-name').value = p.name;
    document.getElementById('inp-tag').value = p.tag;
    document.getElementById('inp-bio').value = p.bio;
    document.getElementById('inp-goal').value = p.goal;
    document.getElementById('inp-tz').value = p.tz;
    document.getElementById('inp-email').value = p.email;
    if (p.avatar) {
      document.getElementById('profile-avatar-img').src = p.avatar;
      const navAv = document.getElementById('nav-avatar');
      if (navAv) navAv.src = p.avatar;
    } else {
      const initials = encodeURIComponent(p.name.split(' ').slice(0,2).join('+'));
      const src = `https://ui-avatars.com/api/?name=${initials}&background=06b6d4&color=fff`;
      document.getElementById('profile-avatar-img').src = src;
    }

    // Stats from other modules
    const pomos = parseInt(localStorage.getItem('fs_pomo_total') || '0');
    document.getElementById('pstat-sessions').textContent = pomos;
    const habits = JSON.parse(localStorage.getItem('fs_habits') || '[]');
    document.getElementById('pstat-habits').textContent = habits.length;
    document.getElementById('pstat-streak').textContent = localStorage.getItem('fs_streak') || '0';
  }

  applyProfile();

  // ── Avatar upload ──
  document.getElementById('avatar-wrapper').addEventListener('click', () => document.getElementById('avatar-input').click());
  document.getElementById('avatar-edit-btn').addEventListener('click', e => { e.stopPropagation(); document.getElementById('avatar-input').click(); });

  document.getElementById('avatar-input').addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
      p.avatar = ev.target.result;
      document.getElementById('profile-avatar-img').src = ev.target.result;
      const navAv = document.getElementById('nav-avatar');
      if (navAv) navAv.src = ev.target.result;
      save();
      showToast('Foto atualizada!', 'success');
    };
    reader.readAsDataURL(file);
  });

  // ── Toggle password ──
  document.getElementById('toggle-pwd').addEventListener('click', () => {
    const inp = document.getElementById('inp-pwd');
    inp.type = inp.type === 'password' ? 'text' : 'password';
  });

  // ── Save personal ──
  document.getElementById('save-personal').addEventListener('click', () => {
    p.name = document.getElementById('inp-name').value.trim() || p.name;
    p.tag = document.getElementById('inp-tag').value.trim().replace(/[^a-z0-9_]/gi,'') || p.tag;
    p.bio = document.getElementById('inp-bio').value.trim();
    p.goal = document.getElementById('inp-goal').value;
    p.tz = document.getElementById('inp-tz').value;
    save();
    applyProfile();
    showFeedback('personal-feedback', '✓ Salvo!');
    showToast('Perfil atualizado!', 'success');
  });

  // ── Save security ──
  document.getElementById('save-security').addEventListener('click', () => {
    const email = document.getElementById('inp-email').value.trim();
    const pwd = document.getElementById('inp-pwd').value;
    const pwdC = document.getElementById('inp-pwd-confirm').value;

    if (pwd && pwd !== pwdC) {
      showFeedback('security-feedback', '✗ Senhas não conferem', true);
      showToast('As senhas não conferem!', 'error');
      return;
    }

    if (email) p.email = email;
    if (pwd) p.pwd = btoa(pwd); // basic obfuscation only
    save();
    document.getElementById('inp-pwd').value = '';
    document.getElementById('inp-pwd-confirm').value = '';
    showFeedback('security-feedback', '✓ Atualizado!');
    showToast('Credenciais atualizadas!', 'success');
  });

  function save() {
    localStorage.setItem('fs_profile', JSON.stringify(p));
  }

  function showFeedback(id, msg, isError = false) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.style.color = isError ? 'var(--pink)' : '#4ade80';
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 3000);
  }

  function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 3000);
  }
});
