// ======================= API CONFIG =======================
const API_BASE = (() => {
  if (window.APP_CONFIG?.API_BASE) return window.APP_CONFIG.API_BASE;
  if (window.location.protocol === 'file:') return 'http://localhost:8000/api';
  return `${window.location.origin}/api`;
})();

// ======================= DATA & STORAGE =======================
let currentUser = null;
let allProducts = [];
let makers = [];
let makerRatings = {};
let productRatings = {};
const PLAN_CONFIG = {
  starter: { label: 'Starter', amount: 30000, maxProducts: 10, canFeature: false },
  pro: { label: 'Studio Pro', amount: 70000, maxProducts: 100, canFeature: false },
  featured: { label: 'Featured Plus', amount: 120000, maxProducts: 200, canFeature: true }
};

const RATER_TOKEN_KEY = 'sanaa_rater_token';
function getRaterToken() {
  let token = localStorage.getItem(RATER_TOKEN_KEY);
  if (!token) {
    token = `${Date.now()}_${Math.random().toString(36).slice(2, 12)}`;
    localStorage.setItem(RATER_TOKEN_KEY, token);
  }
  return token;
}

async function loadData() {
  try {
    // Check for stored session
    const storedUser = localStorage.getItem("sanaa_current_user");
    if (storedUser) currentUser = JSON.parse(storedUser);
    
    // Fetch products from API
    const productsRes = await fetch(`${API_BASE}/products.php?action=list`);
    allProducts = await productsRes.json();
    
    // Fetch makers from API
    const makersRes = await fetch(`${API_BASE}/makers.php?action=list`);
    const makersData = await makersRes.json();
    makers = makersData; // Store for auth
  } catch(e) {
    console.error('Error loading data:', e);
    // Fallback to localStorage if API fails
    const storedProducts = localStorage.getItem("sanaa_all_products");
    if (storedProducts) allProducts = JSON.parse(storedProducts);
  }
}

async function loadRatings() {
  const makerIds = [...new Set(allProducts.map(p => p.artisan_id).filter(Boolean))];
  const productIds = [...new Set(allProducts.map(p => p.id).filter(Boolean))];

  await Promise.all([
    Promise.all(makerIds.map(async (makerId) => {
      try {
        const res = await fetch(`${API_BASE}/ratings.php?action=maker_summary&maker_id=${encodeURIComponent(makerId)}`);
        const payload = await res.json();
        makerRatings[makerId] = payload.success ? payload.data : { avg: 0, count: 0 };
      } catch {
        makerRatings[makerId] = { avg: 0, count: 0 };
      }
    })),
    Promise.all(productIds.map(async (productId) => {
      try {
        const res = await fetch(`${API_BASE}/ratings.php?action=product_summary&product_id=${encodeURIComponent(productId)}`);
        const payload = await res.json();
        productRatings[productId] = payload.success ? payload.data : { avg: 0, count: 0 };
      } catch {
        productRatings[productId] = { avg: 0, count: 0 };
      }
    }))
  ]);
}

function formatRating(summary) {
  if (!summary || !summary.count) return 'No ratings yet';
  return `⭐ ${summary.avg} (${summary.count})`;
}

async function saveProducts() {
  localStorage.setItem("sanaa_all_products", JSON.stringify(allProducts));
}

function saveMakers() {
  localStorage.setItem("sanaa_makers", JSON.stringify(makers));
}

function saveCurrentUser() {
  if(currentUser) localStorage.setItem("sanaa_current_user", JSON.stringify(currentUser));
  else localStorage.removeItem("sanaa_current_user");
}

let currentFilter = "all";
let currentSearch = "";
let viewMode = "home";
let currentPreviewObjectUrl = null;
let ratingDraft = { type: null, makerId: null, productId: null, score: 0, targetLabel: '' };

