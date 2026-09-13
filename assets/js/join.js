document.addEventListener('DOMContentLoaded', loadStatus);

async function loadStatus() {
  try {
    const data = await apiGet('api/join_requests_api.php?action=my_status');
    const box = document.getElementById('joinContent');

    if (data.state === 'approved') {
      box.innerHTML = `
        <p>انت عضو في جروب "<b>${escapeHtml(data.group_name)}</b>".</p>
        <a class="btn btn-primary" href="dashboard.php" style="text-align:center; display:block;">روح للرئيسية</a>
      `;
    } else if (data.state === 'pending') {
      box.innerHTML = `
        <p>طلبك للانضمام لجروب "<b>${escapeHtml(data.group_name)}</b>" لسه معلّق عند الأدمن.</p>
        <p style="color:var(--text-dim); font-size:13px;">حدّث الصفحة بعدين للتأكد.</p>
      `;
    } else {
      box.innerHTML = `
        <label>اكتب كود الجروب اللي اداك ليه الأدمن</label>
        <input type="text" class="field-input" id="joinCodeInput" maxlength="20" style="text-transform:uppercase;">
        <button class="btn btn-primary" id="sendJoinRequest" style="margin-top:12px;">إرسال الطلب</button>
      `;
      document.getElementById('sendJoinRequest').addEventListener('click', sendRequest);
    }
  } catch (e) {
    document.getElementById('joinContent').textContent = e.message;
  }
}

async function sendRequest() {
  const code = document.getElementById('joinCodeInput').value.trim();
  if (!code) { showToast('اكتب الكود'); return; }
  try {
    await apiPost('api/join_requests_api.php?action=request', { join_code: code });
    loadStatus();
  } catch (e) {
    showToast(e.message);
  }
}
