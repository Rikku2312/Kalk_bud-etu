<?php require_once 'header.php'; ?>

<!-- Stats Grid -->
<div class="stats-grid" id="statsGrid">
  <div class="stat-card">
    <div class="stat-card-header">
      <span class="stat-label">Przychody</span>
      <div class="stat-icon" style="background:rgba(16,185,129,.15)">💚</div>
    </div>
    <div class="stat-value amount-income" id="s-income">—</div>
    <div class="stat-change" id="s-income-lbl">bieżący miesiąc</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header">
      <span class="stat-label">Wydatki</span>
      <div class="stat-icon" style="background:rgba(239,68,68,.15)">🔴</div>
    </div>
    <div class="stat-value amount-expense" id="s-expense">—</div>
    <div class="stat-change" id="s-expense-lbl">bieżący miesiąc</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header">
      <span class="stat-label">Bilans</span>
      <div class="stat-icon" style="background:rgba(99,102,241,.15)">⚖️</div>
    </div>
    <div class="stat-value" id="s-balance">—</div>
    <div class="stat-change" id="s-balance-lbl">przychody − wydatki</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header">
      <span class="stat-label">Oszczędności</span>
      <div class="stat-icon" style="background:rgba(245,158,11,.15)">🏦</div>
    </div>
    <div class="stat-value" id="s-savings" style="color:var(--yellow)">—</div>
    <div class="stat-change">aktywne cele</div>
  </div>
</div>

<!-- Month navigation -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div class="month-nav">
    <button class="btn btn-ghost btn-sm" id="prevMonth">‹ Poprzedni</button>
    <span class="month-label" id="monthLabel">—</span>
    <button class="btn btn-ghost btn-sm" id="nextMonth">Następny ›</button>
  </div>
  <button class="btn btn-ghost btn-sm" id="todayBtn">Dzisiaj</button>
</div>

<!-- Charts row -->
<div class="grid-2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">📈 Trend przychodów i wydatków</div>
        <div class="card-subtitle">Ostatnie 12 miesięcy</div>
      </div>
    </div>
    <div class="chart-container" style="height:260px">
      <canvas id="trendChart"></canvas>
    </div>
  </div>
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">🍩 Wydatki według kategorii</div>
        <div class="card-subtitle">Bieżący miesiąc</div>
      </div>
    </div>
    <div class="chart-container" style="height:260px">
      <canvas id="categoryChart"></canvas>
    </div>
  </div>
</div>

<!-- Recent transactions + Budget -->
<div class="grid-2">
  <div class="card">
    <div class="card-header">
      <div class="card-title">🕐 Ostatnie transakcje</div>
      <a href="transactions.php" class="btn btn-ghost btn-sm">Zobacz wszystkie</a>
    </div>
    <div id="recentList"><div class="empty-state"><div class="empty-icon">⏳</div><p>Ładowanie…</p></div></div>
  </div>
  <div class="card">
    <div class="card-header">
      <div class="card-title">🎯 Budżet miesięczny</div>
      <a href="budget.php" class="btn btn-ghost btn-sm">Zarządzaj</a>
    </div>
    <div id="budgetList"><div class="empty-state"><div class="empty-icon">⏳</div><p>Ładowanie…</p></div></div>
  </div>
</div>

<script>
// ── State ──────────────────────────────────────────────────
let curMonth = new Date().getMonth() + 1;
let curYear  = new Date().getFullYear();
let trendChart = null, categoryChart = null;

const MONTH_NAMES = ['Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec',
                     'Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'];

// ── Init ───────────────────────────────────────────────────
document.getElementById('prevMonth').addEventListener('click', () => { curMonth--; if(curMonth<1){curMonth=12;curYear--;} loadAll(); });
document.getElementById('nextMonth').addEventListener('click', () => { curMonth++; if(curMonth>12){curMonth=1;curYear++;} loadAll(); });
document.getElementById('todayBtn').addEventListener('click',  () => { curMonth=new Date().getMonth()+1; curYear=new Date().getFullYear(); loadAll(); });

async function loadAll() {
  document.getElementById('monthLabel').textContent = `${MONTH_NAMES[curMonth-1]} ${curYear}`;
  try {
    await Promise.all([loadStats(), loadBudgets()]);
  } catch (e) {
    console.error('loadAll failed:', e);
    toast('Nie udało się załadować danych.', 'error');
  }
}

// ── Stats ──────────────────────────────────────────────────
async function loadStats() {
  const data = await apiFetch('stats', { month: curMonth, year: curYear });

  const income  = data.summary?.income  ?? 0;
  const expense = data.summary?.expense ?? 0;
  const balance = data.summary?.balance ?? 0;

  document.getElementById('s-income').textContent  = fmtPLN(income);
  document.getElementById('s-expense').textContent = fmtPLN(expense);
  document.getElementById('s-balance').textContent = fmtPLN(balance);
  document.getElementById('s-balance').style.color = balance >= 0 ? 'var(--green)' : 'var(--red)';

  // Savings goals count
  const savData = await apiFetch('savings');
  document.getElementById('s-savings').textContent = (savData.data?.length ?? 0) + ' celów';

  // Charts
  buildTrendChart(data.trend ?? []);
  buildCategoryChart(data.by_category ?? []);
  buildRecentList(data.recent ?? []);
}

