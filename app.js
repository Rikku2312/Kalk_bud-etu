// --- DATA INITIALIZATION & LOCALSTORAGE ---

const DEFAULT_CATEGORIES = [
  { id: 1, name: 'Wynagrodzenie', type: 'income', icon: '💼', color: '#10b981' },
  { id: 2, name: 'Inne przychody', type: 'income', icon: '💰', color: '#f59e0b' },
  { id: 3, name: 'Jedzenie', type: 'expense', icon: '🍕', color: '#ef4444' },
  { id: 4, name: 'Mieszkanie', type: 'expense', icon: '🏠', color: '#eab308' },
  { id: 5, name: 'Transport', type: 'expense', icon: '🚗', color: '#f97316' },
  { id: 6, name: 'Rozrywka', type: 'expense', icon: '🎮', color: '#a855f7' },
  { id: 7, name: 'Zdrowie', type: 'expense', icon: '💊', color: '#ec4899' },
  { id: 8, name: 'Inne wydatki', type: 'expense', icon: '🛒', color: '#6b7280' }
];

// Profile colors for badges
const PROFILE_COLORS = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#a855f7', '#ec4899', '#f97316', '#14b8a6'];

// Overview state
let overviewSelectedProfile = 'all';

function initData() {
  let profiles = JSON.parse(localStorage.getItem('kb_profiles'));

  if (!profiles) {
    // Migration or first boot
    profiles = [{ id: 'default', name: 'Główny profil' }];
    localStorage.setItem('kb_profiles', JSON.stringify(profiles));
    localStorage.setItem('kb_active_profile', 'default');

    // Migrate old data if exists
    const oldData = localStorage.getItem('kb_data');
    if (oldData) {
      localStorage.setItem('kb_data_default', oldData);
      localStorage.removeItem('kb_data');
    }
  }

  if (!localStorage.getItem('kb_active_profile')) {
    localStorage.setItem('kb_active_profile', profiles[0].id);
  }

  const activeId = localStorage.getItem('kb_active_profile');
  if (!localStorage.getItem(`kb_data_${activeId}`)) {
    const defaultData = {
      transactions: [],
      budgets: [],
      savings: [],
      nextId: 2
    };
    localStorage.setItem(`kb_data_${activeId}`, JSON.stringify(defaultData));
  }
}

function getValidData(rawStr) {
  if (!rawStr) return { transactions: [], budgets: [], savings: [], nextId: 1 };
  try {
    let parsed = JSON.parse(rawStr);
    if (Array.isArray(parsed)) {
      return { transactions: parsed, budgets: [], savings: [], nextId: parsed.length + 1 };
    }
    return {
      transactions: Array.isArray(parsed.transactions) ? parsed.transactions : [],
      budgets: Array.isArray(parsed.budgets) ? parsed.budgets : [],
      savings: Array.isArray(parsed.savings) ? parsed.savings : [],
      nextId: parsed.nextId || 2
    };
  } catch (e) {
    return { transactions: [], budgets: [], savings: [], nextId: 1 };
  }
}

function getProfiles() {
  try {
    const parsed = JSON.parse(localStorage.getItem('kb_profiles'));
    if (Array.isArray(parsed) && parsed.length > 0) return parsed;
  } catch (e) { }
  return [{ id: 'default', name: 'Główny profil' }];
}

function getActiveProfileId() {
  return localStorage.getItem('kb_active_profile') || 'default';
}

function getData() {
  const activeId = getActiveProfileId();
  return getValidData(localStorage.getItem(`kb_data_${activeId}`));
}

function saveData(data) {
  const activeId = getActiveProfileId();
  localStorage.setItem(`kb_data_${activeId}`, JSON.stringify(data));
}

// Profile management
window.switchProfile = function (id) {
  localStorage.setItem('kb_active_profile', id);
  initData(); // Ensure data structure exists
  refreshViews();
  renderProfilesUI();
}

