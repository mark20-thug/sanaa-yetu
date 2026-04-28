const API_BASE = `${window.location.origin}/api/admin.php`;
const KEY_STORAGE = 'sanaa_admin_key';

let adminKey = localStorage.getItem(KEY_STORAGE) || '';

function headers() {
  return {
    'Content-Type': 'application/json',
    'X-Admin-Key': adminKey
  };
}

function showAuthOnly() {
  document.getElementById('authSection').classList.remove('hidden');
  document.getElementById('dashboardSection').classList.add('hidden');
}

function showDashboardOnly() {
  document.getElementById('authSection').classList.add('hidden');
  document.getElementById('dashboardSection').classList.remove('hidden');
}

async function adminGet(action) {
  const res = await fetch(`${API_BASE}?action=${encodeURIComponent(action)}`, { headers: headers() });
  const data = await res.json();
  if (!res.ok || !data.success) throw new Error(data.message || 'Admin request failed');
  return data.data || [];
}

async function adminPost(action, body) {
  const res = await fetch(`${API_BASE}?action=${encodeURIComponent(action)}`, {
    method: 'POST',
    headers: headers(),
    body: JSON.stringify(body)
  });
  const data = await res.json();
  if (!res.ok || !data.success) throw new Error(data.message || 'Admin action failed');
  return data;
}

function makerItemHtml(m) {
  return `
    <div class="item">
      <h3>${escapeHtml(m.business_name || m.name || 'Maker')}</h3>
      <div class="muted">Name: ${escapeHtml(m.name || '')}</div>
      <div class="muted">Email: ${escapeHtml(m.email || '')}</div>
      <div class="muted">Location: ${escapeHtml(m.location || 'N/A')}</div>
      <div class="muted">Plan: ${escapeHtml(m.plan || 'starter')}</div>
      <div class="muted">Amount: UGX ${escapeHtml((m.payment_amount_ugx || 0).toString())}</div>
      <div class="muted">Payment Ref: ${escapeHtml(m.payment_reference || 'N/A')}</div>
      <div class="muted">Status: ${escapeHtml(m.payment_status || 'unpaid')} / ${escapeHtml(m.approval_status || 'pending')}</div>
      <div class="actions">
        <button class="approve" onclick="reviewMaker('${m.id}','approve')">Approve</button>
        <button class="reject" onclick="reviewMaker('${m.id}','reject')">Reject</button>
      </div>
    </div>
  `;
}

function productItemHtml(p) {
  return `
    <div class="item">
      <h3>${escapeHtml(p.name || 'Product')}</h3>
      <div class="muted">Maker: ${escapeHtml(p.artisan_name || 'N/A')}</div>
      <div class="muted">Category: ${escapeHtml(p.category || 'N/A')}</div>
      <div class="muted">Price: UGX ${escapeHtml(p.price || '')}</div>
      <div class="muted">Status: ${escapeHtml(p.status || 'pending')}</div>
      <div class="actions">
        <button class="approve" onclick="reviewProduct('${p.id}','approve')">Approve</button>
        <button class="reject" onclick="reviewProduct('${p.id}','reject')">Reject</button>
      </div>
    </div>
  `;
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>]/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
}

async function loadDashboard() {
  if (!adminKey) {
    showAuthOnly();
    return;
  }

  try {
    const [makers, products, analytics] = await Promise.all([
      adminGet('pending_makers'),
      adminGet('pending_products'),
      adminGet('ratings_analytics')
    ]);

    const makersContainer = document.getElementById('pendingMakers');
    const productsContainer = document.getElementById('pendingProducts');

    makersContainer.innerHTML = makers.length ? makers.map(makerItemHtml).join('') : '<p class="muted">No pending makers.</p>';
    productsContainer.innerHTML = products.length ? products.map(productItemHtml).join('') : '<p class="muted">No pending products.</p>';
    document.getElementById('statTotalRatings').innerText = analytics.total_ratings || 0;
    document.getElementById('statMakerAvg').innerText = analytics.maker_rating_avg || 0;
    document.getElementById('statProductAvg').innerText = analytics.product_rating_avg || 0;
    document.getElementById('statRecentRatings').innerText = (analytics.maker_ratings_last_7_days || 0) + (analytics.product_ratings_last_7_days || 0);

    showDashboardOnly();
  } catch (err) {
    alert(err.message || 'Failed to load dashboard');
    showAuthOnly();
  }
}

async function reviewMaker(makerId, decision) {
  const notes = prompt(`Optional notes for maker ${decision}:`) || '';
  try {
    await adminPost('review_maker', { maker_id: makerId, decision, notes });
    await loadDashboard();
  } catch (err) {
    alert(err.message || 'Failed to review maker');
  }
}

async function reviewProduct(productId, decision) {
  const reason = decision === 'reject' ? (prompt('Reason for rejection:') || '') : '';
  try {
    await adminPost('review_product', { product_id: productId, decision, reason });
    await loadDashboard();
  } catch (err) {
    alert(err.message || 'Failed to review product');
  }
}

function saveKeyAndLoad() {
  const key = document.getElementById('adminKeyInput').value.trim();
  if (!key) {
    alert('Enter admin key');
    return;
  }
  adminKey = key;
  localStorage.setItem(KEY_STORAGE, adminKey);
  loadDashboard();
}

function logoutAdmin() {
  adminKey = '';
  localStorage.removeItem(KEY_STORAGE);
  document.getElementById('adminKeyInput').value = '';
  showAuthOnly();
}

window.reviewMaker = reviewMaker;
window.reviewProduct = reviewProduct;

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('saveAdminKeyBtn').addEventListener('click', saveKeyAndLoad);
  document.getElementById('refreshBtn').addEventListener('click', loadDashboard);
  document.getElementById('logoutBtn').addEventListener('click', logoutAdmin);
  loadDashboard();
});