function updateProductImagePreview() {
  const fileInput = document.getElementById('productImageFile');
  const urlInput = document.getElementById('productImage');
  const previewWrap = document.getElementById('productImagePreviewWrap');
  const previewImg = document.getElementById('productImagePreview');
  if (!fileInput || !urlInput || !previewWrap || !previewImg) return;

  const selectedFile = fileInput.files?.[0];
  if (selectedFile) {
    if (currentPreviewObjectUrl) {
      URL.revokeObjectURL(currentPreviewObjectUrl);
      currentPreviewObjectUrl = null;
    }
    const objectUrl = URL.createObjectURL(selectedFile);
    currentPreviewObjectUrl = objectUrl;
    previewImg.src = objectUrl;
    previewWrap.style.display = 'block';
    return;
  }

  const urlValue = urlInput.value.trim();
  if (urlValue) {
    if (currentPreviewObjectUrl) {
      URL.revokeObjectURL(currentPreviewObjectUrl);
      currentPreviewObjectUrl = null;
    }
    previewImg.src = urlValue;
    previewWrap.style.display = 'block';
    return;
  }

  if (currentPreviewObjectUrl) {
    URL.revokeObjectURL(currentPreviewObjectUrl);
    currentPreviewObjectUrl = null;
  }
  previewImg.removeAttribute('src');
  previewWrap.style.display = 'none';
}

function clearSelectedProductImage() {
  const fileInput = document.getElementById('productImageFile');
  const urlInput = document.getElementById('productImage');
  if (fileInput) fileInput.value = '';
  if (urlInput) urlInput.value = '';
  updateProductImagePreview();
}

function getSelectedPlan() {
  const selected = document.querySelector('input[name="makerPlan"]:checked');
  return selected ? selected.value : 'starter';
}

function updateSelectedPlanSummary() {
  const planKey = getSelectedPlan();
  const plan = PLAN_CONFIG[planKey] || PLAN_CONFIG.starter;
  const summary = document.getElementById('selectedPlanSummary');
  if (summary) {
    summary.value = `Selected: ${plan.label} (UGX ${plan.amount.toLocaleString()}/month)`;
  }
}

async function uploadProductImage(file) {
  const formData = new FormData();
  formData.append('image', file);

  const res = await fetch(`${API_BASE}/upload.php`, {
    method: 'POST',
    body: formData
  });

  const result = await res.json();
  if (!res.ok || !result.success || !result.url) {
    throw new Error(result.message || 'Image upload failed');
  }

  return result.url;
}

function renderMarketplace() {
  let filtered = [...allProducts];
  if (viewMode === "mygoods" && currentUser) filtered = filtered.filter(p => p.artisan_id === currentUser.id);
  if (currentFilter !== "all") filtered = filtered.filter(p => p.category === currentFilter);
  if (currentSearch.trim()) {
    const search = currentSearch.toLowerCase();
    filtered = filtered.filter(p => p.name.toLowerCase().includes(search) || p.artisan_name.toLowerCase().includes(search));
  }
  const container = document.getElementById('productsGrid');
  if (!container) return;
  if (filtered.length === 0) { container.innerHTML = `<div style="grid-column:1/-1; text-align:center; padding:40px;">✨ No products found ✨</div>`; return; }
  container.innerHTML = filtered.map(p => `
    <div class="product-card">
      <div class="product-img" style="background-image: url('${p.image_url || 'https://images.unsplash.com/photo-1544441893-675973e31985?w=400&h=300&fit=crop'}');">
        <div class="shop-badge"><i class="fas fa-store"></i> ${escapeHtml(p.artisan_name)}</div>
      </div>
      <div class="product-info">
        <div class="product-title">${escapeHtml(p.name)}</div>
        <div class="product-price">UGX ${escapeHtml(p.price)}</div>
        <div class="product-story">⭐ ${escapeHtml(formatRating(productRatings[p.id]))}</div>
        <div class="product-story">📖 ${escapeHtml(p.story?.substring(0, 80))}</div>
        <div class="artisan-link" onclick="event.stopPropagation(); viewMakerGoods('${p.artisan_id}', '${escapeHtml(p.artisan_name)}')"><i class="fas fa-user"></i> View ${escapeHtml(p.artisan_name)}'s goods</div>
        <button class="whatsapp-btn" onclick="event.stopPropagation(); openWhatsAppChat('${p.artisan_whatsapp}', '${escapeHtml(p.name)}')"><i class="fab fa-whatsapp"></i> Order via WhatsApp</button>
        <button class="whatsapp-btn" style="margin-top:8px; background:#1f2937;" onclick="event.stopPropagation(); rateProductPrompt('${p.id}', '${p.artisan_id}')"><i class="fas fa-star"></i> Rate Product</button>
      </div>
    </div>
  `).join('');
}