function createProfile(name) {
  const profiles = getProfiles();
  const newId = 'profile_' + Date.now();
  profiles.push({ id: newId, name: name });
  localStorage.setItem('kb_profiles', JSON.stringify(profiles));

  // Set as active
  localStorage.setItem('kb_active_profile', newId);
  initData();
  refreshViews();
  renderProfilesUI();
}

window.deleteProfile = function (id) {
  let profiles = getProfiles();
  if (profiles.length <= 1) {
    alert('Nie możesz usunąć jedynego profilu.');
    return;
  }
  if (!confirm('Na pewno usunąć ten profil i wszystkie jego dane?')) return;

  profiles = profiles.filter(p => p.id !== id);
  localStorage.setItem('kb_profiles', JSON.stringify(profiles));
  localStorage.removeItem(`kb_data_${id}`);

  if (getActiveProfileId() === id) {
    localStorage.setItem('kb_active_profile', profiles[0].id);
  }

  initData();
  refreshViews();
  renderProfilesUI();
};

function renderProfilesUI() {
  const profiles = getProfiles();
  const activeId = getActiveProfileId();

  // Sidebar Select
  const select = document.getElementById('profileSelect');
  select.innerHTML = profiles.map(p =>
    `<option value="${p.id}" ${p.id === activeId ? 'selected' : ''}>${p.name}</option>`
  ).join('');

  // Modal List
  const list = document.getElementById('profilesList');
  list.innerHTML = profiles.map(p => `
    <div class="profile-item ${p.id === activeId ? 'active-profile' : ''}" style="cursor: pointer;" onclick="if('${p.id}' !== '${activeId}') { switchProfile('${p.id}'); closeModal('profileModal'); }">
      <div class="profile-item-info">
        <span style="font-size: 1.2rem;">${p.id === activeId ? '👤' : '👥'}</span>
        <span style="font-weight: 500;">${p.name}</span>
      </div>
      <div>
        ${p.id !== activeId ? `<button class="btn-delete" onclick="event.stopPropagation(); deleteProfile('${p.id}')" title="Usuń profil">🗑️</button>` : '<span style="font-size: 0.8rem; color: var(--text-muted); padding-right: 10px;">Aktywny</span>'}
      </div>
    </div>
  `).join('');
}

document.getElementById('profileSelect').addEventListener('change', (e) => {
  window.switchProfile(e.target.value);
});

document.getElementById('createProfileForm').addEventListener('submit', (e) => {
  e.preventDefault();
  const name = document.getElementById('newProfileName').value;
  if (name.trim()) {
    createProfile(name.trim());
    document.getElementById('createProfileForm').reset();
  }
});

// Formatters
const fmtPLN = (val) => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(val);
const fmtDate = (d) => new Date(d).toLocaleDateString('pl-PL', { day: '2-digit', month: 'short', year: 'numeric' });

// --- NAVIGATION & ROUTING ---
const pages = document.querySelectorAll('.page');
const navItems = document.querySelectorAll('.nav-item');
const pageTitle = document.getElementById('pageTitle');
const pageSubtitle = document.getElementById('pageSubtitle');

const pageTitles = {
  dashboard: { title: 'Dashboard', sub: 'Przegląd Twoich finansów' },
  transactions: { title: 'Transakcje', sub: 'Zarządzaj swoimi wpisami' },
  budgets: { title: 'Budżety', sub: 'Kontroluj swoje wydatki' },
  savings: { title: 'Cele', sub: 'Oszczędzaj na marzenia' },
  overview: { title: 'Przegląd Ogólny', sub: 'Wszystkie konta w jednym miejscu' }
};

navItems.forEach(item => {
  item.addEventListener('click', (e) => {
    e.preventDefault();
    const target = item.dataset.target;

    // Update active nav
    navItems.forEach(n => n.classList.remove('active'));
    item.classList.add('active');

    // Update active page
    pages.forEach(p => p.classList.remove('active'));
    document.getElementById(`page-${target}`).classList.add('active');

    // Update headers
    pageTitle.textContent = pageTitles[target].title;
    pageSubtitle.textContent = pageTitles[target].sub;

    // Refresh Data
    refreshViews();
    if (target === 'overview') renderOverview();
  });
});

