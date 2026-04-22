<?php require_once 'header.php'; ?>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:20px">
  <div class="filters-bar" style="margin-bottom:0">
    <input type="text" class="form-control" id="searchInput" placeholder="🔍 Szukaj opisu…" style="width:200px">
    <select class="form-control" id="filterType">
      <option value="">Wszystkie typy</option>
      <option value="income">💚 Przychody</option>
      <option value="expense">🔴 Wydatki</option>
    </select>
    <select class="form-control" id="filterCategory">
      <option value="">Wszystkie kategorie</option>
    </select>
    <input type="month" class="form-control" id="filterMonth" value="<?= date('Y-m') ?>">
    <button class="btn btn-ghost btn-sm" id="clearFilters">Wyczyść</button>
    <button class="btn btn-primary" id="addTransBtn" style="margin-left:auto">+ Nowa transakcja</button>
  </div>
</div>

<!-- Summary bar -->
<div class="stats-grid" style="margin-bottom:20px" id="filterSummary">
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Przychody (filtr)</span><span>💚</span></div>
    <div class="stat-value amount-income" id="flt-income">—</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Wydatki (filtr)</span><span>🔴</span></div>
    <div class="stat-value amount-expense" id="flt-expense">—</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Bilans (filtr)</span><span>⚖️</span></div>
    <div class="stat-value" id="flt-balance">—</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Liczba</span><span>🔢</span></div>
    <div class="stat-value" id="flt-count" style="color:var(--cyan)">—</div>
  </div>
</div>

<!-- Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title">📋 Lista transakcji</div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-ghost btn-sm" id="exportCSV">⬇ Eksport CSV</button>
    </div>
  </div>
  <div class="table-wrap">
    <table id="transTable">
      <thead>
        <tr>
          <th>Data</th>
          <th>Opis</th>
          <th>Kategoria</th>
          <th>Typ</th>
          <th style="text-align:right">Kwota</th>
          <th style="text-align:center">Akcje</th>
        </tr>
      </thead>
      <tbody id="transBody">
        <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted)">⏳ Ładowanie…</td></tr>
      </tbody>
    </table>
  </div>
  <!-- Pagination -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px;flex-wrap:wrap;gap:8px">
    <span id="pageInfo" style="font-size:12px;color:var(--text-muted)"></span>
    <div style="display:flex;gap:8px">
      <button class="btn btn-ghost btn-sm" id="prevPage">‹ Poprz.</button>
      <button class="btn btn-ghost btn-sm" id="nextPage">Nast. ›</button>
    </div>
  </div>
</div>

<!-- ── Modal: Add / Edit Transaction ── -->
<div class="modal-overlay" id="transModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Nowa transakcja</div>
      <button class="modal-close" id="closeModal">✕</button>
    </div>

    <!-- Type toggle -->
    <div class="form-group">
      <label class="form-label">Typ</label>
      <div class="type-toggle">
        <input type="radio" name="type" id="typeExpense" value="expense" checked>
        <label for="typeExpense">🔴 Wydatek</label>
        <input type="radio" name="type" id="typeIncome" value="income">
        <label for="typeIncome">💚 Przychód</label>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="fAmount">Kwota (PLN) *</label>
        <input type="number" class="form-control" id="fAmount" min="0.01" step="0.01" placeholder="0,00">
      </div>
      <div class="form-group">
        <label class="form-label" for="fDate">Data *</label>
        <input type="date" class="form-control" id="fDate" value="<?= date('Y-m-d') ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="fCategory">Kategoria</label>
      <select class="form-control" id="fCategory"><option value="">— bez kategorii —</option></select>
    </div>

    <div class="form-group">
      <label class="form-label" for="fDesc">Opis</label>
      <input type="text" class="form-control" id="fDesc" placeholder="Np. Biedronka, przelew…">
    </div>

    <div class="form-group">
      <label class="form-label" for="fNote">Notatka (opcjonalnie)</label>
      <textarea class="form-control" id="fNote" placeholder="Dodatkowe uwagi…"></textarea>
    </div>

    <div class="modal-footer">
      <button class="btn btn-ghost" id="cancelModal">Anuluj</button>
      <button class="btn btn-primary" id="saveModal">💾 Zapisz</button>
    </div>
  </div>
</div>

<script>
let allCategories = [];
let editingId     = null;
let currentOffset = 0;
const LIMIT       = 20;

// ── Load categories ────────────────────────────────────────
async function loadCategories() {
  const data = await apiFetch('categories');
  allCategories = data.data || [];
  const fCat = document.getElementById('filterCategory');
  allCategories.forEach(c => {
    fCat.add(new Option(`${c.icon} ${c.name}`, c.id));
  });
}

