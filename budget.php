<?php require_once 'header.php'; ?>

<!-- Month nav -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap">
  <div class="month-nav">
    <button class="btn btn-ghost btn-sm" id="prevMonth">‹ Poprzedni</button>
    <span class="month-label" id="monthLabel" style="font-weight:700;font-size:16px">—</span>
    <button class="btn btn-ghost btn-sm" id="nextMonth">Następny ›</button>
  </div>
  <button class="btn btn-primary" id="addBudgetBtn" style="margin-left:auto">+ Ustaw budżet</button>
</div>

<!-- Overview stats -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Zaplanowane</span><span>📋</span></div>
    <div class="stat-value" id="b-planned" style="color:var(--accent)">—</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Wydane</span><span>💸</span></div>
    <div class="stat-value amount-expense" id="b-spent">—</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Pozostało</span><span>✅</span></div>
    <div class="stat-value" id="b-left">—</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">% wykorzystania</span><span>📊</span></div>
    <div class="stat-value" id="b-pct" style="color:var(--yellow)">—</div>
  </div>
</div>

<!-- Budget chart + table -->
<div class="grid-2">
  <div class="card">
    <div class="card-header">
      <div class="card-title">📊 Budżet vs Wydatki</div>
    </div>
    <div class="chart-container" style="height:320px">
      <canvas id="budgetChart"></canvas>
    </div>
  </div>
  <div class="card">
    <div class="card-header">
      <div class="card-title">🗂 Szczegóły budżetów</div>
    </div>
    <div id="budgetDetails"></div>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="budgetModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Ustaw budżet kategorii</div>
      <button class="modal-close" id="closeBudgetModal">✕</button>
    </div>
    <div class="form-group">
      <label class="form-label">Kategoria wydatkowa</label>
      <select class="form-control" id="bCategory"></select>
    </div>
    <div class="form-group">
      <label class="form-label">Limit (PLN)</label>
      <input type="number" class="form-control" id="bAmount" min="1" step="1" placeholder="np. 500">
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="cancelBudgetModal">Anuluj</button>
      <button class="btn btn-primary" id="saveBudget">💾 Zapisz</button>
    </div>
  </div>
</div>

<script>
let curMonth = new Date().getMonth()+1;
let curYear  = new Date().getFullYear();
const MONTHS = ['Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec','Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'];
let budgetChart = null;

document.getElementById('prevMonth').addEventListener('click', () => { curMonth--; if(curMonth<1){curMonth=12;curYear--;} load(); });
document.getElementById('nextMonth').addEventListener('click', () => { curMonth++; if(curMonth>12){curMonth=1;curYear++;} load(); });

