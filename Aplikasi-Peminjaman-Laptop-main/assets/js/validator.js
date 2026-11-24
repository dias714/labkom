/**
 * Form Validation Library & Helper
 * Sistem Peminjaman Laptop Sekolah
 */
const Validator = {
  // Aturan Validasi (Validation Rules)
  rules: {
    required(msg = 'Field ini wajib diisi') {
      return (val) => {
        if (val === null || val === undefined) return msg;
        const str = String(val).trim();
        return str.length > 0 ? true : msg;
      };
    },

    numeric(msg = 'Hanya boleh berisi angka') {
      return (val) => {
        if (!val) return true;
        return /^[0-9]+$/.test(String(val).trim()) ? true : msg;
      };
    },

    minLength(min, msg) {
      return (val) => {
        if (!val) return true;
        const str = String(val).trim();
        return str.length >= min ? true : (msg || `Minimal ${min} karakter`);
      };
    },

    maxLength(max, msg) {
      return (val) => {
        if (!val) return true;
        const str = String(val).trim();
        return str.length <= max ? true : (msg || `Maksimal ${max} karakter`);
      };
    },

    username(msg = 'Username hanya boleh huruf, angka, dan garis bawah (3-20 karakter)') {
      return (val) => {
        if (!val) return true;
        return /^[a-zA-Z0-9_]{3,20}$/.test(String(val).trim()) ? true : msg;
      };
    },

    dateNotBefore(refDateStr, msg = 'Tanggal tidak boleh sebelum hari ini') {
      return (val) => {
        if (!val) return true;
        const ref = new Date(refDateStr);
        ref.setHours(0, 0, 0, 0);
        const target = new Date(val);
        target.setHours(0, 0, 0, 0);
        return target >= ref ? true : msg;
      };
    },

    unique(collectionName, fieldName, excludeId = null, msg) {
      return (val) => {
        if (!val) return true;
        const str = String(val).trim().toLowerCase();
        const items = DB.get(collectionName) || [];
        const exists = items.some(item => {
          if (excludeId !== null && item.id == excludeId) return false;
          return String(item[fieldName] || '').trim().toLowerCase() === str;
        });
        return !exists ? true : (msg || `${fieldName.toUpperCase()} sudah digunakan`);
      };
    },

    inList(allowed = [], msg = 'Pilihan tidak valid') {
      return (val) => {
        if (!val) return true;
        return allowed.includes(String(val).trim()) ? true : msg;
      };
    },

    safeText(msg = 'Karakter tidak diizinkan') {
      return (val) => {
        if (!val) return true;
        return /<script|<iframe|javascript:/i.test(String(val)) ? msg : true;
      };
    }
  },

  // Validasi elemen tunggal
  validateField(input, fieldRules = []) {
    if (!input) return true;
    const value = input.value;
    let errorMsg = null;

    for (const rule of fieldRules) {
      const res = rule(value);
      if (res !== true) {
        errorMsg = res;
        break;
      }
    }

    const container = input.closest('div') || input.parentElement;
    let errEl = container.querySelector('.form-error');

    if (errorMsg) {
      input.classList.add('is-invalid');
      input.classList.remove('is-valid');
      if (!errEl) {
        errEl = document.createElement('p');
        errEl.className = 'form-error';
        container.appendChild(errEl);
      }
      errEl.innerHTML = `<i data-lucide="alert-circle" class="w-3.5 h-3.5 inline"></i> <span>${esc(errorMsg)}</span>`;
      if (window.lucide) lucide.createIcons();
      return false;
    } else {
      input.classList.remove('is-invalid');
      if (value && value.trim().length > 0) {
        input.classList.add('is-valid');
      } else {
        input.classList.remove('is-valid');
      }
      if (errEl) errEl.remove();
      return true;
    }
  },

  // Pasang listener validasi real-time pada form
  attach(form, schema) {
    if (!form || !schema) return;
    Object.keys(schema).forEach(name => {
      const input = form.querySelector(`[name="${name}"], #${name}`);
      if (!input) return;
      const rules = schema[name];

      const handler = () => Validator.validateField(input, rules);
      input.addEventListener('input', handler);
      input.addEventListener('change', handler);
      input.addEventListener('blur', handler);
    });
  },

  // Validasi seluruh form sebelum submit
  validateForm(form, schema) {
    if (!form || !schema) return true;
    let isAllValid = true;
    let firstInvalidInput = null;

    Object.keys(schema).forEach(name => {
      const input = form.querySelector(`[name="${name}"], #${name}`);
      if (!input) return;
      const rules = schema[name];
      const valid = Validator.validateField(input, rules);
      if (!valid) {
        isAllValid = false;
        if (!firstInvalidInput) firstInvalidInput = input;
      }
    });

    if (!isAllValid && firstInvalidInput) {
      firstInvalidInput.focus();
      if (window.toast) toast('Periksa kembali form Anda, terdapat isian yang belum valid.', 'error');
    }

    return isAllValid;
  }
};
