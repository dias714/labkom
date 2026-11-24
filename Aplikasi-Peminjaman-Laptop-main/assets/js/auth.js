/**
 * Otentikasi dan Login dengan Validasi & Prepared Statement
 */
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('loginForm');
  if (!form) return;

  const loginSchema = {
    username: [
      Validator.rules.required('Username atau NIS wajib diisi'),
      Validator.rules.minLength(3, 'Username minimal 3 karakter')
    ],
    password: [
      Validator.rules.required('Password wajib diisi'),
      Validator.rules.minLength(4, 'Password minimal 4 karakter')
    ]
  };

  Validator.attach(form, loginSchema);

  form.addEventListener('submit', e => {
    e.preventDefault();

    if (!Validator.validateForm(form, loginSchema)) {
      return;
    }

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    const error = document.getElementById('loginError');

    // Query Autentikasi dengan PREPARED STATEMENT
    const stmt = DB.prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    const result = stmt.execute([username, password]);
    const user = result.rows && result.rows.length > 0 ? result.rows[0] : null;

    if (!user) {
      error.innerHTML = `<i data-lucide="alert-triangle" class="w-4 h-4 inline mr-1"></i> Username atau password tidak sesuai. Silakan gunakan akun demo di bawah.`;
      error.classList.remove('hidden');
      if (window.lucide) lucide.createIcons();
      return;
    }

    error.classList.add('hidden');

    // Simpan data sesi yang bersih
    DB.set('session', {
      id: user.id,
      username: user.username,
      name: user.name,
      role: user.role,
      loginAt: new Date().toISOString()
    });

    const targetUrl = user.role === 'admin' 
      ? 'admin/dashboard.html' 
      : user.role === 'petugas' 
        ? 'petugas/dashboard.html' 
        : 'siswa/dashboard.html';

    location.href = targetUrl;
  });
});