function renderShops() {
  const uniqueMakersMap = new Map();
  allProducts.forEach(p => { if (!uniqueMakersMap.has(p.artisan_id)) uniqueMakersMap.set(p.artisan_id, { id: p.artisan_id, name: p.artisan_name, whatsapp: p.artisan_whatsapp }); });
  const grid = document.getElementById('shopsGrid');
  if (!grid) return;
  if (uniqueMakersMap.size === 0) { grid.innerHTML = '<div style="padding:20px; text-align:center;">✨ No makers yet. Be the first to join!</div>'; return; }
  grid.innerHTML = Array.from(uniqueMakersMap.values()).map(m => `
    <div class="shop-item" onclick="viewMakerGoods('${m.id}', '${escapeHtml(m.name)}')">
      <div class="shop-name"><i class="fas fa-store"></i> ${escapeHtml(m.name)}</div>
      <div class="product-story" style="margin-top:6px;">${escapeHtml(formatRating(makerRatings[m.id]))}</div>
      <button class="whatsapp-btn" style="margin-top:10px; background:#25D366;" onclick="event.stopPropagation(); window.open('https://wa.me/${m.whatsapp.replace(/[^0-9]/g, '')}?text=Hello!%20I'm%20interested%20in%20your%20artisan%20products%20on%20SanaaYetu', '_blank')"><i class="fab fa-whatsapp"></i> Contact Maker</button>
      <button class="whatsapp-btn" style="margin-top:8px; background:#1f2937;" onclick="event.stopPropagation(); rateMakerPrompt('${m.id}')"><i class="fas fa-star"></i> Rate Maker</button>
    </div>
  `).join('');
}

