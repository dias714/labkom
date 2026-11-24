const guard = (roles = []) => {
  const u = currentUser();
  if (!u) {
    location.href = '../index.html';
    return null;
  }
  if (roles.length && !roles.includes(u.role)) {
    location.href = '../index.html';
    return null;
  }
  return u;
};

const sidebar = (role) => {
  const menus = {
    admin: [
      ['dashboard.html', 'LayoutDashboard', 'Dashboard'],
      ['siswa.html', 'Users', 'Data Siswa'],
      ['petugas.html', 'UserCog', 'Data Petugas'],
      ['users.html', 'ShieldCheck', 'Data User'],
      ['laptop.html', 'Laptop', 'Data Laptop'],
      ['peminjaman.html', 'ClipboardList', 'Data Peminjaman'],
      ['pengembalian.html', 'RotateCcw', 'Pengembalian'],
      ['laporan.html', 'FileText', 'Laporan']
    ],
    petugas: [
      ['dashboard.html', 'LayoutDashboard', 'Dashboard'],
      ['laptop.html', 'Laptop', 'Data Laptop'],
      ['peminjaman.html', 'ClipboardList', 'Permintaan Peminjaman'],
      ['pengembalian.html', 'RotateCcw', 'Pengembalian'],
      ['aktif.html', 'Clock3', 'Peminjaman Aktif'],
      ['riwayat.html', 'History', 'Riwayat']
    ],
    siswa: [
      ['dashboard.html', 'LayoutDashboard', 'Dashboard'],
      ['laptop.html', 'Laptop', 'Laptop'],
      ['peminjaman.html', 'ClipboardList', 'Ajukan Peminjaman'],
      ['status.html', 'CircleCheck', 'Status Peminjaman'],
      ['aktif.html', 'Clock3', 'Peminjaman Aktif'],
      ['riwayat.html', 'History', 'Riwayat']
    ]
  };
  return menus[role];
};

const layout = (title, role, active) => {
  const u = currentUser();
  const items = sidebar(role);
  document.body.innerHTML = `
  <div id="overlay" class="overlay fixed inset-0 bg-slate-950/40 z-40 md:hidden"></div>
  <div class="min-h-screen flex bg-slate-50">
    <aside id="sidebar" class="sidebar bg-white border-r border-slate-200 flex flex-col shrink-0">
      <div class="h-16 px-5 flex items-center gap-3 border-b border-slate-100">
        <div class="h-9 w-9 rounded-xl bg-teal-700 text-white grid place-items-center"><i data-lucide="laptop"></i></div>
        <div><div class="font-bold text-slate-900 text-sm">Lab Laptop</div><div class="text-[11px] text-slate-400">Peminjaman Sekolah</div></div>
      </div>
      <nav class="p-3 space-y-1 flex-1">${items.map(x => `<a href="${x[0]}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium ${active === x[2] ? 'bg-blue-50 text-teal-800 font-semibold' : 'text-slate-600 hover:bg-slate-50'}"><i data-lucide="${x[1]}" class="w-5 h-5"></i>${x[2]}</a>`).join('')}</nav>
      <div class="p-3 border-t border-slate-100 space-y-2">
        <button onclick="showPreparedLog()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition">
          <span class="flex items-center gap-2"><i data-lucide="database" class="w-4 h-4"></i>Prepared Statement</span>
          <span class="px-1.5 py-0.5 rounded-full bg-emerald-200 text-emerald-900 text-[10px] font-bold" id="logCountBadge">${DB.queryLog.length}</span>
        </button>
        <div class="p-3 rounded-xl bg-slate-50"><p class="font-semibold text-sm text-slate-800">${esc(u.name)}</p><p class="text-xs text-slate-500 capitalize">${u.role}</p></div>
        <button onclick="logout()" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-red-600 hover:bg-red-50"><i data-lucide="log-out" class="w-5 h-5"></i>Logout</button>
      </div>
    </aside>
    <section class="page flex-1">
      <header class="h-16 bg-white border-b border-slate-200 px-4 md:px-7 flex items-center justify-between sticky top-0 z-30">
        <div class="flex items-center gap-3">
          <button id="menuBtn" class="icon-btn md:hidden" aria-label="Buka menu navigasi"><i data-lucide="menu"></i></button>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="font-bold text-slate-900 text-lg sm:text-xl">${title}</h1>
              <span class="badge-prepared"><i data-lucide="shield-check" class="w-3 h-3 text-emerald-600"></i>Validated & Prepared</span>
            </div>
            <p class="hidden sm:block text-xs text-slate-400">Sistem Peminjaman Laptop Sekolah</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button id="notificationBtn" class="icon-btn relative" title="Notifikasi"><i data-lucide="bell"></i><span id="notificationDot" class="hidden absolute top-1 right-1 h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white"></span></button>
          <div class="hidden sm:block text-right"><p class="text-xs font-semibold text-slate-700">${esc(u.name)}</p><p class="text-[11px] text-slate-400 capitalize">${u.role}</p></div>
          <div class="h-9 w-9 rounded-full bg-teal-100 text-teal-700 grid place-items-center text-sm font-bold">${esc(u.name.charAt(0))}</div>
        </div>
      </header>
      <main id="content" class="p-4 md:p-7 max-w-[1600px] mx-auto"></main>
    </section>
  </div>
  <div id="modalRoot"></div><div id="toastRoot" class="fixed right-4 bottom-4 z-[100] space-y-2"></div>`;

  lucide.createIcons();

  const toggleMenu = () => {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
  };
  const closeMenu = () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
  };
  document.getElementById('menuBtn')?.addEventListener('click', toggleMenu);
  document.getElementById('overlay')?.addEventListener('click', closeMenu);

  const pending = DB.get('peminjaman').filter(x => x.status === 'Menunggu').length;
  document.getElementById('notificationDot')?.classList.toggle('hidden', pending === 0);
  document.getElementById('notificationBtn')?.addEventListener('click', () => modal(
    'Notifikasi',
    pending
      ? `<div class="flex items-start gap-3 rounded-xl bg-amber-50 p-4"><i data-lucide="clock-3" class="text-amber-600 shrink-0"></i><div><p class="font-semibold text-slate-800">${pending} permintaan menunggu</p><p class="text-sm text-slate-500 mt-1">Ada pengajuan peminjaman yang perlu ditinjau dan diproses oleh petugas.</p></div></div>`
      : '<div class="text-center py-6"><i data-lucide="check-circle" class="mx-auto text-emerald-500"></i><p class="font-semibold mt-3">Semua aman</p><p class="text-sm text-slate-500 mt-1">Tidak ada notifikasi baru.</p></div>',
    '<button onclick="closeModal()" class="btn-secondary">Tutup</button>'
  ));

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
      e.preventDefault();
      document.querySelector('input[placeholder*="Cari"]')?.focus();
    }
  });
};

