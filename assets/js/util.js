const API = 'api/index.php';
let CSRF = '';

function esc(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function money(n){return '₱' + Number(n||0).toLocaleString('en-US');}
function pad(n){return String(n).padStart(2,'0');}
function toISO(d){return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;}
function addDays(d,n){const c=new Date(d);c.setDate(c.getDate()+n);return c;}
function fmtDate(iso){if(!iso)return '—';const[y,m,d]=iso.split('-').map(Number);return new Date(y,m-1,d).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});}
function initials(n){return String(n||'').trim().split(/\s+/).map(w=>w[0]).slice(0,2).join('').toUpperCase()||'?';}
function statusCls(s){s=String(s).toLowerCase();
  if(['available','confirmed','paid'].includes(s))return'available';
  if(['pending','maintenance'].includes(s))return'pending';
  if(['occupied','checked in'].includes(s))return'occupied';
  if(s==='checked out')return'neutral';
  if(s==='overdue')return'cancelled';
  return'cancelled';}
function toast(msg,type='success'){
  const stack=document.getElementById('toastStack');
  const el=document.createElement('div');
  el.className='toast'+(type!=='success'?' '+type:'');
  el.textContent=msg;stack.appendChild(el);
  setTimeout(()=>{el.classList.add('out');setTimeout(()=>el.remove(),260);},2800);
}
async function api(action, payload = null, method = 'GET') {
  const url = API + '?action=' + encodeURIComponent(action);
  const opts = { credentials: 'same-origin', headers: {} };
  if (payload || method === 'POST') {
    opts.method = 'POST';
    opts.headers['Content-Type'] = 'application/json';
    opts.headers['X-CSRF'] = CSRF;
    opts.body = JSON.stringify({ ...(payload || {}), csrf: CSRF });
  }
  const res = await fetch(url, opts);
  if (res.status === 401) { location.href = 'index.php'; return; }
  return res.json();
}