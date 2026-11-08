// assets/js/profile.js
let ME = {};
async function loadProfile() {
  const { data } = await api('profile.get');
  ME = data;
  document.getElementById('profileAvatar').textContent = initials(data.name);
  document.getElementById('summaryName').textContent = data.name;
  document.getElementById('summaryEmail').textContent = data.email || '—';
  document.getElementById('summaryRole').textContent = data.role.replace('_', ' ');
  document.getElementById('summaryStatus').textContent = data.status;
  ['name','email','phone','member','role','status'].forEach(k => {
    const el = document.getElementById('v' + k[0].toUpperCase() + k.slice(1));
    if (el) el.textContent = ({name:data.name,email:data.email,phone:data.phone||'Not provided',member:data.member_since,role:data.role,status:data.status})[k] || '—';
  });
  document.getElementById('nameInput').value = data.name;
  document.getElementById('emailInput').value = data.email || '';
  document.getElementById('phoneInput').value = data.phone || '';
  document.getElementById('roleInput').value = data.role;
}

document.getElementById('profileForm').addEventListener('submit', async e => {
  e.preventDefault();
  const res = await api('profile.save', { data: {
    name: document.getElementById('nameInput').value.trim(),
    email: document.getElementById('emailInput').value.trim(),
    phone: document.getElementById('phoneInput').value.trim(),
  }}, 'POST');
  document.getElementById('message').textContent = res.ok ? 'Profile saved.' : (res.error || 'Failed.');
  if (res.ok) { await loadProfile(); closeEdit(); }
});

document.getElementById('logoutBtn').addEventListener('click', () => location.href = 'logout.php');
loadProfile();