function updateModalCategories(type) {
  const sel = document.getElementById('fCategory');
  const val = sel.value;
  sel.innerHTML = '<option value="">— bez kategorii —</option>';
  
  const filtered = allCategories.filter(c => c.type === type);
  if (allCategories.length > 0 && filtered.length === 0) {
    console.warn('No categories found for type:', type);
  }

  filtered.forEach(c => {
    const opt = new Option(`${c.icon} ${c.name}`, c.id);
    sel.add(opt);
  });
  sel.value = val;
}

// ── Filter helpers ─────────────────────────────────────────
function getFilters() {
  const [y,m] = (document.getElementById('filterMonth').value || '').split('-');
  return {
    search:      document.getElementById('searchInput').value.trim(),
    type:        document.getElementById('filterType').value,
    category_id: document.getElementById('filterCategory').value,
    month: m, year: y,
    limit:  LIMIT,
    offset: currentOffset,
  };
}

// ── Load transactions ──────────────────────────────────────
async function loadTrans() {
  const params = getFilters();
  const data   = await apiFetch('transactions', params);
  const rows   = data.data || [];
  const total  = data.total || 0;

  // Summary
  let inc = 0, exp = 0;
  rows.forEach(r => r.type === 'income' ? inc += +r.amount : exp += +r.amount);
  document.getElementById('flt-income').textContent  = fmtPLN(inc);
  document.getElementById('flt-expense').textContent = fmtPLN(exp);
  const bal = inc - exp;
  const balEl = document.getElementById('flt-balance');
  balEl.textContent = fmtPLN(bal);
  balEl.style.color = bal >= 0 ? 'var(--green)' : 'var(--red)';
  document.getElementById('flt-count').textContent = total + ' szt.';

  // Pagination info
  const from = total ? currentOffset + 1 : 0;
  const to   = Math.min(currentOffset + LIMIT, total);
  document.getElementById('pageInfo').textContent = `Pokazuję ${from}–${to} z ${total}`;
  document.getElementById('prevPage').disabled = currentOffset === 0;
  document.getElementById('nextPage').disabled = currentOffset + LIMIT >= total;

  // Table body
  const tbody = document.getElementById('transBody');
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">🔍</div><p>Brak transakcji spełniających kryteria</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = rows.map(r => `
    <tr>
      <td style="white-space:nowrap;color:var(--text-muted);font-size:12px">${fmtDate(r.date)}</td>
      <td>
        <div style="font-weight:500">${r.description || '—'}</div>
        ${r.note ? `<div style="font-size:11px;color:var(--text-muted)">${r.note}</div>` : ''}
      </td>
      <td>
        ${r.category_name
          ? `<span class="cat-pill" style="border:1px solid ${r.category_color}22;color:${r.category_color}">${r.category_icon} ${r.category_name}</span>`
          : '<span style="color:var(--text-muted)">—</span>'}
      </td>
      <td><span class="badge ${r.type==='income'?'badge-income':'badge-expense'}">${r.type==='income'?'💚 Przychód':'🔴 Wydatek'}</span></td>
      <td style="text-align:right;font-weight:700" class="${r.type==='income'?'amount-income':'amount-expense'}">
        ${r.type==='income'?'+':'−'}${fmtPLN(r.amount)}
      </td>
      <td style="text-align:center;white-space:nowrap">
        <button class="btn btn-ghost btn-sm btn-icon" onclick="editTrans(${r.id})" title="Edytuj">✏️</button>
        <button class="btn btn-ghost btn-sm btn-icon" onclick="deleteTrans(${r.id})" title="Usuń">🗑️</button>
      </td>
    </tr>
  `).join('');
}

// ── Pagination ─────────────────────────────────────────────
document.getElementById('prevPage').addEventListener('click', () => { currentOffset = Math.max(0, currentOffset-LIMIT); loadTrans(); });
document.getElementById('nextPage').addEventListener('click', () => { currentOffset += LIMIT; loadTrans(); });

// ── Filters ────────────────────────────────────────────────
['searchInput','filterType','filterCategory','filterMonth'].forEach(id => {
  document.getElementById(id).addEventListener('change', () => { currentOffset=0; loadTrans(); });
});
document.getElementById('searchInput').addEventListener('input', () => { currentOffset=0; loadTrans(); });
document.getElementById('clearFilters').addEventListener('click', () => {
  document.getElementById('searchInput').value = '';
  document.getElementById('filterType').value = '';
  document.getElementById('filterCategory').value = '';
  document.getElementById('filterMonth').value = '<?= date('Y-m') ?>';
  currentOffset = 0; loadTrans();
});

// ── Modal ──────────────────────────────────────────────────
function openModal(id = null, data = null) {
  editingId = id;
  document.getElementById('modalTitle').textContent = id ? 'Edytuj transakcję' : 'Nowa transakcja';
  
  const type = data ? data.type : 'expense';
  document.getElementById(type === 'income' ? 'typeIncome' : 'typeExpense').checked = true;
  
  updateModalCategories(type);
  
  if (data) {
    document.getElementById('fAmount').value   = data.amount;
    document.getElementById('fDate').value     = data.date;
    document.getElementById('fCategory').value = data.category_id || '';
    document.getElementById('fDesc').value     = data.description || '';
    document.getElementById('fNote').value     = data.note || '';
  } else {
    document.getElementById('fAmount').value   = '';
    document.getElementById('fDate').value     = new Date().toISOString().split('T')[0];
    document.getElementById('fCategory').value = '';
    document.getElementById('fDesc').value     = '';
    document.getElementById('fNote').value     = '';
  }
  document.getElementById('transModal').classList.add('open');
}
function closeModal() { document.getElementById('transModal').classList.remove('open'); }

document.getElementById('addTransBtn').addEventListener('click', () => openModal());
document.getElementById('closeModal').addEventListener('click',  closeModal);
document.getElementById('cancelModal').addEventListener('click', closeModal);

// ── Edit ───────────────────────────────────────────────────
async function editTrans(id) {
  // Fetch all to find the one we need (simpler than adding a single-fetch API endpoint now)
  const data = await apiFetch('transactions', { limit: 500 });
  const row  = (data.data || []).find(r => r.id == id);
  if (!row) {
    toast('Nie znaleziono transakcji.', 'error');
    return;
  }
  openModal(id, row);
}

// ── Save ───────────────────────────────────────────────────
document.getElementById('saveModal').addEventListener('click', async () => {
  const type = document.querySelector('input[name="type"]:checked').value;
  const payload = {
    type,
    amount:      document.getElementById('fAmount').value,
    date:        document.getElementById('fDate').value,
    category_id: document.getElementById('fCategory').value || null,
    description: document.getElementById('fDesc').value,
    note:        document.getElementById('fNote').value,
  };
  if (!payload.amount || !payload.date) { toast('Uzupełnij wymagane pola.','error'); return; }

  const url  = editingId ? `api.php?resource=transactions&id=${editingId}` : 'api.php?resource=transactions';
  const meth = editingId ? 'PUT' : 'POST';
  const res  = await fetch(url, { method: meth, headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  const json = await res.json();
  if (json.error) { toast(json.error,'error'); return; }
  toast(editingId ? 'Transakcja zaktualizowana.' : 'Transakcja dodana.','success');
  closeModal(); currentOffset=0; loadTrans();
});

// ── Delete ─────────────────────────────────────────────────
async function deleteTrans(id) {
  if (!confirm('Usunąć tę transakcję?')) return;
  const res  = await fetch(`api.php?resource=transactions&id=${id}`, { method:'DELETE' });
  const json = await res.json();
  toast(json.error ? json.error : 'Transakcja usunięta.', json.error ? 'error' : 'success');
  loadTrans();
}

// ── Export CSV ─────────────────────────────────────────────
document.getElementById('exportCSV').addEventListener('click', async () => {
  const params = getFilters();
  params.limit = 9999; params.offset = 0;
  const data = await apiFetch('transactions', params);
  const rows = data.data || [];
  const header = ['ID','Data','Typ','Kategoria','Opis','Kwota','Notatka'];
  const lines  = rows.map(r => [r.id,r.date,r.type,r.category_name||'',r.description||'',r.amount,r.note||''].join(';'));
  const csv = '\uFEFF' + [header.join(';'), ...lines].join('\n');
  const a   = document.createElement('a');
  a.href    = URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8'}));
  a.download = `transakcje_${new Date().toISOString().slice(0,10)}.csv`;
  a.click();
  toast('Eksport CSV gotowy!','success');
});

// ── Update category options on type change ─────────────────
document.querySelectorAll('input[name="type"]').forEach(r => {
  r.addEventListener('change', () => {
    updateModalCategories(r.value);
  });
});

// ── Init ───────────────────────────────────────────────────
(async () => { await loadCategories(); loadTrans(); })();
</script>

<?php require_once 'footer.php'; ?>
