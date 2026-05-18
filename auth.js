function toggleNav() {
  document.getElementById('nav-ul').classList.toggle('active');
}

function switchTab(tab) {
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  document.getElementById('form-' + tab).classList.add('active');
}

function toggleType() {
  const type = document.getElementById('acc-type').value;
  document.getElementById('individual-fields').style.display = type === 'individual' ? 'block' : 'none';
  document.getElementById('company-fields').style.display = type === 'company' ? 'block' : 'none';
}

function setError(inputId, msgId, message) {
  const input = document.getElementById(inputId);
  const msg = document.getElementById(msgId);
  if (!input || !msg) return;
  input.classList.add('invalid');
  input.classList.remove('valid');
  msg.textContent = message;
  msg.classList.add('visible');
}

function clearError(inputId, msgId) {
  const input = document.getElementById(inputId);
  const msg = document.getElementById(msgId);
  if (!input || !msg) return;
  input.classList.remove('invalid');
  input.classList.add('valid');
  msg.classList.remove('visible');
}

function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function handleLogin() {
  let valid = true;

  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;

  if (!email) {
    setError('login-email', 'err-login-email', '⚠️ Email adresi boş bırakılamaz.');
    valid = false;
  } else if (!validateEmail(email)) {
    setError('login-email', 'err-login-email', '⚠️ Geçerli bir email adresi girin.');
    valid = false;
  } else {
    clearError('login-email', 'err-login-email');
  }

  if (!password) {
    setError('login-password', 'err-login-password', '⚠️ Şifre boş bırakılamaz.');
    valid = false;
  } else if (password.length < 6) {
    setError('login-password', 'err-login-password', '⚠️ Şifre en az 6 karakter olmalıdır.');
    valid = false;
  } else {
    clearError('login-password', 'err-login-password');
  }

  if (!valid) return;

  const btn = document.querySelector('#form-login .auth-submit');
  btn.textContent = 'Logging in...';
  btn.disabled = true;

  const formData = new FormData();
  formData.append('email', email);
  formData.append('password', password);

  fetch('login.php', {
    method: 'POST',
    body: formData
  })
  .then(async response => {
    const text = await response.text();

    if (response.redirected) {
      window.location.href = response.url;
      return;
    }

    if (text.includes('dashboard')) {
      window.location.href = 'dashboard.html';
      return;
    }

    setError('login-email', 'err-login-email', '⚠️ ' + text);
    setError('login-password', 'err-login-password', '⚠️ ' + text);

    btn.textContent = 'Login →';
    btn.disabled = false;
  })
  .catch(() => {
    setError('login-email', 'err-login-email', '⚠️ Sunucuya bağlanılamadı.');
    btn.textContent = 'Login →';
    btn.disabled = false;
  });
}
function handleRegister() {
  let valid = true;
  const type = document.getElementById('acc-type').value;
  const email = document.getElementById('reg-email').value.trim();
  const phone = document.getElementById('reg-phone').value.trim();
  const password = document.getElementById('reg-password').value;

  if (type === 'individual') {
    const fname = document.getElementById('reg-firstname').value.trim();
    const lname = document.getElementById('reg-lastname').value.trim();

    if (!fname) {
      setError('reg-firstname', 'err-reg-firstname', '⚠️ Ad boş bırakılamaz.');
      valid = false;
    } else clearError('reg-firstname', 'err-reg-firstname');

    if (!lname) {
      setError('reg-lastname', 'err-reg-lastname', '⚠️ Soyad boş bırakılamaz.');
      valid = false;
    } else clearError('reg-lastname', 'err-reg-lastname');
  }

  if (type === 'company') {
    const company = document.getElementById('reg-company').value.trim();
    const tax = document.getElementById('reg-tax').value.trim();

    if (!company) {
      setError('reg-company', 'err-reg-company', '⚠️ Şirket adı boş bırakılamaz.');
      valid = false;
    } else clearError('reg-company', 'err-reg-company');

    if (!tax || !/^\d{10}$/.test(tax)) {
      setError('reg-tax', 'err-reg-tax', '⚠️ Vergi numarası 10 haneli olmalıdır.');
      valid = false;
    } else clearError('reg-tax', 'err-reg-tax');
  }

  if (!email || !validateEmail(email)) {
    setError('reg-email', 'err-reg-email', '⚠️ Geçerli bir email adresi girin.');
    valid = false;
  } else clearError('reg-email', 'err-reg-email');

  if (!phone || phone.replace(/\s/g, '').length < 10) {
    setError('reg-phone', 'err-reg-phone', '⚠️ Geçerli bir telefon numarası girin.');
    valid = false;
  } else clearError('reg-phone', 'err-reg-phone');

  if (!password || password.length < 6) {
    setError('reg-password', 'err-reg-password', '⚠️ Şifre en az 6 karakter olmalıdır.');
    valid = false;
  } else clearError('reg-password', 'err-reg-password');

  if (!valid) return;

  const btn = document.querySelector('#form-register .auth-submit');
  btn.textContent = 'Creating account...';
  btn.disabled = true;

  const formData = new FormData();
  formData.append('account_type', type);
  formData.append('email', email);
  formData.append('phone', phone);
  formData.append('password', password);

  if (type === 'individual') {
    formData.append('first_name', document.getElementById('reg-firstname').value.trim());
    formData.append('last_name', document.getElementById('reg-lastname').value.trim());
  }

  if (type === 'company') {
    formData.append('company_name', document.getElementById('reg-company').value.trim());
    formData.append('tax_no', document.getElementById('reg-tax').value.trim());
    formData.append('contact_person', document.getElementById('reg-company').value.trim());
  }

  fetch('register.php', {
    method: 'POST',
    body: formData
  })
  .then(async response => {
    const text = await response.text();

    if (response.redirected) {
      window.location.href = response.url;
      return;
    }

    if (text.trim() === '') {
      window.location.href = 'dashboard.html';
      return;
    }

    if (text.includes('dashboard')) {
      window.location.href = 'dashboard.html';
      return;
    }

    setError('reg-email', 'err-reg-email', '⚠️ ' + text);
    btn.textContent = 'Create Account →';
    btn.disabled = false;
  })
  .catch(() => {
    setError('reg-email', 'err-reg-email', '⚠️ Sunucuya bağlanılamadı. Lütfen tekrar deneyin.');
    btn.textContent = 'Create Account →';
    btn.disabled = false;
  });
}

const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('tab') === 'register') switchTab('register');