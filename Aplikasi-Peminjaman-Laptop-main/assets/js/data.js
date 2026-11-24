/**
 * Database and Data Layer dengan Arsitektur Prepared Statement
 * Aplikasi Peminjaman Laptop Sekolah
 */

class PreparedStatement {
  constructor(sql) {
    this.sql = sql.trim();
    this.params = [];
    this.namedParams = {};
    this.parse();
  }

  parse() {
    const raw = this.sql;
    // Deteksi tipe query: INSERT, UPDATE, DELETE, SELECT
    if (/^INSERT\s+INTO\s+/i.test(raw)) {
      this.type = 'INSERT';
      const m = raw.match(/^INSERT\s+INTO\s+([a-zA-Z0-9_]+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i);
      if (!m) throw new Error('Format query INSERT tidak valid: ' + raw);
      this.table = m[1].toLowerCase();
      this.columns = m[2].split(',').map(s => s.trim());
      this.placeholders = m[3].split(',').map(s => s.trim());
    } else if (/^UPDATE\s+/i.test(raw)) {
      this.type = 'UPDATE';
      const m = raw.match(/^UPDATE\s+([a-zA-Z0-9_]+)\s+SET\s+(.+?)(?:\s+WHERE\s+(.+))?$/i);
      if (!m) throw new Error('Format query UPDATE tidak valid: ' + raw);
      this.table = m[1].toLowerCase();
      this.setClauses = m[2].split(',').map(s => {
        const parts = s.split('=').map(p => p.trim());
        return { col: parts[0], placeholder: parts[1] };
      });
      this.whereClause = m[3] ? m[3].trim() : null;
    } else if (/^DELETE\s+FROM\s+/i.test(raw)) {
      this.type = 'DELETE';
      const m = raw.match(/^DELETE\s+FROM\s+([a-zA-Z0-9_]+)(?:\s+WHERE\s+(.+))?$/i);
      if (!m) throw new Error('Format query DELETE tidak valid: ' + raw);
      this.table = m[1].toLowerCase();
      this.whereClause = m[2] ? m[2].trim() : null;
    } else if (/^SELECT\s+/i.test(raw)) {
      this.type = 'SELECT';
      const m = raw.match(/^SELECT\s+(.+?)\s+FROM\s+([a-zA-Z0-9_]+)(?:\s+WHERE\s+(.+))?$/i);
      if (!m) throw new Error('Format query SELECT tidak valid: ' + raw);
      this.selectCols = m[1].trim();
      this.table = m[2].toLowerCase();
      this.whereClause = m[3] ? m[3].trim() : null;
    } else {
      throw new Error('Query SQL tidak didukung dalam Prepared Statement: ' + raw);
    }
  }

  // Sanitasi parameter input untuk mencegah XSS & manipulasi data
  sanitizeValue(val) {
    if (val === null || val === undefined) return null;
    if (typeof val === 'number' || typeof val === 'boolean') return val;
    // Konversi string dan amankan
    let str = String(val).trim();
    // Hilangkan script tags dan pola injeksi yang berbahaya
    str = str.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
    return str;
  }

  // Mengikat parameter bernama (:param)
  bind(name, val) {
    const key = name.startsWith(':') ? name : ':' + name;
    this.namedParams[key] = this.sanitizeValue(val);
    return this;
  }

  // Evaluasi kondisi WHERE terhadap item data
  evaluateCondition(item, whereClause, paramMap) {
    if (!whereClause) return true;
    // Format standar: col = ? atau col = :param
    const condParts = whereClause.split(/\s+AND\s+/i);
    for (const part of condParts) {
      const match = part.match(/([a-zA-Z0-9_]+)\s*(=|!=|<>|>|<|>=|<=|LIKE)\s*([?:]\w*|\S+)/i);
      if (!match) continue;
      const col = match[1];
      const op = match[2].toUpperCase();
      const rawPlaceholder = match[3];

      let targetVal;
      if (rawPlaceholder === '?') {
        targetVal = paramMap.shift();
      } else if (rawPlaceholder.startsWith(':')) {
        targetVal = this.namedParams[rawPlaceholder] ?? paramMap[rawPlaceholder.slice(1)];
      } else {
        targetVal = rawPlaceholder.replace(/^['"]|['"]$/g, '');
      }

      const itemVal = item[col];
      if (op === '=' || op === '==') {
        if (String(itemVal) != String(targetVal)) return false;
      } else if (op === '!=' || op === '<>') {
        if (String(itemVal) == String(targetVal)) return false;
      } else if (op === 'LIKE') {
        const regex = new RegExp(String(targetVal).replace(/%/g, '.*'), 'i');
        if (!regex.test(String(itemVal))) return false;
      }
    }
    return true;
  }

  // Eksekusi prepared statement dengan parameter bound atau parameter array
  execute(params = []) {
    let paramList = Array.isArray(params) ? [...params] : [];
    if (!Array.isArray(params) && typeof params === 'object') {
      Object.keys(params).forEach(k => this.bind(k, params[k]));
    }

    // Catat ke Query Log untuk transparansi sistem
    DB.queryLog.push({
      sql: this.sql,
      type: this.type,
      table: this.table,
      params: Array.isArray(params) ? params : this.namedParams,
      timestamp: new Date().toISOString()
    });

    const items = DB.get(this.table, []);

    if (this.type === 'INSERT') {
      const newRecord = {};
      this.columns.forEach((col, idx) => {
        const placeholder = this.placeholders[idx];
        let val;
        if (placeholder === '?') {
          val = paramList.shift();
        } else if (placeholder.startsWith(':')) {
          val = this.namedParams[placeholder] ?? (typeof params === 'object' ? params[placeholder.slice(1)] : null);
        } else {
          val = placeholder.replace(/^['"]|['"]$/g, '');
        }
        newRecord[col] = this.sanitizeValue(val);
      });

      if (!newRecord.id) {
        newRecord.id = Date.now() + Math.floor(Math.random() * 1000);
      }

      items.push(newRecord);
      DB.set(this.table, items);
      return { success: true, insertId: newRecord.id, affectedRows: 1, data: newRecord };
    }

    if (this.type === 'UPDATE') {
      let affected = 0;
      const setCount = this.setClauses.filter(sc => sc.placeholder === '?').length;
      const whereParams = paramList.slice(setCount);

      const updatedList = items.map(item => {
        if (this.evaluateCondition(item, this.whereClause, [...whereParams])) {
          const updated = { ...item };
          let setParamIdx = 0;
          this.setClauses.forEach((sc) => {
            let val;
            if (sc.placeholder === '?') {
              val = paramList[setParamIdx++];
            } else if (sc.placeholder.startsWith(':')) {
              val = this.namedParams[sc.placeholder] ?? (typeof params === 'object' ? params[sc.placeholder.slice(1)] : null);
            } else {
              val = sc.placeholder.replace(/^['"]|['"]$/g, '');
            }
            if (val !== undefined) {
              updated[sc.col] = this.sanitizeValue(val);
            }
          });
          affected++;
          return updated;
        }
        return item;
      });

      DB.set(this.table, updatedList);
      return { success: true, affectedRows: affected };
    }

    if (this.type === 'DELETE') {
      const pMap = [...paramList];
      const initialCount = items.length;
      const remaining = items.filter(item => !this.evaluateCondition(item, this.whereClause, [...pMap]));
      const affected = initialCount - remaining.length;
      DB.set(this.table, remaining);
      return { success: true, affectedRows: affected };
    }

    if (this.type === 'SELECT') {
      const pMap = [...paramList];
      const matched = items.filter(item => this.evaluateCondition(item, this.whereClause, [...pMap]));
      return { success: true, rows: matched, count: matched.length };
    }

    return { success: false, error: 'Tipe operasi tidak dikenal' };
  }
}

const DB = {
  queryLog: [],

  get(key, fallback = []) {
    try {
      const item = localStorage.getItem(key);
      return item ? JSON.parse(item) : fallback;
    } catch {
      return fallback;
    }
  },

  set(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
  },

  uid(prefix = 'ID') {
    return prefix + Date.now().toString(36).toUpperCase();
  },

  // Prepared Statement Factory
  prepare(sql) {
    return new PreparedStatement(sql);
  },

  // Fitur Transaksi Atomik Sederhana
  _txSnapshot: null,
  beginTransaction() {
    this._txSnapshot = {
      users: localStorage.getItem('users'),
      siswa: localStorage.getItem('siswa'),
      petugas: localStorage.getItem('petugas'),
      laptops: localStorage.getItem('laptops'),
      peminjaman: localStorage.getItem('peminjaman'),
      pengembalian: localStorage.getItem('pengembalian')
    };
  },
  commit() {
    this._txSnapshot = null;
  },
  rollback() {
    if (this._txSnapshot) {
      Object.keys(this._txSnapshot).forEach(k => {
        if (this._txSnapshot[k] !== null) localStorage.setItem(k, this._txSnapshot[k]);
      });
      this._txSnapshot = null;
    }
  }
};

const seed = () => {
  if (!localStorage.getItem('users')) DB.set('users', [
    {id:1, username:'admin', password:'admin123', role:'admin', name:'Administrator'},
    {id:2, username:'petugas', password:'petugas123', role:'petugas', name:'Budi Santoso'},
    {id:3, username:'siswa', password:'siswa123', role:'siswa', name:'Andi Pratama'}
  ]);

  if (!localStorage.getItem('siswa')) DB.set('siswa', [
    {id:1, nis:'2026001', name:'Andi Pratama', kelas:'XII RPL 1', jurusan:'RPL', username:'siswa', status:'Aktif'},
    {id:2, nis:'2026002', name:'Siti Aulia', kelas:'XII RPL 1', jurusan:'RPL', username:'sitiaulia', status:'Aktif'},
    {id:3, nis:'2026003', name:'Rizky Maulana', kelas:'XII RPL 2', jurusan:'RPL', username:'rizky', status:'Aktif'},
    {id:4, nis:'2026004', name:'Nadia Putri', kelas:'XII RPL 2', jurusan:'RPL', username:'nadia', status:'Aktif'},
    {id:5, nis:'2026005', name:'Fajar Hidayat', kelas:'XII TKJ 1', jurusan:'TKJ', username:'fajar', status:'Aktif'}
  ]);

  if (!localStorage.getItem('petugas')) DB.set('petugas', [
    {id:1, nip:'1989001', name:'Budi Santoso', username:'petugas', status:'Aktif'},
    {id:2, nip:'1989002', name:'Dewi Lestari', username:'dewi', status:'Aktif'}
  ]);

  if (!localStorage.getItem('laptops')) DB.set('laptops', [
    {id:1, code:'LT001', name:'Laptop 01', brand:'Lenovo', model:'ThinkPad E14', processor:'Core i5', ram:'8 GB', storage:'256 GB SSD', status:'Tersedia', condition:'Baik', location:'Lab RPL'},
    {id:2, code:'LT002', name:'Laptop 02', brand:'Acer', model:'Aspire 5', processor:'Core i5', ram:'8 GB', storage:'512 GB SSD', status:'Dipinjam', condition:'Baik', location:'Lab RPL'},
    {id:3, code:'LT003', name:'Laptop 03', brand:'ASUS', model:'VivoBook 14', processor:'Core i3', ram:'8 GB', storage:'256 GB SSD', status:'Tersedia', condition:'Baik', location:'Lab RPL'},
    {id:4, code:'LT004', name:'Laptop 04', brand:'HP', model:'14s', processor:'Core i5', ram:'8 GB', storage:'512 GB SSD', status:'Maintenance', condition:'Perlu diperiksa', location:'Ruang Teknisi'},
    {id:5, code:'LT005', name:'Laptop 05', brand:'Lenovo', model:'IdeaPad Slim 3', processor:'Ryzen 5', ram:'8 GB', storage:'512 GB SSD', status:'Tersedia', condition:'Baik', location:'Lab RPL'},
    {id:6, code:'LT006', name:'Laptop 06', brand:'Acer', model:'TravelMate', processor:'Core i5', ram:'8 GB', storage:'256 GB SSD', status:'Rusak', condition:'Rusak', location:'Ruang Teknisi'},
    {id:7, code:'LT007', name:'Laptop 07', brand:'ASUS', model:'ExpertBook', processor:'Core i5', ram:'16 GB', storage:'512 GB SSD', status:'Tersedia', condition:'Baik', location:'Lab RPL'},
    {id:8, code:'LT008', name:'Laptop 08', brand:'HP', model:'ProBook', processor:'Core i5', ram:'8 GB', storage:'512 GB SSD', status:'Tersedia', condition:'Baik', location:'Lab RPL'},
    {id:9, code:'LT009', name:'Laptop 09', brand:'Lenovo', model:'V14', processor:'Core i3', ram:'8 GB', storage:'256 GB SSD', status:'Dipinjam', condition:'Baik', location:'Lab RPL'},
    {id:10, code:'LT010', name:'Laptop 10', brand:'Acer', model:'Aspire 3', processor:'Core i3', ram:'8 GB', storage:'256 GB SSD', status:'Tersedia', condition:'Baik', location:'Lab RPL'}
  ]);

  if (!localStorage.getItem('peminjaman')) DB.set('peminjaman', [
    {id:1, code:'PJM001', studentId:1, laptopId:2, requestDate:'2026-09-10', borrowDate:'2026-09-11', returnPlan:'2026-09-18', returnedDate:'', purpose:'Praktik pemrograman', status:'Dipinjam', note:''},
    {id:2, code:'PJM002', studentId:2, laptopId:3, requestDate:'2026-09-14', borrowDate:'', returnPlan:'2026-09-20', returnedDate:'', purpose:'Proyek RPL', status:'Menunggu', note:''},
    {id:3, code:'PJM003', studentId:3, laptopId:1, requestDate:'2026-08-20', borrowDate:'2026-08-21', returnPlan:'2026-08-25', returnedDate:'2026-08-25', purpose:'Uji aplikasi', status:'Dikembalikan', note:'Kondisi baik'},
    {id:4, code:'PJM004', studentId:4, laptopId:6, requestDate:'2026-08-28', borrowDate:'', returnPlan:'2026-09-02', returnedDate:'', purpose:'Praktik jaringan', status:'Ditolak', note:'Laptop sedang rusak'}
  ]);

  if (!localStorage.getItem('pengembalian')) DB.set('pengembalian', [
    {id:1, peminjamanId:3, code:'PJM003', studentId:3, laptopId:1, returnedDate:'2026-08-25', condition:'Baik', note:'Dikembalikan tepat waktu dalam kondisi baik', penalty:0, officerName:'Budi Santoso'}
  ]);
};
seed();

const currentUser = () => {
  const s = DB.get('session', null);
  return s;
};

const logout = () => {
  localStorage.removeItem('session');
  location.href = '../index.html';
};

const esc = (v = '') => String(v).replace(/[&<>"']/g, m => ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#039;'
}[m]));

const badge = (status) => {
  const map = {
    Tersedia: 'green',
    Berhasil: 'green',
    Aktif: 'green',
    Menunggu: 'yellow',
    Dipinjam: 'blue',
    Disetujui: 'blue',
    Dikembalikan: 'green',
    Ditolak: 'red',
    Rusak: 'red',
    Maintenance: 'orange'
  };
  const c = map[status] || 'gray';
  const colorClasses = {
    green: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
    yellow: 'bg-amber-100 text-amber-800 border border-amber-200',
    blue: 'bg-blue-100 text-blue-800 border border-blue-200',
    red: 'bg-rose-100 text-rose-800 border border-rose-200',
    orange: 'bg-orange-100 text-orange-800 border border-orange-200',
    gray: 'bg-slate-100 text-slate-800 border border-slate-200'
  };
  return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${colorClasses[c]}">${esc(status)}</span>`;
};

const formatDate = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
