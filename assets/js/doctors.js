let currentDoctorId = null;
let currentDoctorData = null;

document.addEventListener('DOMContentLoaded', () => {
  loadDoctors();

  document.getElementById('btnAddDoctor').addEventListener('click', () => {
    document.getElementById('newDoctorName').value = '';
    document.getElementById('newDoctorImage').value = '';
    openModal('modalAddDoctor');
  });

  document.getElementById('confirmAddDoctor').addEventListener('click', async () => {
    const name = document.getElementById('newDoctorName').value.trim();
    if (!name) { showToast('اكتب اسم الدكتور'); return; }
    const fd = new FormData();
    fd.append('name', name);
    const img = document.getElementById('newDoctorImage').files[0];
    if (img) fd.append('image', img);
    try {
      await apiPost('api/doctors_api.php?action=add', fd);
      closeModal('modalAddDoctor');
      loadDoctors();
    } catch (e) { showToast(e.message); }
  });

  document.getElementById('btnDeleteDoctor').addEventListener('click', async () => {
    if (!currentDoctorId) return;
    if (!confirm('متأكد إنك عايز تمسح الدكتور ده؟')) return;
    try {
      await apiPost('api/doctors_api.php?action=delete', { id: currentDoctorId });
      currentDoctorId = null;
      document.getElementById('doctorContent').style.display = 'none';
      document.getElementById('noDoctorSelected').style.display = 'block';
      loadDoctors();
    } catch (e) { showToast(e.message); }
  });

  document.getElementById('doctorTitle').addEventListener('change', async (e) => {
    if (!currentDoctorId) return;
    try {
      await apiPost('api/doctors_api.php?action=update_title', { id: currentDoctorId, title: e.target.value });
      showToast('تم الحفظ');
    } catch (e2) { showToast(e2.message); }
  });

  document.getElementById('btnToggleBlacklist').addEventListener('click', () => {
    if (!currentDoctorData) return;
    if (currentDoctorData.is_blacklisted == 1) {
      // remove from blacklist directly, no confirmation needed for removing
      removeFromBlacklist();
    } else {
      document.getElementById('blacklistReasonInput').value = '';
      openModal('modalBlacklist');
    }
  });

  document.getElementById('confirmBlacklist').addEventListener('click', async () => {
    const reason = document.getElementById('blacklistReasonInput').value.trim();
    try {
      await apiPost('api/doctors_api.php?action=toggle_blacklist', {
        id: currentDoctorId, blacklisted: 1, reason
      });
      closeModal('modalBlacklist');
      loadDoctorDetail(currentDoctorId);
      loadDoctors();
    } catch (e) { showToast(e.message); }
  });

  document.getElementById('blacklistReason').addEventListener('change', async (e) => {
    if (!currentDoctorId || currentDoctorData.is_blacklisted != 1) return;
    try {
      await apiPost('api/doctors_api.php?action=toggle_blacklist', {
        id: currentDoctorId, blacklisted: 1, reason: e.target.value
      });
      showToast('تم الحفظ');
    } catch (e2) { showToast(e2.message); }
  });

  document.getElementById('btnAddVisit').addEventListener('click', () => {
    document.getElementById('visitDate').value = '';
    openModal('modalAddVisit');
  });

  document.getElementById('confirmAddVisit').addEventListener('click', async () => {
    const date = document.getElementById('visitDate').value;
    if (!date) { showToast('اختار التاريخ'); return; }
    try {
      await apiPost('api/visits_api.php?action=add', { doctor_id: currentDoctorId, visit_date: date });
      closeModal('modalAddVisit');
      loadDoctorDetail(currentDoctorId);
    } catch (e) { showToast(e.message); }
  });

  document.getElementById('confirmVisitNote').addEventListener('click', async () => {
    const id = document.getElementById('visitNoteId').value;
    const note = document.getElementById('visitNoteText').value;
    try {
      await apiPost('api/visits_api.php?action=update_note', { id, note });
      closeModal('modalVisitNote');
      loadDoctorDetail(currentDoctorId);
    } catch (e) { showToast(e.message); }
  });

  document.getElementById('btnSaveNote').addEventListener('click', async () => {
    if (!currentDoctorId) return;
    const note = document.getElementById('generalNote').value;
    try {
      await apiPost('api/doctors_api.php?action=update_note', { id: currentDoctorId, note });
      showToast('تم الحفظ');
    } catch (e) { showToast(e.message); }
  });
});