function viewMakerGoods(makerId, makerName) {
  const makerProducts = allProducts.filter(p => p.artisan_id === makerId);
  document.getElementById('makerProfileTitle').innerHTML = `<i class="fas fa-user"></i> ${makerName}'s Collection`;
  const container = document.getElementById('makerProductsGrid');
  if (makerProducts.length === 0) container.innerHTML = "<div style='padding:20px; text-align:center;'>✨ No products yet from this maker.</div>";
  else {
    container.innerHTML = makerProducts.map(p => `
      <div class="product-item-dash" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
        <div><strong>${escapeHtml(p.name)}</strong> — UGX ${escapeHtml(p.price)}<br><small>${escapeHtml(p.story?.substring(0,60))}</small></div>
        <div style="display:flex; gap:8px;">
          <button class="whatsapp-btn" style="padding:6px 12px; width:auto;" onclick="window.open('https://wa.me/${p.artisan_whatsapp.replace(/[^0-9]/g, '')}?text=I'd%20like%20to%20order%20${encodeURIComponent(p.name)}', '_blank')"><i class="fab fa-whatsapp"></i> Order</button>
          <button class="whatsapp-btn" style="padding:6px 12px; width:auto; background:#1f2937;" onclick="rateProductPrompt('${p.id}', '${p.artisan_id}')"><i class="fas fa-star"></i> Rate</button>
        </div>
      </div>
    `).join('');
  }
  document.getElementById('makerProfileModal').style.display = 'flex';
}
function closeMakerProfile() { document.getElementById('makerProfileModal').style.display = 'none'; }
function openWhatsAppChat(whatsapp, productName) {
  const phone = whatsapp.replace(/[^0-9]/g, '');
  window.open(`https://wa.me/${phone}?text=${encodeURIComponent(`Hello! I'm interested in "${productName}" from SanaaYetu.`)}`, '_blank');
}
function handleSearch() { currentSearch = document.getElementById('searchInput').value; renderMarketplace(); updateViewTitle(); }
function resetToHome() { viewMode = "home"; currentFilter = "all"; currentSearch = ""; document.getElementById('searchInput').value = ""; renderMarketplace(); renderShops(); updateViewTitle(); highlightActiveCategory(); }
function filterByCategory(cat) { currentFilter = cat; renderMarketplace(); highlightActiveCategory(); updateViewTitle(); }
function highlightActiveCategory() { document.querySelectorAll('.category-badge').forEach(el => { if(el.dataset.cat === currentFilter) el.classList.add('active'); else el.classList.remove('active'); }); }
function updateViewTitle() { const titleDiv = document.getElementById('viewTitle'); if (viewMode === "mygoods") titleDiv.innerHTML = `<div style="display:flex; justify-content:space-between;"><span><i class="fas fa-box"></i> My Goods</span><span class="back-link" onclick="resetToHome()">← Back to Home</span></div>`; else titleDiv.innerHTML = ''; }
function showMyGoods() { if(!currentUser) { alert("Please login as maker first"); return; } viewMode = "mygoods"; currentFilter = "all"; currentSearch = ""; document.getElementById('searchInput').value = ""; renderMarketplace(); updateViewTitle(); }

async function rateMakerPrompt(makerId) {
  const maker = makers.find((m) => m.id === makerId);
  openRatingModal({
    type: 'maker',
    makerId,
    productId: null,
    targetLabel: maker?.name || 'this maker'
  });
}

async function rateProductPrompt(productId, makerId) {
  const product = allProducts.find((p) => p.id === productId);
  openRatingModal({
    type: 'product',
    makerId,
    productId,
    targetLabel: product?.name || 'this product'
  });
}

function setRatingStars(score) {
  ratingDraft.score = score;
  document.querySelectorAll('.rating-star').forEach((btn) => {
    const value = parseInt(btn.dataset.score, 10);
    if (value <= score) btn.classList.add('active');
    else btn.classList.remove('active');
  });
}

function openRatingModal({ type, makerId, productId, targetLabel }) {
  ratingDraft = { type, makerId, productId, score: 0, targetLabel };
  document.getElementById('ratingComment').value = '';
  setRatingStars(0);
  document.getElementById('ratingModalTitle').innerHTML = `<i class="fas fa-star"></i> Rate ${type === 'maker' ? 'Maker' : 'Product'}`;
  document.getElementById('ratingTargetText').innerText = `You are rating: ${targetLabel}`;
  document.getElementById('ratingModal').style.display = 'flex';
}

function closeRatingModal() {
  document.getElementById('ratingModal').style.display = 'none';
}

async function submitRatingFromModal() {
  if (!ratingDraft.type || !ratingDraft.score) {
    alert('Please select a star rating first.');
    return;
  }
  const comment = document.getElementById('ratingComment').value.trim().substring(0, 200);

  try {
    const body = ratingDraft.type === 'maker'
      ? {
          maker_id: ratingDraft.makerId,
          score: ratingDraft.score,
          comment,
          rater_token: getRaterToken()
        }
      : {
          product_id: ratingDraft.productId,
          maker_id: ratingDraft.makerId,
          score: ratingDraft.score,
          comment,
          rater_token: getRaterToken()
        };

    const action = ratingDraft.type === 'maker' ? 'rate_maker' : 'rate_product';
    const res = await fetch(`${API_BASE}/ratings.php?action=${action}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const result = await res.json();
    if (!result.success) {
      alert(result.message || 'Failed to submit rating');
      return;
    }
    closeRatingModal();
    await loadRatings();
    renderMarketplace();
    renderShops();
    alert('Thanks! Your rating was submitted.');
  } catch (e) {
    console.error(e);
    alert('Failed to submit rating');
  }
}

// ======================= AUTH FIXED =======================
function openAuthModal() { document.getElementById('authModal').style.display = 'flex'; }
function closeAuthModal() { document.getElementById('authModal').style.display = 'none'; }
function openDashboardModal() { if(!currentUser) { openAuthModal(); return; } document.getElementById('dashboardUserName').innerText = currentUser.name; renderUserProducts(); document.getElementById('dashboardModal').style.display = 'flex'; }
function closeDashboardModal() { document.getElementById('dashboardModal').style.display = 'none'; }
function toggleToLogin() { document.getElementById('loginForm').style.display='none'; document.getElementById('signinForm').style.display='block'; document.getElementById('authModalTitle').innerHTML='<i class="fas fa-sign-in-alt"></i> Welcome Back'; }
function toggleToRegister() { document.getElementById('loginForm').style.display='block'; document.getElementById('signinForm').style.display='none'; document.getElementById('authModalTitle').innerHTML='<i class="fas fa-hands-helping"></i> Become a Maker'; }
async function registerUser() {
  const name = document.getElementById('loginName').value.trim();
  const businessName = document.getElementById('loginBusinessName').value.trim();
  const email = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value;
  let whatsapp = document.getElementById('whatsappNumber').value.trim();
  const location = document.getElementById('makerLocation').value.trim();
  const bio = document.getElementById('makerBio').value.trim();
  if (!name || !email || !password || password.length < 4) { alert("All fields required, password min 4 chars"); return; }
  if (!whatsapp) { alert("WhatsApp number required for buyers to contact you!"); return; }
  if (!whatsapp.startsWith('+')) whatsapp = '+' + whatsapp.replace(/[^0-9]/g, '');
  
  try {
    const res = await fetch(`${API_BASE}/makers.php?action=register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name,
        business_name: businessName || name,
        email,
        password,
        whatsapp,
        location,
        bio
      })
    });
    const result = await res.json();
    
    if (result.success) {
      alert("✅ Account created. Next: complete payment and submit your payment reference, then wait for admin approval.");
      toggleToLogin();
      document.getElementById('signinEmail').value = email;
    } else {
      alert(result.message || 'Registration failed');
    }
  } catch(e) {
    console.error(e);
    alert('Registration failed. Please try again.');
  }
}