// --- MODALS ---
function openModal(id) {
  document.getElementById(id).classList.add('active');
  if (id === 'transactionModal') {
    populateCategories('expense'); // default
    // Set today's date if empty
    const dateInput = document.getElementById('transDate');
    if (dateInput && !dateInput.value) {
      dateInput.value = new Date().toISOString().split('T')[0];
    }
  }
  if (id === 'budgetModal') populateCategories('expense', 'budgetCategory');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('active');
}

// Close modals on outside click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal(overlay.id);
  });
});

// Radio buttons for transaction type change
document.querySelectorAll('input[name="transType"]').forEach(radio => {
  radio.addEventListener('change', (e) => {
    populateCategories(e.target.value);
  });
});

function populateCategories(type, selectId = 'transCategory') {
  const select = document.getElementById(selectId);
  select.innerHTML = '';
  const filtered = DEFAULT_CATEGORIES.filter(c => c.type === type);
  filtered.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.textContent = `${c.icon} ${c.name}`;
    select.appendChild(opt);
  });
}

// --- RENDER LOGIC ---
let expenseChartInstance = null;

function refreshViews() {
  const data = getData();

  // Calculations
  let income = 0, expense = 0;

  data.transactions.forEach(t => {
    if (t.type === 'income') income += Number(t.amount);
    if (t.type === 'expense') expense += Number(t.amount);
  });

  const balance = income - expense;

  // Render Dashboard
  document.getElementById('dashIncome').textContent = fmtPLN(income);
  document.getElementById('dashExpense').textContent = fmtPLN(expense);
  document.getElementById('dashBalance').textContent = fmtPLN(balance);
  document.getElementById('dashBalance').style.color = balance >= 0 ? 'var(--success)' : 'var(--danger)';

  renderChart(data.transactions);
  renderRecent(data.transactions);

  // Render other pages
  renderTransactions(data.transactions);
  renderBudgets(data);
  renderSavings(data.savings);
}

function getCategory(id) {
  return DEFAULT_CATEGORIES.find(c => c.id == id) || { name: 'Inne', icon: '❓', color: '#666' };
}

function renderRecent(transactions) {
  const list = document.getElementById('dashRecentList');
  const recent = [...transactions].sort((a, b) => b.timestamp - a.timestamp).slice(0, 5);

  if (recent.length === 0) {
    list.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:20px;">Brak transakcji</p>';
    return;
  }

  list.innerHTML = recent.map(t => {
    const cat = getCategory(t.categoryId);
    const sign = t.type === 'income' ? '+' : '-';
    const color = t.type === 'income' ? 'var(--success)' : 'var(--danger)';
    return `
      <div class="recent-item">
        <div class="recent-info">
          <div class="recent-icon">${cat.icon}</div>
          <div>
            <div class="recent-desc">${t.description}</div>
            <div class="recent-cat">${fmtDate(t.date)} &bull; ${cat.name}</div>
          </div>
        </div>
        <div style="color:${color};font-weight:600;">${sign}${fmtPLN(t.amount)}</div>
      </div>
    `;
  }).join('');
}

function renderTransactions(transactions) {
  const tbody = document.getElementById('transactionsTableBody');
  const filterType = document.getElementById('filterType').value;
  const search = document.getElementById('searchTransaction').value.toLowerCase();

  let filtered = [...transactions].sort((a, b) => new Date(b.date) - new Date(a.date));

  if (filterType !== 'all') filtered = filtered.filter(t => t.type === filterType);
  if (search) filtered = filtered.filter(t => t.description.toLowerCase().includes(search));

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted)">Brak transakcji pasujących do kryteriów</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(t => {
    const cat = getCategory(t.categoryId);
    const sign = t.type === 'income' ? '+' : '-';
    const badgeCls = t.type === 'income' ? 'income' : 'expense';
    const typeLabel = t.type === 'income' ? 'Przychód' : 'Wydatek';

    return `
      <tr>
        <td>${fmtDate(t.date)}</td>
        <td style="font-weight:500">${t.description}</td>
        <td>${cat.icon} ${cat.name}</td>
        <td><span class="badge ${badgeCls}">${typeLabel}</span></td>
        <td style="font-weight:600">${sign}${fmtPLN(t.amount)}</td>
        <td>
          <button class="btn-delete" onclick="deleteTransaction(${t.id})" title="Usuń">🗑️</button>
        </td>
      </tr>
    `;
  }).join('');
}

