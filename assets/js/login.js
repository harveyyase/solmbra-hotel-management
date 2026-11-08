const loginForm = document.getElementById('loginForm');
const loginMsg  = document.getElementById('loginMessage');
const loginBtn  = document.getElementById('loginBtn');
const passInput = document.getElementById('loginPass');
const toggleBtn = document.getElementById('togglePin');

toggleBtn.addEventListener('click', function () {
  const icon = this.querySelector('i');
  if (passInput.type === 'password') {
    passInput.type = 'text';
    icon.className = 'fas fa-eye';
  } else {
    passInput.type = 'password';
    icon.className = 'fas fa-eye-slash';
  }
});

loginForm.addEventListener('submit', e => {
  // Let PHP handle auth — just show the spinner while POST happens
  loginBtn.classList.add('loading');
  loginBtn.disabled = true;
});