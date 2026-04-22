<?php require_once 'header.php'; ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:24px">
  <button class="btn btn-primary" id="addGoalBtn">+ Nowy cel</button>
</div>

<!-- Summary -->
<div class="stats-grid" style="margin-bottom:24px" id="goalStats">
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Aktywne cele</span><span>🎯</span></div>
    <div class="stat-value" id="gs-count" style="color:var(--accent)">—</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Łączny cel</span><span>🏆</span></div>
    <div class="stat-value" id="gs-target" style="color:var(--yellow)">—</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Łącznie zaoszczędzone</span><span>💰</span></div>
    <div class="stat-value amount-income" id="gs-saved">—</div>
  </div>
  <div class="stat-card">
    <div class="stat-card-header"><span class="stat-label">Ukończone</span><span>✅</span></div>
    <div class="stat-value" id="gs-done" style="color:var(--green)">—</div>
  </div>
</div>

<!-- Goals chart + grid -->
<div class="grid-2" style="margin-bottom:24px">
  <div class="card">
    <div class="card-header"><div class="card-title">📊 Postęp celów</div></div>
    <div class="chart-container" style="height:280px"><canvas id="goalsChart"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">🗓 Terminy celów</div></div>
    <div id="deadlineList"></div>
  </div>
</div>

<div class="grid-auto" id="goalCards">
  <div class="empty-state"><div class="empty-icon">⏳</div><p>Ładowanie…</p></div>
</div>

<!-- Modal Add/Edit Goal -->
<div class="modal-overlay" id="goalModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="goalModalTitle">Nowy cel oszczędnościowy</div>
      <button class="modal-close" id="closeGoalModal">✕</button>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Ikona (emoji)</label>
        <input type="text" class="form-control" id="gIcon" placeholder="🎯" maxlength="4" value="🎯">
      </div>
      <div class="form-group">
        <label class="form-label">Kolor</label>
        <input type="color" class="form-control" id="gColor" value="#10b981" style="height:42px;padding:4px">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Nazwa celu *</label>
      <input type="text" class="form-control" id="gName" placeholder="np. Wakacje 2025">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Kwota docelowa (PLN) *</label>
        <input type="number" class="form-control" id="gTarget" min="1" step="1" placeholder="5000">
      </div>
      <div class="form-group">
        <label class="form-label">Już zaoszczędzone (PLN)</label>
        <input type="number" class="form-control" id="gSaved" min="0" step="1" placeholder="0" value="0">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Termin (opcjonalnie)</label>
      <input type="date" class="form-control" id="gDeadline">
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="cancelGoalModal">Anuluj</button>
      <button class="btn btn-primary" id="saveGoal">💾 Zapisz</button>
    </div>
  </div>
</div>

<!-- Modal: wpłata -->
<div class="modal-overlay" id="depositModal">
  <div class="modal" style="max-width:360px">
    <div class="modal-header">
      <div class="modal-title">💸 Wpłać na cel</div>
      <button class="modal-close" id="closeDepositModal">✕</button>
    </div>
    <div class="form-group">
      <label class="form-label">Kwota wpłaty (PLN)</label>
      <input type="number" class="form-control" id="depositAmount" min="1" step="1" placeholder="100">
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="cancelDepositModal">Anuluj</button>
      <button class="btn btn-success" id="confirmDeposit">✅ Wpłać</button>
    </div>
  </div>
</div>

<script>
let editGoalId    = null;
let depositGoalId = null;
let depositCurrent = 0;
let goalsChart    = null;

