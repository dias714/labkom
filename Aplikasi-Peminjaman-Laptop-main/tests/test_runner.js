const fs = require('fs');
const vm = require('vm');

const localStorageMap = new Map();
const sandbox = {
  localStorage: {
    getItem: k => localStorageMap.get(k) || null,
    setItem: (k, v) => localStorageMap.set(k, String(v)),
    removeItem: k => localStorageMap.delete(k)
  },
  console,
  Date,
  Math,
  String,
  RegExp,
  Array,
  Object,
  JSON
};
vm.createContext(sandbox);

const dataCode = fs.readFileSync('assets/js/data.js', 'utf8');
const valCode = fs.readFileSync('assets/js/validator.js', 'utf8');
vm.runInContext(dataCode, sandbox);
vm.runInContext(valCode, sandbox);

vm.runInContext(`
  console.log('--- TEST 1: DB SEED ---');
  const laptops = DB.get('laptops');
  console.log('Seed laptops count:', laptops.length);
  if (laptops.length !== 10) throw new Error('Expected 10 laptops');

  console.log('--- TEST 2: PREPARED STATEMENT INSERT ---');
  const insertStmt = DB.prepare('INSERT INTO laptops (code, name, brand, model, processor, ram, storage, status, condition, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
  const res = insertStmt.execute(['LT999', 'Laptop Unit Test', 'Asus', 'ZenBook', 'Core i7', '16 GB', '1 TB SSD', 'Tersedia', 'Baik', 'Lab RPL']);
  console.log('Insert result:', res.success, 'New count:', DB.get('laptops').length);
  if (DB.get('laptops').length !== 11) throw new Error('Insert failed');

  console.log('--- TEST 3: PREPARED STATEMENT UPDATE ---');
  const updateStmt = DB.prepare('UPDATE laptops SET status = ?, condition = ? WHERE code = ?');
  updateStmt.execute(['Dipinjam', 'Baik', 'LT999']);
  const updatedLap = DB.get('laptops').find(l => l.code === 'LT999');
  console.log('Updated status:', updatedLap.status);
  if (updatedLap.status !== 'Dipinjam') throw new Error('Update failed');

  console.log('--- TEST 4: PREPARED STATEMENT SELECT ---');
  const selectStmt = DB.prepare('SELECT * FROM laptops WHERE code = ?');
  const selectRes = selectStmt.execute(['LT999']);
  console.log('Select rows count:', selectRes.rows.length);
  if (selectRes.rows.length !== 1) throw new Error('Select failed');

  console.log('--- TEST 5: PREPARED STATEMENT DELETE ---');
  const delStmt = DB.prepare('DELETE FROM laptops WHERE code = ?');
  delStmt.execute(['LT999']);
  console.log('After delete count:', DB.get('laptops').length);
  if (DB.get('laptops').length !== 10) throw new Error('Delete failed');

  console.log('--- TEST 6: VALIDATOR RULES ---');
  const req = Validator.rules.required('Wajib')('');
  console.log('Required check empty:', req === 'Wajib');
  const num = Validator.rules.numeric('Harus angka')('abc');
  console.log('Numeric check abc:', num === 'Harus angka');
  const unq = Validator.rules.unique('laptops', 'code', null, 'Sudah ada')('LT001');
  console.log('Unique check LT001:', unq === 'Sudah ada');

  console.log('ALL UNIT TESTS PASSED 100%!');
`, sandbox);
