let activeGroupId = null;

document.addEventListener('DOMContentLoaded', () => {
  loadGroups();
  loadPending();

  document.getElementById('btnCreateGroup').addEventListener('click', async () => {
    const name = document.getElementById('newGroupName').value.trim();
    if (!name) { showToast('اكتب اسم الجروب'); return; }
    try {
      const res = await apiPost('api/groups_api.php?action=create', { name });
      showToast('اتعمل! الكود: ' + res.join_code);
      document.getElementById('newGroupName').value = '';
      loadGroups();
    } catch (e) { showToast(e.message); }
  });
});

async function loadGroups() {
  try {
    const data = await apiGet('api/groups_api.php?action=list');
    activeGroupId = data.active_group_id;
    const list = document.getElementById('groupsList');
    list.innerHTML = '';
    if (data.groups.length === 0) {
      list.innerHTML = '<div class="empty-hint" style="margin-top:0;">مفيش جروبات لسه</div>';
    }
    data.groups.forEach(g => {
      const row = document.createElement('div');
      row.className = 'visit-row';
      const isActive = g.id === activeGroupId;
      row.innerHTML = `
        <span class="visit-date">${escapeHtml(g.name)} ${isActive ? '✅' : ''}</span>
        <span class="visit-note">الكود: <b>${escapeHtml(g.join_code)}</b></span>
        <button class="btn btn-sm rename-btn">تعديل الاسم</button>
        <button class="btn btn-sm btn-primary switch-btn">${isActive ? 'شغّال عليه' : 'اشتغل عليه'}</button>
        <button class="btn btn-sm btn-danger delete-btn">حذف الجروب</button>
      `;
      row.querySelector('.switch-btn').addEventListener('click', () => switchGroup(g.id));
      row.querySelector('.rename-btn').addEventListener('click', () => renameGroup(g.id, g.name));
      row.querySelector('.delete-btn').addEventListener('click', () => deleteGroup(g.id, g.name));
      list.appendChild(row);
    });
  } catch (e) { showToast(e.message); }
}

async function switchGroup(id) {
  try {
    await apiPost('api/groups_api.php?action=switch', { id });
    showToast('اتحول للجروب ده. البيانات في المخازن/الدكاتره هتبقى بتاعت الجروب ده دلوقتي.');
    loadGroups();
  } catch (e) { showToast(e.message); }
}

async function renameGroup(id, oldName) {
  const name = prompt('الاسم الجديد:', oldName);
  if (!name || !name.trim()) return;
  try {
    await apiPost('api/groups_api.php?action=rename', { id, name: name.trim() });
    loadGroups();
  } catch (e) { showToast(e.message); }
}

async function deleteGroup(id, name) {
  if (!confirm(`متأكد إنك عايز تمسح جروب "${name}"؟ هيتمسح كل المخازن والدكاتره اللي جواه نهائيًا.`)) return;
  try {
    await apiPost('api/groups_api.php?action=delete', { id });
    loadGroups();
  } catch (e) { showToast(e.message); }
}

async function loadPending() {
  try {
    const data = await apiGet('api/join_requests_api.php?action=list_pending');
    const list = document.getElementById('pendingList');
    list.innerHTML = '';
    if (data.requests.length === 0) {
      list.innerHTML = '<div class="empty-hint" style="margin-top:0;">مفيش طلبات معلّقة</div>';
    }
    data.requests.forEach(r => {
      const row = document.createElement('div');
      row.className = 'visit-row';
      row.innerHTML = `
        <span class="visit-date">${escapeHtml(r.username)}</span>
        <span class="visit-note">عايز ينضم لـ "${escapeHtml(r.group_name)}"</span>
        <button class="btn btn-sm btn-primary approve-btn">قبول</button>
        <button class="btn btn-sm btn-danger reject-btn">رفض</button>
      `;
      row.querySelector('.approve-btn').addEventListener('click', () => decide(r.id, true));
      row.querySelector('.reject-btn').addEventListener('click', () => decide(r.id, false));
      list.appendChild(row);
    });
  } catch (e) { showToast(e.message); }
}

async function decide(id, approve) {
  try {
    await apiPost('api/join_requests_api.php?action=decide', { id, approve: approve ? 1 : 0 });
    loadPending();
  } catch (e) { showToast(e.message); }
}