async function loadGoals() {
  const data  = await apiFetch('savings');
  const goals = data.data || [];

  // Stats
  const total   = goals.length;
  const target  = goals.reduce((s,g)=>s + +g.target_amount, 0);
  const saved   = goals.reduce((s,g)=>s + +g.saved_amount, 0);
  const done    = goals.filter(g=>+g.saved_amount >= +g.target_amount).length;
  document.getElementById('gs-count').textContent  = total;
  document.getElementById('gs-target').textContent = fmtPLN(target);
  document.getElementById('gs-saved').textContent  = fmtPLN(saved);
  document.getElementById('gs-done').textContent   = done + ' / ' + total;

  // Chart
  if (goalsChart) goalsChart.destroy();
  if (goals.length) {
    goalsChart = new Chart(document.getElementById('goalsChart'), {
      type: 'bar',
      data: {
        labels: goals.map(g => g.icon+' '+g.name),
        datasets: [
          { label:'Cel',           data: goals.map(g=>+g.target_amount), backgroundColor:'rgba(99,102,241,.4)', borderRadius:6, borderSkipped:false },
          { label:'Zaoszczędzone', data: goals.map(g=>+g.saved_amount),  backgroundColor: goals.map(g=>g.color+'cc'), borderRadius:6, borderSkipped:false },
        ]
      },
      options: {
        indexAxis: 'y', responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{labels:{color:'#f0f0f5',font:{family:'Inter'}}}, tooltip:{callbacks:{label:ctx=>fmtPLN(ctx.raw)}} },
        scales:{
          x:{ ticks:{color:'#8888aa',callback:v=>fmtPLN(v)}, grid:{color:'rgba(255,255,255,.05)'} },
          y:{ ticks:{color:'#f0f0f5'}, grid:{display:false} }
        }
      }
    });
  }

  // Deadlines
  const deadEl = document.getElementById('deadlineList');
  const withDL = goals.filter(g=>g.deadline).sort((a,b)=>new Date(a.deadline)-new Date(b.deadline));
  if (!withDL.length) { deadEl.innerHTML='<div class="empty-state" style="padding:20px"><p>Brak celów z terminem</p></div>'; }
  else deadEl.innerHTML = withDL.map(g => {
    const days = Math.ceil((new Date(g.deadline)-new Date())/(1000*86400));
    const cls  = days<0?'var(--red)':days<30?'var(--yellow)':'var(--green)';
    return `<div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)">
      <span style="font-size:22px">${g.icon}</span>
      <div style="flex:1"><div style="font-weight:600">${g.name}</div><div style="font-size:11px;color:var(--text-muted)">${fmtDate(g.deadline)}</div></div>
      <span style="font-size:12px;font-weight:700;color:${cls}">${days<0?'Przekroczony!':days===0?'Dziś!':days+' dni'}</span>
    </div>`;
  }).join('');

  // Goal cards
  const el = document.getElementById('goalCards');
  if (!goals.length) { el.innerHTML='<div class="empty-state"><div class="empty-icon">💰</div><p>Brak celów. Kliknij "+ Nowy cel".</p></div>'; return; }
  el.innerHTML = goals.map(g => {
    const pct     = Math.min((+g.saved_amount / +g.target_amount)*100, 100).toFixed(1);
    const cls     = pct>=100?'progress-ok':pct>=50?'progress-warn':'progress-over';
    const done    = +g.saved_amount >= +g.target_amount;
    const left    = Math.max(0, +g.target_amount - +g.saved_amount);
    return `<div class="goal-card" style="border-top:3px solid ${g.color}">
      ${done?'<div style="background:rgba(16,185,129,.15);color:var(--green);font-size:11px;font-weight:700;padding:4px 10px;border-radius:6px;margin-bottom:8px;display:inline-block">✅ UKOŃCZONY</div>':''}
      <div class="goal-header">
        <span class="goal-icon">${g.icon}</span>
        <div>
          <div class="goal-name">${g.name}</div>
          ${g.deadline?`<div class="goal-deadline">📅 Termin: ${fmtDate(g.deadline)}</div>`:''}
        </div>
      </div>
      <div class="goal-amounts">
        <span class="goal-saved">${fmtPLN(g.saved_amount)}</span>
        <span class="goal-target">/ ${fmtPLN(g.target_amount)}</span>
      </div>
      <div class="progress"><div class="progress-bar ${cls}" style="width:${pct}%;background:${g.color}"></div></div>
      <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:12px;color:var(--text-muted)">
        <span>${pct}% ukończone</span>
        <span>Zostało: ${fmtPLN(left)}</span>
      </div>
      <div style="display:flex;gap:6px;margin-top:12px">
        <button class="btn btn-success btn-sm" onclick="openDeposit(${g.id},${g.saved_amount})" style="flex:1">💸 Wpłać</button>
        <button class="btn btn-ghost btn-sm btn-icon" onclick="editGoal(${g.id})">✏️</button>
        <button class="btn btn-ghost btn-sm btn-icon" onclick="deleteGoal(${g.id})">🗑️</button>
      </div>
    </div>`;
  }).join('');
}