const showPreparedLog = () => {
  const log = DB.queryLog || [];
  const logHtml = log.length === 0
    ? '<p class="text-slate-400 text-sm text-center py-6">Belum ada query Prepared Statement yang dieksekusi pada sesi ini.</p>'
    : `<div class="space-y-3 max-h-[60vh] overflow-y-auto text-xs font-mono">
        ${log.slice().reverse().map((entry, idx) => `
          <div class="bg-slate-900 text-slate-200 p-3.5 rounded-xl border border-slate-800">
            <div class="flex justify-between items-center text-slate-400 mb-1.5 pb-1.5 border-b border-slate-800 font-sans text-[11px]">
              <span class="font-bold text-emerald-400">#${log.length - idx} [${entry.type}] ${entry.table}</span>
              <span>${new Date(entry.timestamp).toLocaleTimeString('id-ID')}</span>
            </div>
            <p class="text-emerald-300 font-semibold mb-1">${esc(entry.sql)}</p>
            <div class="text-slate-400 mt-1">
              <span class="text-amber-300">Bound Params:</span> ${esc(JSON.stringify(entry.params))}
            </div>
          </div>
        `).join('')}
      </div>`;

  modal(
    'Log Prepared Statement (Keamanan & Parameter Binding)',
    `<div>
      <p class="text-xs text-slate-500 mb-3">Setiap operasi INSERT, UPDATE, dan DELETE pada sistem telah melewati arsitektur <b>Prepared Statement</b> dengan parameter sanitization dan type-binding untuk mencegah serangan XSS serta injeksi data.</p>
      ${logHtml}
    </div>`,
    `<button onclick="DB.queryLog=[]; document.getElementById('logCountBadge').textContent='0'; closeModal(); toast('Log dibersihkan');" class="btn-secondary text-red-600">Bersihkan Log</button><button onclick="closeModal()" class="btn-primary">Tutup</button>`
  );
};

const toast = (msg, type = 'success') => {
  const el = document.createElement('div');
  el.className = 'toast bg-white border border-slate-200 shadow-lg rounded-xl px-4 py-3 flex items-center gap-3 text-sm';
  el.innerHTML = `<i data-lucide="${type === 'success' ? 'check-circle' : 'circle-alert'}" class="w-5 h-5 ${type === 'success' ? 'text-emerald-600' : 'text-red-600'}"></i><span class="font-medium text-slate-800">${esc(msg)}</span>`;
  document.getElementById('toastRoot')?.appendChild(el);
  if (window.lucide) lucide.createIcons();
  setTimeout(() => el.remove(), 2800);
};

const modal = (title, body, buttons = '') => {
  document.getElementById('modalRoot').innerHTML = `
    <div id="modal" class="modal-backdrop fixed inset-0 z-[90] flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
          <h3 class="font-bold text-slate-900">${title}</h3>
          <button onclick="closeModal()" class="icon-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="p-5">${body}</div>
        ${buttons ? `<div class="px-5 py-4 border-t border-slate-100 flex justify-end gap-2">${buttons}</div>` : ''}
      </div>
    </div>`;
  if (window.lucide) lucide.createIcons();
};

const closeModal = () => {
  const root = document.getElementById('modalRoot');
  if (root) root.innerHTML = '';
};

const statCard = (label, value, icon, sub = '') => `
  <div class="bg-white border border-slate-200 rounded-2xl p-5">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-slate-500">${label}</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">${value}</p>
        ${sub ? `<p class="text-xs text-slate-400 mt-1">${sub}</p>` : ''}
      </div>
      <div class="h-11 w-11 rounded-xl bg-teal-50 text-teal-700 grid place-items-center"><i data-lucide="${icon}"></i></div>
    </div>
  </div>`;