// ── Trend chart ────────────────────────────────────────────
function buildTrendChart(trend) {
  const labels = [], incomeData = [], expenseData = [];
  const map = {};
  trend.forEach(r => {
    const key = `${r.y}-${String(r.m).padStart(2,'0')}`;
    if (!map[key]) map[key] = { income: 0, expense: 0 };
    map[key][r.type] = parseFloat(r.total);
  });
  Object.keys(map).sort().forEach(k => {
    const [y, m] = k.split('-');
    labels.push(MONTH_NAMES[parseInt(m)-1].substring(0,3) + ' ' + y);
    incomeData.push(map[k].income);
    expenseData.push(map[k].expense);
  });

  if (trendChart) trendChart.destroy();
  trendChart = new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Przychody', data: incomeData,  backgroundColor: 'rgba(16,185,129,.7)', borderRadius: 6, borderSkipped: false },
        { label: 'Wydatki',   data: expenseData, backgroundColor: 'rgba(239,68,68,.7)',  borderRadius: 6, borderSkipped: false },
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { labels: { color: '#f0f0f5', font: { family: 'Inter' } } }, tooltip: { callbacks: { label: ctx => fmtPLN(ctx.raw) } } },
      scales: {
        x: { ticks: { color: '#8888aa' }, grid: { color: 'rgba(255,255,255,.05)' } },
        y: { ticks: { color: '#8888aa', callback: v => fmtPLN(v) }, grid: { color: 'rgba(255,255,255,.05)' } }
      }
    }
  });
}

// ── Category doughnut ──────────────────────────────────────
function buildCategoryChart(cats) {
  const expenses = cats.filter(c => c.type === 'expense');
  if (!expenses.length) {
    if (categoryChart) categoryChart.destroy();
    return;
  }
  const labels = expenses.map(c => c.icon + ' ' + c.name);
  const values = expenses.map(c => parseFloat(c.total));
  const colors = expenses.map(c => c.color || '#6366f1');

  if (categoryChart) categoryChart.destroy();
  categoryChart = new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: { labels, datasets: [{ data: values, backgroundColor: colors, borderColor: '#111118', borderWidth: 3, hoverOffset: 8 }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: { position: 'right', labels: { color: '#f0f0f5', font: { family: 'Inter', size: 11 }, padding: 12, boxWidth: 12 } },
        tooltip: { callbacks: { label: ctx => `${ctx.label}: ${fmtPLN(ctx.raw)}` } }
      }
    }
  });
}

// ── Recent list ────────────────────────────────────────────
function buildRecentList(rows) {
  const el = document.getElementById('recentList');
  if (!rows.length) { el.innerHTML = '<div class="empty-state"><div class="empty-icon">💸</div><p>Brak transakcji</p></div>'; return; }
  el.innerHTML = rows.map(r => `
    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
      <span style="font-size:22px">${r.icon || '💰'}</span>
      <div style="flex:1">
        <div style="font-weight:600;font-size:13px">${r.description || r.category_name || '—'}</div>
        <div style="font-size:11px;color:var(--text-muted)">${fmtDate(r.date)} · ${r.category_name || '—'}</div>
      </div>
      <span class="${r.type==='income'?'amount-income':'amount-expense'}" style="font-size:14px">
        ${r.type==='income'?'+':'−'}${fmtPLN(r.amount)}
      </span>
    </div>
  `).join('');
}

// ── Budget overview ────────────────────────────────────────
async function loadBudgets() {
  const data = await apiFetch('budgets', { month: curMonth, year: curYear });
  const el   = document.getElementById('budgetList');
  if (!data.data?.length) { el.innerHTML = '<div class="empty-state"><div class="empty-icon">🎯</div><p>Brak budżetów na ten miesiąc</p></div>'; return; }
  el.innerHTML = data.data.map(b => {
    const pct = Math.min((b.spent / b.amount) * 100, 100).toFixed(0);
    const cls = pct < 75 ? 'progress-ok' : pct < 100 ? 'progress-warn' : 'progress-over';
    return `
      <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
          <span style="font-size:13px">${b.icon} ${b.category_name}</span>
          <span style="font-size:12px;color:var(--text-muted)">${fmtPLN(b.spent)} / ${fmtPLN(b.amount)}</span>
        </div>
        <div class="progress"><div class="progress-bar ${cls}" style="width:${pct}%"></div></div>
      </div>`;
  }).join('');
}

loadAll();
</script>

<?php require_once 'footer.php'; ?>