function renderBudgets(data) {
  const grid = document.getElementById('budgetsGrid');
  const currentMonth = new Date().getMonth();
  const currentYear = new Date().getFullYear();

  if (data.budgets.length === 0) {
    grid.innerHTML = '<div style="grid-column: 1/-1;text-align:center;color:var(--text-muted);padding:2rem;">Brak limitów budżetowych. Ustaw pierwszy limit!</div>';
    return;
  }

  grid.innerHTML = data.budgets.map(b => {
    const cat = getCategory(b.categoryId);
    // Calculate spent
    let spent = 0;
    data.transactions.forEach(t => {
      const d = new Date(t.date);
      if (t.type === 'expense' && t.categoryId == b.categoryId && d.getMonth() === currentMonth && d.getFullYear() === currentYear) {
        spent += Number(t.amount);
      }
    });

    const pct = Math.min((spent / b.amount) * 100, 100);
    const color = pct < 75 ? 'var(--success)' : pct < 100 ? 'var(--warning)' : 'var(--danger)';

    return `
      <div class="glass-panel">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
          <h3 style="font-size:1.1rem;display:flex;align-items:center;gap:8px;">${cat.icon} ${cat.name}</h3>
          <button class="btn-delete" onclick="deleteBudget(${b.id})">🗑️</button>
        </div>
        <div class="progress-info">
          <span>Wydano: ${fmtPLN(spent)}</span>
          <span>Limit: ${fmtPLN(b.amount)}</span>
        </div>
        <div class="progress-container">
          <div class="progress-bar" style="width: ${pct}%; background-color: ${color}"></div>
        </div>
        <div style="text-align:right;font-size:0.8rem;color:${color}">${pct.toFixed(0)}%</div>
      </div>
    `;
  }).join('');
}

function renderSavings(savings) {
  const grid = document.getElementById('savingsGrid');
  if (savings.length === 0) {
    grid.innerHTML = '<div style="grid-column: 1/-1;text-align:center;color:var(--text-muted);padding:2rem;">Brak celów oszczędnościowych.</div>';
    return;
  }

  grid.innerHTML = savings.map(s => {
    const pct = Math.min((s.current / s.target) * 100, 100);
    return `
      <div class="glass-panel">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
          <h3 style="font-size:1.1rem;">🎯 ${s.name}</h3>
          <div style="display:flex; gap: 5px;">
            <button class="btn btn-sm btn-primary" onclick="openAddFundsModal(${s.id})" title="Wpłać środki">Wpłać</button>
            <button class="btn-delete" onclick="deleteSavings(${s.id})">🗑️</button>
          </div>
        </div>
        <div class="progress-info">
          <span>Zebrano: ${fmtPLN(s.current)}</span>
          <span>Cel: ${fmtPLN(s.target)}</span>
        </div>
        <div class="progress-container">
          <div class="progress-bar" style="width: ${pct}%; background-color: var(--primary)"></div>
        </div>
      </div>
    `;
  }).join('');
}

function renderChart(transactions) {
  const ctx = document.getElementById('expenseChart').getContext('2d');

  // Aggregate by category
  const categories = {};
  transactions.forEach(t => {
    if (!categories[t.categoryId]) categories[t.categoryId] = 0;
    categories[t.categoryId] += Number(t.amount);
  });

  const labels = [];
  const data = [];
  const bgColors = [];

  Object.keys(categories).forEach(catId => {
    const cat = getCategory(catId);
    labels.push(cat.name);
    data.push(categories[catId]);
    bgColors.push(cat.color);
  });

  if (expenseChartInstance) expenseChartInstance.destroy();

  if (data.length === 0) {
    // Empty chart placeholder logic could go here, but Chart.js handles empty gracefully enough
  }

  expenseChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: bgColors,
        borderWidth: 2,
        borderColor: '#0b0f19'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: { color: '#f3f4f6', font: { family: 'Inter' } }
        }
      }
    }
  });
}