async function loginUser() {
  const email = document.getElementById('signinEmail').value.trim();
  const password = document.getElementById('signinPassword').value;
  
  try {
    const res = await fetch(`${API_BASE}/makers.php?action=login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    const result = await res.json();
    
    if (result.success) {
      currentUser = { id: result.data.id, name: result.data.name, email: result.data.email, whatsapp: result.data.whatsapp };
      saveCurrentUser(); updateUIBasedOnAuth(); closeAuthModal();
      openDashboardModal();
    } else {
      alert(result.message || 'Login failed');
    }
  } catch(e) {
    console.error(e);
    alert('Login failed. Please try again.');
  }
}

async function submitPaymentReference() {
  const email = document.getElementById('signinEmail').value.trim();
  const paymentReference = document.getElementById('paymentReference').value.trim();
  const selectedPlan = getSelectedPlan();
  const selectedPlanConfig = PLAN_CONFIG[selectedPlan] || PLAN_CONFIG.starter;
  if (!email || !paymentReference) {
    alert("Enter your email and payment reference first.");
    return;
  }

  try {
    const res = await fetch(`${API_BASE}/makers.php?action=submit_payment`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email,
        payment_reference: paymentReference,
        plan: selectedPlan,
        amount_ugx: selectedPlanConfig.amount
      })
    });
    const result = await res.json();
    if (result.success) {
      alert("✅ Payment submitted. Admin will review and approve your account.");
      document.getElementById('paymentReference').value = '';
    } else {
      alert(result.message || 'Failed to submit payment reference');
    }
  } catch (e) {
    console.error(e);
    alert('Failed to submit payment reference');
  }
}
function logoutUser() { currentUser = null; saveCurrentUser(); viewMode="home"; currentFilter="all"; currentSearch=""; document.getElementById('searchInput').value=""; renderMarketplace(); renderShops(); updateUIBasedOnAuth(); updateViewTitle(); }
function updateUIBasedOnAuth() {
  const authBtn = document.getElementById('authBtn'); const myGoods = document.getElementById('myGoodsBtn'); const dashboard = document.getElementById('dashboardBtn'); const logout = document.getElementById('logoutBtn'); const greeting = document.getElementById('userGreeting');
  if(currentUser) {
    if(authBtn) authBtn.style.display='none'; if(myGoods) myGoods.style.display='flex'; if(dashboard) dashboard.style.display='flex'; if(logout) logout.style.display='flex'; if(greeting) { greeting.style.display='block'; greeting.innerHTML = `<i class="fas fa-user-check"></i> ${currentUser.name}`; }
  } else {
    if(authBtn) authBtn.style.display='flex'; if(myGoods) myGoods.style.display='none'; if(dashboard) dashboard.style.display='none'; if(logout) logout.style.display='none'; if(greeting) greeting.style.display='none';
  }
}
function renderUserProducts() {
  const container = document.getElementById('artisanProductList');
  if(!container || !currentUser) return;
  const myProducts = allProducts.filter(p => p.artisan_id === currentUser.id);
  if(myProducts.length===0) { container.innerHTML='<div style="padding:20px; text-align:center;">✨ No products yet. Publish your first craft!</div>'; return; }
  container.innerHTML = myProducts.map(p => `<div class="product-item-dash"><strong>${escapeHtml(p.name)}</strong> — UGX ${escapeHtml(p.price)}<br><small>${escapeHtml(p.story?.substring(0,60))}</small></div>`).join('');
}
async function addProduct() {
  if(!currentUser) { alert("Login required"); return; }
  const name = document.getElementById('productName').value.trim();
  const priceRaw = document.getElementById('productPrice').value.trim();
  const story = document.getElementById('productStory').value.trim();
  const category = document.getElementById('productCategory').value;
  const imageFile = document.getElementById('productImageFile').files?.[0] || null;
  let imageUrl = document.getElementById('productImage').value.trim();
  if(!name || !priceRaw) { alert("Name and price required"); return; }
  const priceNum = parseInt(priceRaw.replace(/[^0-9]/g,''));
  if(isNaN(priceNum)) { alert("Valid price required"); return; }
  const formattedPrice = priceNum.toLocaleString();
  
  try {
    if (imageFile) {
      imageUrl = await uploadProductImage(imageFile);
    }
    if(!imageUrl) imageUrl = `https://source.unsplash.com/featured/400x300/?uganda,craft&sig=${Date.now()}`;

    const res = await fetch(`${API_BASE}/products.php?action=add`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name,
        price: formattedPrice,
        story: story.substring(0,180) || "Handcrafted in Uganda",
        category,
        image_url: imageUrl,
        artisan_id: currentUser.id,
        artisan_name: currentUser.name,
        artisan_whatsapp: currentUser.whatsapp
      })
    });
    const result = await res.json();
    
    if (result.success) {
      allProducts.unshift(result.data);
      renderMarketplace(); renderUserProducts();
      document.getElementById('productName').value='';
      document.getElementById('productPrice').value='';
      document.getElementById('productStory').value='';
      document.getElementById('productImageFile').value='';
      document.getElementById('productImage').value='';
      updateProductImagePreview();
      alert(result.message || "✅ Product submitted and awaiting admin approval.");
      if(viewMode==='mygoods') renderMarketplace();
    } else {
      alert(result.message || 'Failed to add product');
    }
  } catch(e) {
    console.error(e);
    alert('Failed to publish product');
  }
}
function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m])); }

