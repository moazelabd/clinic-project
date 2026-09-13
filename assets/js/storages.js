let currentStorageId = null;
let searchDebounce = null;

document.addEventListener('DOMContentLoaded', () => {
  loadStorages();

  on('btnAddStorage', 'click', () => {
    document.getElementById('newStorageName').value = '';
    openModal('modalAddStorage');
  });

  document.getElementById('confirmAddStorage').addEventListener('click', async () => {
    const name = document.getElementById('newStorageName').value.trim();
    if (!name) { showToast('اكتب اسم المخزن'); return; }
    try {
      await apiPost('api/storages_api.php?action=add', { name });
      closeModal('modalAddStorage');
      loadStorages();
    } catch (e) { showToast(e.message); }
  });

  on('btnDeleteStorage', 'click', async () => {
    if (!currentStorageId) return;
    if (!confirm('متأكد إنك عايز تمسح المخزن ده؟ هيتمسح كل الأدوية اللي جواه.')) return;
    try {
      await apiPost('api/storages_api.php?action=delete', { id: currentStorageId });
      currentStorageId = null;
      document.getElementById('storageContent').style.display = 'none';
      document.getElementById('noStorageSelected').style.display = 'block';
      loadStorages();
    } catch (e) { showToast(e.message); }
  });

  on('btnAddMedicine', 'click', () => {
    openMedicineModal(null);
  });

  document.getElementById('searchInput').addEventListener('input', (e) => {
    clearTimeout(searchDebounce);
    const q = e.target.value;
    searchDebounce = setTimeout(() => loadMedicines(q), 250);
  });

  document.getElementById('stockPlus').addEventListener('click', () => {
    const input = document.getElementById('medStock');
    input.value = Math.max(0, (parseInt(input.value || '0', 10) + 1));
  });
  document.getElementById('stockMinus').addEventListener('click', () => {
    const input = document.getElementById('medStock');
    input.value = Math.max(0, (parseInt(input.value || '0', 10) - 1));
  });

  document.getElementById('confirmMedicine').addEventListener('click', saveMedicine);
  document.getElementById('btnDeleteMedicine').addEventListener('click', deleteMedicine);
});

async function loadStorages() {
  try {
    const data = await apiGet('api/storages_api.php?action=list');
    const list = document.getElementById('storageList');
    list.innerHTML = '';
    data.storages.forEach(s => {
      const div = document.createElement('div');
      div.className = 'sidebar-item' + (s.id === currentStorageId ? ' active' : '');
      div.textContent = s.name;
      div.addEventListener('click', () => selectStorage(s.id, s.name));
      list.appendChild(div);
    });
  } catch (e) { showToast(e.message); }
}

function selectStorage(id, name) {
  currentStorageId = id;
  document.getElementById('noStorageSelected').style.display = 'none';
  document.getElementById('storageContent').style.display = 'block';
  document.getElementById('searchInput').value = '';
  loadStorages();
  loadMedicines('');
}

async function loadMedicines(q) {
  if (!currentStorageId) return;
  try {
    const url = 'api/medicines_api.php?action=list&storage_id=' + encodeURIComponent(currentStorageId)
      + (q ? '&q=' + encodeURIComponent(q) : '');
    const data = await apiGet(url);
    const grid = document.getElementById('medicineGrid');
    grid.innerHTML = '';
    data.medicines.forEach(m => {
      const card = document.createElement('div');
      card.className = 'med-card';
      const imgHtml = m.image_path
        ? `<img src="${escapeHtml(m.image_path)}" alt="">`
        : `<div class="placeholder">💊</div>`;
      card.innerHTML = `${imgHtml}<div class="name">${escapeHtml(m.name)}</div><div class="stock">الستوك: ${m.stock}</div>`;
      card.addEventListener('click', () => openMedicineModal(m));
      grid.appendChild(card);
    });
    if (data.medicines.length === 0) {
      grid.innerHTML = '<div class="empty-hint">مفيش نتايج</div>';
    }
  } catch (e) { showToast(e.message); }
}

function openMedicineModal(med) {
  document.getElementById('medicineModalTitle').textContent = med ? (IS_ADMIN ? 'تعديل الدواء' : 'بيانات الدواء') : 'إضافة دواء';
  document.getElementById('medId').value = med ? med.id : '';
  document.getElementById('medName').value = med ? med.name : '';
  document.getElementById('medStock').value = med ? med.stock : 0;
  document.getElementById('medImage').value = '';

  const readOnly = med && !IS_ADMIN;
  document.getElementById('medName').disabled = readOnly;
  document.getElementById('medStock').disabled = readOnly;
  document.getElementById('medImage').style.display = readOnly ? 'none' : '';
  document.getElementById('stockMinus').style.display = readOnly ? 'none' : '';
  document.getElementById('stockPlus').style.display = readOnly ? 'none' : '';
  document.getElementById('confirmMedicine').style.display = readOnly ? 'none' : '';
  document.getElementById('btnDeleteMedicine').style.display = (med && IS_ADMIN) ? 'inline-block' : 'none';
  openModal('modalMedicine');
}

async function saveMedicine() {
  const id = document.getElementById('medId').value;
  const name = document.getElementById('medName').value.trim();
  const stock = document.getElementById('medStock').value;
  const imageFile = document.getElementById('medImage').files[0];

  if (!name) { showToast('اكتب اسم الدواء'); return; }

  const fd = new FormData();
  fd.append('name', name);
  fd.append('stock', stock);
  if (imageFile) fd.append('image', imageFile);

  try {
    if (id) {
      fd.append('id', id);
      await apiPost('api/medicines_api.php?action=update', fd);
    } else {
      fd.append('storage_id', currentStorageId);
      await apiPost('api/medicines_api.php?action=add', fd);
    }
    closeModal('modalMedicine');
    loadMedicines(document.getElementById('searchInput').value);
  } catch (e) { showToast(e.message); }
}

async function deleteMedicine() {
  const id = document.getElementById('medId').value;
  if (!id) return;
  if (!confirm('متأكد إنك عايز تمسح الدواء ده؟')) return;
  try {
    await apiPost('api/medicines_api.php?action=delete', { id });
    closeModal('modalMedicine');
    loadMedicines(document.getElementById('searchInput').value);
  } catch (e) { showToast(e.message); }
}