// --- FORMS SUBMISSIONS ---

document.getElementById('transactionForm').addEventListener('submit', (e) => {
  e.preventDefault();
  const data = getData();
  const dateValue = document.getElementById('transDate').value || new Date().toISOString().split('T')[0];
  const newTrans = {
    id: data.nextId++,
    type: document.querySelector('input[name="transType"]:checked').value,
    amount: document.getElementById('transAmount').value,
    description: document.getElementById('transDesc').value,
    categoryId: document.getElementById('transCategory').value,
    date: dateValue,
    timestamp: Date.now()
  };
  data.transactions.push(newTrans);
  saveData(data);
  closeModal('transactionModal');
  document.getElementById('transactionForm').reset();
  refreshViews();
});

document.getElementById('budgetForm').addEventListener('submit', (e) => {
  e.preventDefault();
  const data = getData();
  const catId = document.getElementById('budgetCategory').value;
  const amount = document.getElementById('budgetLimit').value;

  // Check if exists
  const existingIndex = data.budgets.findIndex(b => b.categoryId == catId);
  if (existingIndex >= 0) {
    data.budgets[existingIndex].amount = amount;
  } else {
    data.budgets.push({ id: data.nextId++, categoryId: catId, amount: amount });
  }

  saveData(data);
  closeModal('budgetModal');
  document.getElementById('budgetForm').reset();
  refreshViews();
});

document.getElementById('savingsForm').addEventListener('submit', (e) => {
  e.preventDefault();
  const data = getData();
  data.savings.push({
    id: data.nextId++,
    name: document.getElementById('saveName').value,
    target: document.getElementById('saveTarget').value,
    current: document.getElementById('saveCurrent').value
  });

  saveData(data);
  closeModal('savingsModal');
  document.getElementById('savingsForm').reset();
  refreshViews();
});

// Add Funds
window.openAddFundsModal = function (id) {
  document.getElementById('addFundSaveId').value = id;
  document.getElementById('addFundAmount').value = '';
  openModal('addFundsModal');
}

document.getElementById('addFundsForm').addEventListener('submit', (e) => {
  e.preventDefault();
  const data = getData();
  const id = Number(document.getElementById('addFundSaveId').value);
  const amount = Number(document.getElementById('addFundAmount').value);

  const saveIndex = data.savings.findIndex(s => Number(s.id) === id);
  if (saveIndex >= 0) {
    data.savings[saveIndex].current = Number(data.savings[saveIndex].current) + amount;
    saveData(data);
    refreshViews();
  } else {
    alert("Błąd: Nie znaleziono celu oszczędnościowego.");
  }
  closeModal('addFundsModal');
});

// Filters
document.getElementById('filterType').addEventListener('change', () => renderTransactions(getData().transactions));
document.getElementById('searchTransaction').addEventListener('input', () => renderTransactions(getData().transactions));
document.getElementById('overviewFilterType').addEventListener('change', () => renderOverview());
document.getElementById('overviewSearch').addEventListener('input', () => renderOverview());

// Delete functions
window.deleteTransaction = function (id) {
  if (!confirm('Usunąć tę transakcję?')) return;
  const data = getData();
  data.transactions = data.transactions.filter(t => t.id !== id);
  saveData(data);
  refreshViews();
}

window.deleteBudget = function (id) {
  if (!confirm('Usunąć ten limit?')) return;
  const data = getData();
  data.budgets = data.budgets.filter(b => b.id !== id);
  saveData(data);
  refreshViews();
}

window.deleteSavings = function (id) {
  if (!confirm('Usunąć ten cel?')) return;
  const data = getData();
  data.savings = data.savings.filter(s => s.id !== id);
  saveData(data);
  refreshViews();
}

