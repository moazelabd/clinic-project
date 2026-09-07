// Shared helpers used by storages.js and doctors.js

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

async function apiPost(url, formDataOrObject) {
  let body;
  if (formDataOrObject instanceof FormData) {
    formDataOrObject.append('csrf_token', CSRF_TOKEN);
    body = formDataOrObject;
  } else {
    const obj = Object.assign({}, formDataOrObject, { csrf_token: CSRF_TOKEN });
    body = new URLSearchParams(obj);
  }
  const res = await fetch(url, { method: 'POST', body, credentials: 'same-origin' });
  let data;
  try {
    data = await res.json();
  } catch (e) {
    throw new Error('استجابة غير متوقعة من السيرفر');
  }
  if (!data.ok) {
    throw new Error(data.error || 'حصل خطأ');
  }
  return data;
}

async function apiGet(url) {
  const res = await fetch(url, { credentials: 'same-origin' });
  const data = await res.json();
  if (!data.ok) {
    throw new Error(data.error || 'حصل خطأ');
  }
  return data;
}

function showToast(msg) {
  let t = document.querySelector('.toast');
  if (!t) {
    t = document.createElement('div');
    t.className = 'toast';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.display = 'block';
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.style.display = 'none'; }, 2500);
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

document.addEventListener('click', (e) => {
  const btn = e.target.closest('.js-modal-cancel');
  if (btn) {
    closeModal(btn.dataset.modal);
  }
});