// INITIALIZE WITH PROPER EVENT LISTENERS
document.addEventListener('DOMContentLoaded', () => {
  (async () => {
    await loadData();
    await loadRatings();
    renderMarketplace();
    renderShops();
    updateUIBasedOnAuth();
    highlightActiveCategory();
    updateViewTitle();
  })();
  
  document.querySelectorAll('.category-badge').forEach(el => el.addEventListener('click', () => filterByCategory(el.dataset.cat)));
  document.getElementById('authBtn').onclick = openAuthModal;
  document.getElementById('myGoodsBtn').onclick = showMyGoods;
  document.getElementById('dashboardBtn').onclick = openDashboardModal;
  document.getElementById('logoutBtn').onclick = logoutUser;
  document.getElementById('doRegisterBtn').onclick = registerUser;
  document.getElementById('doLoginBtn').onclick = loginUser;
  document.getElementById('submitPaymentBtn').onclick = submitPaymentReference;
  document.getElementById('publishProductBtn').onclick = addProduct;
  document.getElementById('closeAuthModalBtn').onclick = closeAuthModal;
  document.getElementById('closeDashboardModalBtn').onclick = closeDashboardModal;
  document.getElementById('closeMakerModalBtn').onclick = closeMakerProfile;
  document.getElementById('switchToLoginBtn').onclick = toggleToLogin;
  document.getElementById('switchToRegisterBtn').onclick = toggleToRegister;
  document.getElementById('productImageFile').addEventListener('change', updateProductImagePreview);
  document.getElementById('productImage').addEventListener('input', updateProductImagePreview);
  document.getElementById('clearProductImageBtn').addEventListener('click', clearSelectedProductImage);
  document.querySelectorAll('input[name="makerPlan"]').forEach((el) => el.addEventListener('change', updateSelectedPlanSummary));
  updateSelectedPlanSummary();
  document.getElementById('closeRatingModalBtn').onclick = closeRatingModal;
  document.getElementById('submitRatingBtn').onclick = submitRatingFromModal;
  document.querySelectorAll('.rating-star').forEach((btn) => {
    btn.addEventListener('click', () => setRatingStars(parseInt(btn.dataset.score, 10)));
  });
  window.openWhatsAppChat = openWhatsAppChat; window.viewMakerGoods = viewMakerGoods; window.resetToHome = resetToHome; window.handleSearch = handleSearch; window.rateMakerPrompt = rateMakerPrompt; window.rateProductPrompt = rateProductPrompt;
});