// Reset System
document.getElementById('resetDataBtn').addEventListener('click', () => {
  if (confirm('UWAGA! To usunie wszystkie Twoje dane ze wszystkich profili. Czy na pewno chcesz zresetować aplikację?')) {
    localStorage.clear();
    initData();
    renderProfilesUI();
    refreshViews();
  }
});

// (date logic merged into openModal above)

// ============================================================
// OVERVIEW PAGE
// ============================================================

function getAllProfilesData() {
  const profiles = getProfiles();
  const result = [];
  profiles.forEach((p, idx) => {
    const raw = localStorage.getItem(`kb_data_${p.id}`);
    const validData = getValidData(raw);
    validData.transactions.forEach(t => {
      result.push({ ...t, profileId: p.id, profileName: p.name, profileColor: PROFILE_COLORS[idx % PROFILE_COLORS.length] });
    });
  });
  return result;
}

let overviewCategoryChartInstance = null;
let overviewProfileChartInstance = null;

window.switchOverviewTab = function (profileId) {
  overviewSelectedProfile = profileId;
  renderOverview();
}

function renderOverview() {
  const profiles = getProfiles();
  const filterType = document.getElementById('overviewFilterType').value;
  const search = document.getElementById('overviewSearch').value.toLowerCase();

  // Render tabs
  const tabsContainer = document.getElementById('overviewTabs');
  tabsContainer.innerHTML = [
    { id: 'all', name: '🌐 Wszystkie' },
    ...profiles.map((p, i) => ({ id: p.id, name: p.name, color: PROFILE_COLORS[i % PROFILE_COLORS.length] }))
  ].map(tab => {
    const isActive = overviewSelectedProfile === tab.id;
    const color = tab.color || '#6366f1';
    const activeStyle = isActive
      ? 'border-color:' + color + ';color:' + color + ';background:' + color + '22;'
      : '';
    const badge = tab.id !== 'all'
      ? '<span class="profile-badge" style="background:' + color + '">' + tab.name.charAt(0).toUpperCase() + '</span>'
      : '';
    return '<button class="overview-tab ' + (isActive ? 'active' : '') + '" '
      + 'style="' + activeStyle + '" '
      + 'onclick="switchOverviewTab(\'' + tab.id + '\')">'
      + badge + ' ' + tab.name
      + '</button>';
  }).join('');

  // Gather data
  let allTrans = getAllProfilesData();
  if (overviewSelectedProfile !== 'all') {
    allTrans = allTrans.filter(t => t.profileId === overviewSelectedProfile);
  }

  // Summary cards
  let totalIncome = 0, totalExpense = 0;
  allTrans.forEach(t => {
    if (t.type === 'income') totalIncome += Number(t.amount);
    if (t.type === 'expense') totalExpense += Number(t.amount);
  });
  const totalBalance = totalIncome - totalExpense;

  const summaryEl = document.getElementById('overviewSummary');
  summaryEl.innerHTML = `
    <div class="summary-card income">
      <div class="card-icon">📈</div>
      <div class="card-info"><h3>Przychody</h3><p>${fmtPLN(totalIncome)}</p></div>
    </div>
    <div class="summary-card expense">
      <div class="card-icon">📉</div>
      <div class="card-info"><h3>Wydatki</h3><p>${fmtPLN(totalExpense)}</p></div>
    </div>
    <div class="summary-card balance">
      <div class="card-icon">💰</div>
      <div class="card-info"><h3>Bilans</h3><p style="color:${totalBalance >= 0 ? 'var(--success)' : 'var(--danger)'}">${fmtPLN(totalBalance)}</p></div>
    </div>
    <div class="summary-card" style="border-color:rgba(99,102,241,0.3)">
      <div class="card-icon" style="background:rgba(99,102,241,0.1);color:var(--primary)">📊</div>
      <div class="card-info"><h3>Transakcji</h3><p>${allTrans.length}</p></div>
    </div>
  `;

  // Profile chart (only when 'all' is selected)
  const profileChartContainer = document.getElementById('overviewProfileChart').parentElement.parentElement;
  if (overviewSelectedProfile === 'all') {
    profileChartContainer.style.display = 'block';
    renderOverviewProfileChart(profiles, allTrans);
  } else {
    profileChartContainer.style.display = 'none';
  }

  // Category chart (expenses only)
  renderOverviewCategoryChart(allTrans.filter(t => t.type === 'expense'));

  // Table
  let filtered = [...allTrans].sort((a, b) => new Date(b.date) - new Date(a.date));
  if (filterType !== 'all') filtered = filtered.filter(t => t.type === filterType);
  if (search) filtered = filtered.filter(t =>
    t.description.toLowerCase().includes(search) ||
    t.profileName.toLowerCase().includes(search)
  );

  const profileLabel = overviewSelectedProfile === 'all' ? 'Wszystkie Transakcje' :
    (profiles.find(p => p.id === overviewSelectedProfile)?.name || '') + ' – Transakcje';
  document.getElementById('overviewTableTitle').textContent = profileLabel;

  const tbody = document.getElementById('overviewTableBody');
  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted)">Brak transakcji</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(t => {
    const cat = getCategory(t.categoryId);
    const sign = t.type === 'income' ? '+' : '-';
    const badgeCls = t.type === 'income' ? 'income' : 'expense';
    const typeLabel = t.type === 'income' ? 'Przychód' : 'Wydatek';
    const profileColor = t.profileColor || '#6366f1';
    return `
      <tr>
        <td>
          <span class="profile-badge" style="background:${profileColor};display:inline-flex;margin-right:6px;">${t.profileName.charAt(0).toUpperCase()}</span>
          <span style="font-size:0.85rem">${t.profileName}</span>
        </td>
        <td>${fmtDate(t.date)}</td>
        <td style="font-weight:500">${t.description}</td>
        <td>${cat.icon} ${cat.name}</td>
        <td><span class="badge ${badgeCls}">${typeLabel}</span></td>
        <td style="font-weight:600;color:${t.type === 'income' ? 'var(--success)' : 'var(--danger)'}">${sign}${fmtPLN(t.amount)}</td>
      </tr>
    `;
  }).join('');
}

