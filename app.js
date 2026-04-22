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

function initData() {
  if (!localStorage.getItem('kb_data')) {
    const defaultData = {
      transactions: [
        {
          id: 1,
          type: 'income',
          amount: 10000,
          description: 'Bilans początkowy',
          categoryId: 2,
          date: new Date().toISOString().split('T')[0],
          timestamp: Date.now()
        }
      ],
      budgets: [],
      savings: [],
      nextId: 2
    };
    localStorage.setItem('kb_data', JSON.stringify(defaultData));
  }
}

function getData() {
  return JSON.parse(localStorage.getItem('kb_data'));
}

function saveData(data) {
  localStorage.setItem('kb_data', JSON.stringify(data));
}

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
  savings: { title: 'Cele', sub: 'Oszczędzaj na marzenia' }
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
  });
});

// --- MODALS ---
function openModal(id) {
  document.getElementById(id).classList.add('active');
  if (id === 'transactionModal') populateCategories('expense'); // default
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
  const currentMonth = new Date().getMonth();
  const currentYear = new Date().getFullYear();
  
  data.transactions.forEach(t => {
    const d = new Date(t.date);
    if (d.getMonth() === currentMonth && d.getFullYear() === currentYear) {
      if (t.type === 'income') income += Number(t.amount);
      if (t.type === 'expense') expense += Number(t.amount);
    }
  });

  const balance = income - expense;

  // Render Dashboard
  document.getElementById('dashIncome').textContent = fmtPLN(income);
  document.getElementById('dashExpense').textContent = fmtPLN(expense);
  document.getElementById('dashBalance').textContent = fmtPLN(balance);
  document.getElementById('dashBalance').style.color = balance >= 0 ? 'var(--success)' : 'var(--danger)';

  renderChart(data.transactions, currentMonth, currentYear);
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
          <button class="btn-delete" onclick="deleteSavings(${s.id})">🗑️</button>
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

function renderChart(transactions, month, year) {
  const ctx = document.getElementById('expenseChart').getContext('2d');
  
  // Aggregate expenses by category
  const expenses = {};
  transactions.forEach(t => {
    const d = new Date(t.date);
    if (t.type === 'expense' && d.getMonth() === month && d.getFullYear() === year) {
      if (!expenses[t.categoryId]) expenses[t.categoryId] = 0;
      expenses[t.categoryId] += Number(t.amount);
    }
  });

  const labels = [];
  const data = [];
  const bgColors = [];

  Object.keys(expenses).forEach(catId => {
    const cat = getCategory(catId);
    labels.push(cat.name);
    data.push(expenses[catId]);
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
  const newTrans = {
    id: data.nextId++,
    type: document.querySelector('input[name="transType"]:checked').value,
    amount: document.getElementById('transAmount').value,
    description: document.getElementById('transDesc').value,
    categoryId: document.getElementById('transCategory').value,
    date: document.getElementById('transDate').value,
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

// Filters
document.getElementById('filterType').addEventListener('change', () => renderTransactions(getData().transactions));
document.getElementById('searchTransaction').addEventListener('input', () => renderTransactions(getData().transactions));

// Delete functions
window.deleteTransaction = function(id) {
  if (!confirm('Usunąć tę transakcję?')) return;
  const data = getData();
  data.transactions = data.transactions.filter(t => t.id !== id);
  saveData(data);
  refreshViews();
}

window.deleteBudget = function(id) {
  if (!confirm('Usunąć ten limit?')) return;
  const data = getData();
  data.budgets = data.budgets.filter(b => b.id !== id);
  saveData(data);
  refreshViews();
}

window.deleteSavings = function(id) {
  if (!confirm('Usunąć ten cel?')) return;
  const data = getData();
  data.savings = data.savings.filter(s => s.id !== id);
  saveData(data);
  refreshViews();
}

// Reset System
document.getElementById('resetDataBtn').addEventListener('click', () => {
  if (confirm('UWAGA! To usunie wszystkie Twoje dane. Czy na pewno chcesz zresetować aplikację?')) {
    localStorage.removeItem('kb_data');
    initData();
    refreshViews();
  }
});

// Boot
initData();
refreshViews();