async function removeFromBlacklist() {
  try {
    await apiPost('api/doctors_api.php?action=toggle_blacklist', { id: currentDoctorId, blacklisted: 0 });
    loadDoctorDetail(currentDoctorId);
    loadDoctors();
  } catch (e) { showToast(e.message); }
}

async function loadDoctors() {
  try {
    const data = await apiGet('api/doctors_api.php?action=list');
    const list = document.getElementById('doctorList');
    list.innerHTML = '';
    data.doctors.forEach(d => {
      const div = document.createElement('div');
      div.className = 'sidebar-item' + (d.is_blacklisted == 1 ? ' blacklisted' : '') + (d.id === currentDoctorId ? ' active' : '');
      div.innerHTML = `<span>${escapeHtml(d.name)}</span><span class="status-dot"></span>`;
      div.addEventListener('click', () => { currentDoctorId = d.id; loadDoctorDetail(d.id); loadDoctors(); });
      list.appendChild(div);
    });
  } catch (e) { showToast(e.message); }
}

async function loadDoctorDetail(id) {
  try {
    const data = await apiGet('api/doctors_api.php?action=get&id=' + encodeURIComponent(id));
    currentDoctorData = data.doctor;
    currentDoctorId = data.doctor.id;

    document.getElementById('noDoctorSelected').style.display = 'none';
    document.getElementById('doctorContent').style.display = 'flex';

    document.getElementById('doctorNameDisplay').textContent = data.doctor.name;
    document.getElementById('doctorTitle').value = data.doctor.title || '';

    const avatarWrap = document.getElementById('doctorAvatarWrap');
    avatarWrap.innerHTML = data.doctor.image_path
      ? `<img class="doctor-avatar" src="${escapeHtml(data.doctor.image_path)}" alt="">`
      : `<div class="doctor-avatar-placeholder">🩺</div>`;

    const isBl = data.doctor.is_blacklisted == 1;
    document.getElementById('blacklistBadge').className = 'status-badge' + (isBl ? ' on' : '');
    document.getElementById('btnToggleBlacklist').textContent = isBl ? 'إزالة من القائمة السوداء' : 'إضافة للقائمة السوداء';
    const reasonBox = document.getElementById('blacklistReason');
    reasonBox.style.display = isBl ? 'block' : 'none';
    reasonBox.value = data.doctor.blacklist_reason || '';

    document.getElementById('generalNote').value = data.doctor.general_note || '';

    const visitsList = document.getElementById('visitsList');
    visitsList.innerHTML = '';
    if (data.doctor.visits.length === 0) {
      visitsList.innerHTML = '<div class="empty-hint" style="margin-top:0;">مفيش زيارات لسه</div>';
    }
    data.doctor.visits.forEach(v => {
      const row = document.createElement('div');
      row.className = 'visit-row';
      row.innerHTML = `
        <span class="visit-date">${escapeHtml(v.visit_date)}</span>
        <span class="visit-note">${v.note ? escapeHtml(v.note) : 'مفيش ملاحظة'}</span>
        <button class="btn btn-sm" data-id="${v.id}" data-note="${escapeHtml(v.note || '')}">إضافة/تعديل ملاحظة</button>
      `;
      row.querySelector('button').addEventListener('click', (e) => {
        document.getElementById('visitNoteId').value = v.id;
        document.getElementById('visitNoteText').value = v.note || '';
        openModal('modalVisitNote');
      });
      visitsList.appendChild(row);
    });
  } catch (e) { showToast(e.message); }
}