function renderOverviewCategoryChart(expenseTransactions) {
  const ctx = document.getElementById('overviewCategoryChart').getContext('2d');
  const categories = {};
  expenseTransactions.forEach(t => {
    if (!categories[t.categoryId]) categories[t.categoryId] = 0;
    categories[t.categoryId] += Number(t.amount);
  });
  const labels = [], data = [], colors = [];
  Object.keys(categories).forEach(catId => {
    const cat = getCategory(catId);
    labels.push(cat.name);
    data.push(categories[catId]);
    colors.push(cat.color);
  });
  if (overviewCategoryChartInstance) overviewCategoryChartInstance.destroy();
  overviewCategoryChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2, borderColor: '#0b0f19' }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'right', labels: { color: '#f3f4f6', font: { family: 'Inter' } } } }
    }
  });
}

function renderOverviewProfileChart(profiles, allTransactions) {
  const ctx = document.getElementById('overviewProfileChart').getContext('2d');
  const profileExpenses = profiles.map((p, i) => {
    const total = allTransactions
      .filter(t => t.profileId === p.id && t.type === 'expense')
      .reduce((sum, t) => sum + Number(t.amount), 0);
    return { name: p.name, total, color: PROFILE_COLORS[i % PROFILE_COLORS.length] };
  });
  if (overviewProfileChartInstance) overviewProfileChartInstance.destroy();
  overviewProfileChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: profileExpenses.map(p => p.name),
      datasets: [{
        label: 'Wydatki',
        data: profileExpenses.map(p => p.total),
        backgroundColor: profileExpenses.map(p => p.color + 'cc'),
        borderColor: profileExpenses.map(p => p.color),
        borderWidth: 2,
        borderRadius: 8
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,0.05)' } },
        y: { ticks: { color: '#9ca3af', callback: v => fmtPLN(v) }, grid: { color: 'rgba(255,255,255,0.05)' } }
      }
    }
  });
}

// Boot
initData();
renderProfilesUI();
refreshViews();