// ── Add / Edit ──────────────────────────────────────────────
document.getElementById('addGoalBtn').addEventListener('click', () => {
  editGoalId = null;
  document.getElementById('goalModalTitle').textContent = 'Nowy cel oszczędnościowy';
  ['gIcon','gColor','gName','gTarget','gDeadline'].forEach(id => {
    document.getElementById(id).value = id==='gIcon'?'🎯':id==='gColor'?'#10b981':'';
  });
  document.getElementById('gSaved').value = '0';
  document.getElementById('goalModal').classList.add('open');
});

async function editGoal(id) {
  const res  = await fetch('api.php?resource=savings');
  const all  = await res.json();
  const g    = (all.data||[]).find(x=>x.id==id);
  if (!g) return;
  editGoalId = id;
  document.getElementById('goalModalTitle').textContent = 'Edytuj cel';
  document.getElementById('gIcon').value     = g.icon;
  document.getElementById('gColor').value    = g.color;
  document.getElementById('gName').value     = g.name;
  document.getElementById('gTarget').value   = g.target_amount;
  document.getElementById('gSaved').value    = g.saved_amount;
  document.getElementById('gDeadline').value = g.deadline || '';
  document.getElementById('goalModal').classList.add('open');
}

['closeGoalModal','cancelGoalModal'].forEach(id => document.getElementById(id).addEventListener('click', () => document.getElementById('goalModal').classList.remove('open')));

document.getElementById('saveGoal').addEventListener('click', async () => {
  const payload = {
    name:          document.getElementById('gName').value.trim(),
    target_amount: document.getElementById('gTarget').value,
    saved_amount:  document.getElementById('gSaved').value || 0,
    deadline:      document.getElementById('gDeadline').value || null,
    icon:          document.getElementById('gIcon').value.trim() || '🎯',
    color:         document.getElementById('gColor').value,
  };
  if (!payload.name || !payload.target_amount) { toast('Uzupełnij wymagane pola.','error'); return; }
  const url  = editGoalId ? `api.php?resource=savings&id=${editGoalId}` : 'api.php?resource=savings';
  const meth = editGoalId ? 'PUT' : 'POST';
  const res  = await fetch(url, {method:meth,headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  const json = await res.json();
  if (json.error) { toast(json.error,'error'); return; }
  toast('Cel zapisany!','success');
  document.getElementById('goalModal').classList.remove('open');
  loadGoals();
});

// ── Deposit ─────────────────────────────────────────────────
function openDeposit(id, current) {
  depositGoalId   = id;
  depositCurrent  = +current;
  document.getElementById('depositAmount').value = '';
  document.getElementById('depositModal').classList.add('open');
}

['closeDepositModal','cancelDepositModal'].forEach(id => document.getElementById(id).addEventListener('click', () => document.getElementById('depositModal').classList.remove('open')));

document.getElementById('confirmDeposit').addEventListener('click', async () => {
  const add = +document.getElementById('depositAmount').value;
  if (!add || add<=0) { toast('Podaj kwotę wpłaty.','error'); return; }
  // Fetch current goal
  const res = await fetch('api.php?resource=savings');
  const all = await res.json();
  const g   = (all.data||[]).find(x=>x.id==depositGoalId);
  if (!g) return;
  const newSaved = +g.saved_amount + add;
  const payload  = { name:g.name, target_amount:g.target_amount, saved_amount:newSaved, deadline:g.deadline||null, icon:g.icon, color:g.color };
  await fetch(`api.php?resource=savings&id=${depositGoalId}`,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  if (newSaved >= +g.target_amount) toast('🎉 Brawo! Cel osiągnięty!','success');
  else toast(`Wpłacono ${fmtPLN(add)}. Zaoszczędzone: ${fmtPLN(newSaved)}`,'success');
  document.getElementById('depositModal').classList.remove('open');
  loadGoals();
});

async function deleteGoal(id) {
  if (!confirm('Usunąć cel oszczędnościowy?')) return;
  await fetch(`api.php?resource=savings&id=${id}`,{method:'DELETE'});
  toast('Cel usunięty.','success'); loadGoals();
}

loadGoals();
</script>

<?php require_once 'footer.php'; ?>