async function load() {
  document.getElementById('monthLabel').textContent = `${MONTHS[curMonth-1]} ${curYear}`;
  const data = await apiFetch('budgets', {month:curMonth, year:curYear});
  const rows = data.data || [];

  // Stats
  const planned = rows.reduce((s,r)=>s+ +r.amount,0);
  const spent   = rows.reduce((s,r)=>s+ +r.spent,0);
  const left    = planned - spent;
  const pct     = planned ? ((spent/planned)*100).toFixed(1) : 0;

  document.getElementById('b-planned').textContent = fmtPLN(planned);
  document.getElementById('b-spent').textContent   = fmtPLN(spent);
  const leftEl = document.getElementById('b-left');
  leftEl.textContent = fmtPLN(left);
  leftEl.style.color = left>=0 ? 'var(--green)' : 'var(--red)';
  document.getElementById('b-pct').textContent = pct + '%';

  // Chart
  if (budgetChart) budgetChart.destroy();
  if (rows.length) {
    budgetChart = new Chart(document.getElementById('budgetChart'), {
      type: 'bar',
      data: {
        labels: rows.map(r => r.icon+' '+r.category_name),
        datasets: [
          { label:'Budżet', data: rows.map(r=>+r.amount), backgroundColor:'rgba(99,102,241,.5)', borderRadius:6, borderSkipped:false },
          { label:'Wydane', data: rows.map(r=>+r.spent),  backgroundColor: rows.map(r => +r.spent/+r.amount>=1?'rgba(239,68,68,.8)':+r.spent/+r.amount>=.75?'rgba(245,158,11,.8)':'rgba(16,185,129,.8)'), borderRadius:6, borderSkipped:false },
        ]
      },
      options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ labels:{color:'#f0f0f5',font:{family:'Inter'}} }, tooltip:{ callbacks:{label:ctx=>fmtPLN(ctx.raw)} } },
        scales:{
          x:{ ticks:{color:'#8888aa'}, grid:{color:'rgba(255,255,255,.05)'} },
          y:{ ticks:{color:'#8888aa',callback:v=>fmtPLN(v)}, grid:{color:'rgba(255,255,255,.05)'} }
        }
      }
    });
  }

  // Details
  const el = document.getElementById('budgetDetails');
  if (!rows.length) { el.innerHTML='<div class="empty-state"><div class="empty-icon">🎯</div><p>Brak budżetów. Kliknij "Ustaw budżet".</p></div>'; return; }
  el.innerHTML = rows.map(r => {
    const pct = Math.min((+r.spent / +r.amount)*100,100).toFixed(0);
    const cls  = pct<75?'progress-ok':pct<100?'progress-warn':'progress-over';
    const statusIcon = pct>=100?'🔴':pct>=75?'🟡':'🟢';
    return `<div style="margin-bottom:18px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
        <span style="font-size:14px;font-weight:600">${r.icon} ${r.category_name} ${statusIcon}</span>
        <div style="display:flex;gap:8px;align-items:center">
          <span style="font-size:12px;color:var(--text-muted)">${fmtPLN(r.spent)} / ${fmtPLN(r.amount)}</span>
          <button class="btn btn-ghost btn-sm btn-icon" onclick="deleteBudget(${r.id})" title="Usuń">🗑️</button>
        </div>
      </div>
      <div class="progress"><div class="progress-bar ${cls}" style="width:${pct}%"></div></div>
      <div style="font-size:11px;color:var(--text-muted);margin-top:4px">${pct}% wykorzystania · pozostało ${fmtPLN(+r.amount - +r.spent)}</div>
    </div>`;
  }).join('');
}

// Modal
document.getElementById('addBudgetBtn').addEventListener('click', async () => {
  const cats = await apiFetch('categories', {type:'expense'});
  const sel  = document.getElementById('bCategory');
  sel.innerHTML = '';
  (cats.data||[]).forEach(c => sel.add(new Option(`${c.icon} ${c.name}`, c.id)));
  document.getElementById('bAmount').value = '';
  document.getElementById('budgetModal').classList.add('open');
});
document.getElementById('closeBudgetModal').addEventListener('click',  () => document.getElementById('budgetModal').classList.remove('open'));
document.getElementById('cancelBudgetModal').addEventListener('click', () => document.getElementById('budgetModal').classList.remove('open'));

document.getElementById('saveBudget').addEventListener('click', async () => {
  const payload = { category_id: document.getElementById('bCategory').value, amount: document.getElementById('bAmount').value, month: curMonth, year: curYear };
  if (!payload.amount) { toast('Podaj kwotę.','error'); return; }
  const res  = await fetch('api.php?resource=budgets', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  const json = await res.json();
  if (json.error) { toast(json.error,'error'); return; }
  toast('Budżet zapisany!','success');
  document.getElementById('budgetModal').classList.remove('open');
  load();
});

async function deleteBudget(id) {
  if (!confirm('Usunąć budżet?')) return;
  await fetch(`api.php?resource=budgets&id=${id}`,{method:'DELETE'});
  toast('Budżet usunięty.','success'); load();
}

load();
</script>

<?php require_once 'footer.php'; ?>
