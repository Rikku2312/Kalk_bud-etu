<?php require_once 'header.php'; ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px">
  <button class="btn btn-primary" id="addCatBtn">+ Nowa kategoria</button>
</div>

<!-- Tabs -->
<div class="tabs">
  <button class="tab-btn active" data-tab="expense">🔴 Wydatkowe</button>
  <button class="tab-btn" data-tab="income">💚 Przychodowe</button>
</div>

<div class="tab-panel active" id="tab-expense">
  <div class="grid-auto" id="catListExpense"></div>
</div>
<div class="tab-panel" id="tab-income">
  <div class="grid-auto" id="catListIncome"></div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="catModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="catModalTitle">Nowa kategoria</div>
      <button class="modal-close" id="closeCatModal">✕</button>
    </div>
    <div class="form-group">
      <label class="form-label">Typ kategorii</label>
      <div class="type-toggle">
        <input type="radio" name="catType" id="catTypeExpense" value="expense" checked>
        <label for="catTypeExpense">🔴 Wydatek</label>
        <input type="radio" name="catType" id="catTypeIncome" value="income">
        <label for="catTypeIncome">💚 Przychód</label>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Ikona (emoji)</label>
        <input type="text" class="form-control" id="catIcon" placeholder="💰" maxlength="4" value="💰">
      </div>
      <div class="form-group">
        <label class="form-label">Kolor</label>
        <input type="color" class="form-control" id="catColor" value="#6366f1" style="height:42px;padding:4px">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Nazwa kategorii *</label>
      <input type="text" class="form-control" id="catName" placeholder="np. Jedzenie">
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="cancelCatModal">Anuluj</button>
      <button class="btn btn-primary" id="saveCat">💾 Zapisz</button>
    </div>
  </div>
</div>

<script>
let editCatId = null;

// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-'+btn.dataset.tab).classList.add('active');
  });
});

async function loadCats() {
  const data = await apiFetch('categories');
  const cats = data.data || [];
  renderCats(cats.filter(c=>c.type==='expense'), 'catListExpense');
  renderCats(cats.filter(c=>c.type==='income'),  'catListIncome');
}

function renderCats(cats, containerId) {
  const el = document.getElementById(containerId);
  if (!cats.length) { el.innerHTML='<div class="empty-state"><div class="empty-icon">🏷️</div><p>Brak kategorii</p></div>'; return; }
  el.innerHTML = cats.map(c => `
    <div class="card" style="border-left:4px solid ${c.color};padding:16px">
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:28px">${c.icon}</span>
        <div style="flex:1">
          <div style="font-weight:700;font-size:15px">${c.name}</div>
          <div style="font-size:11px;color:var(--text-muted)">${c.type==='income'?'Przychód':'Wydatek'}</div>
        </div>
        <div style="display:flex;gap:4px">
          <button class="btn btn-ghost btn-sm btn-icon" onclick="editCat(${c.id},'${c.name.replace(/'/g,"\\'")}','${c.type}','${c.icon}','${c.color}')" title="Edytuj">✏️</button>
          <button class="btn btn-ghost btn-sm btn-icon" onclick="deleteCat(${c.id})" title="Usuń">🗑️</button>
        </div>
      </div>
    </div>
  `).join('');
}

// Modal
document.getElementById('addCatBtn').addEventListener('click', () => {
  editCatId = null;
  document.getElementById('catModalTitle').textContent = 'Nowa kategoria';
  document.getElementById('catTypeExpense').checked = true;
  document.getElementById('catIcon').value  = '💰';
  document.getElementById('catColor').value = '#6366f1';
  document.getElementById('catName').value  = '';
  document.getElementById('catModal').classList.add('open');
});

function editCat(id, name, type, icon, color) {
  editCatId = id;
  document.getElementById('catModalTitle').textContent = 'Edytuj kategorię';
  document.getElementById(type==='income'?'catTypeIncome':'catTypeExpense').checked = true;
  document.getElementById('catIcon').value  = icon;
  document.getElementById('catColor').value = color;
  document.getElementById('catName').value  = name;
  document.getElementById('catModal').classList.add('open');
}

document.getElementById('closeCatModal').addEventListener('click',  () => document.getElementById('catModal').classList.remove('open'));
document.getElementById('cancelCatModal').addEventListener('click', () => document.getElementById('catModal').classList.remove('open'));

document.getElementById('saveCat').addEventListener('click', async () => {
  const payload = {
    name:  document.getElementById('catName').value.trim(),
    type:  document.querySelector('input[name="catType"]:checked').value,
    icon:  document.getElementById('catIcon').value.trim()  || '💰',
    color: document.getElementById('catColor').value,
  };
  if (!payload.name) { toast('Podaj nazwę kategorii.','error'); return; }
  const url  = editCatId ? `api.php?resource=categories&id=${editCatId}` : 'api.php?resource=categories';
  const meth = editCatId ? 'PUT' : 'POST';
  const res  = await fetch(url, {method:meth,headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  const json = await res.json();
  if (json.error) { toast(json.error,'error'); return; }
  toast(editCatId?'Kategoria zaktualizowana.':'Kategoria dodana.','success');
  document.getElementById('catModal').classList.remove('open');
  loadCats();
});

async function deleteCat(id) {
  if (!confirm('Usunąć kategorię? Transakcje tej kategorii zostaną bez przypisania.')) return;
  await fetch(`api.php?resource=categories&id=${id}`,{method:'DELETE'});
  toast('Kategoria usunięta.','success'); loadCats();
}

loadCats();
</script>

<?php require_once 'footer.php'; ?>
