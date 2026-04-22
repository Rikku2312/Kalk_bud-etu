<?php require_once 'header.php'; ?>

<!-- Period selector -->
<div class="card" style="margin-bottom:24px">
  <div class="filters-bar" style="margin-bottom:0;flex-wrap:wrap">
    <div>
      <label class="form-label" style="margin-bottom:4px">Zakres dat</label>
      <div style="display:flex;gap:8px;align-items:center">
        <input type="date" class="form-control" id="rFrom" value="<?= date('Y-m-01') ?>">
        <span style="color:var(--text-muted)">→</span>
        <input type="date" class="form-control" id="rTo" value="<?= date('Y-m-d') ?>">
      </div>
    </div>
    <div>
      <label class="form-label" style="margin-bottom:4px">Szybki wybór</label>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <button class="btn btn-ghost btn-sm" onclick="setRange('thisMonth')">Ten miesiąc</button>
        <button class="btn btn-ghost btn-sm" onclick="setRange('lastMonth')">Poprzedni</button>
        <button class="btn btn-ghost btn-sm" onclick="setRange('thisYear')">Ten rok</button>
        <button class="btn btn-ghost btn-sm" onclick="setRange('last3')">Ostatnie 3 mies.</button>
        <button class="btn btn-ghost btn-sm" onclick="setRange('last6')">Ostatnie 6 mies.</button>
      </div>
    </div>
    <button class="btn btn-primary" id="runReport" style="align-self:flex-end">📊 Generuj raport</button>
  </div>
</div>

<!-- KPI -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card"><div class="stat-card-header"><span class="stat-label">Przychody</span><span>💚</span></div><div class="stat-value amount-income" id="r-income">—</div></div>
  <div class="stat-card"><div class="stat-card-header"><span class="stat-label">Wydatki</span><span>🔴</span></div><div class="stat-value amount-expense" id="r-expense">—</div></div>
  <div class="stat-card"><div class="stat-card-header"><span class="stat-label">Bilans</span><span>⚖️</span></div><div class="stat-value" id="r-balance">—</div></div>
  <div class="stat-card"><div class="stat-card-header"><span class="stat-label">Transakcje</span><span>🔢</span></div><div class="stat-value" id="r-count" style="color:var(--cyan)">—</div></div>
  <div class="stat-card"><div class="stat-card-header"><span class="stat-label">Śr. wydatek/dzień</span><span>📅</span></div><div class="stat-value" id="r-avg" style="color:var(--yellow)">—</div></div>
  <div class="stat-card"><div class="stat-card-header"><span class="stat-label">Największy wydatek</span><span>🔺</span></div><div class="stat-value amount-expense" id="r-max">—</div></div>
</div>

<!-- Charts row 1 -->
<div class="grid-2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-header"><div class="card-title">📈 Przychody vs Wydatki (trend)</div></div>
    <div class="chart-container" style="height:280px"><canvas id="rTrendChart"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">🍩 Struktura wydatków</div></div>
    <div class="chart-container" style="height:280px"><canvas id="rPieChart"></canvas></div>
  </div>
</div>

<!-- Charts row 2 -->
<div class="grid-2" style="margin-bottom:20px">
  <div class="card">
    <div class="card-header"><div class="card-title">📊 Wydatki według kategorii</div></div>
    <div class="chart-container" style="height:280px"><canvas id="rBarChart"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">📉 Bilans dzienny</div></div>
    <div class="chart-container" style="height:280px"><canvas id="rLineChart"></canvas></div>
  </div>
</div>

<!-- Top 5 expenses -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title">🏆 Największe wydatki w okresie</div></div>
  <div class="table-wrap">
    <table id="topExpTable">
      <thead><tr><th>#</th><th>Data</th><th>Opis</th><th>Kategoria</th><th style="text-align:right">Kwota</th></tr></thead>
      <tbody id="topExpBody"><tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted)">Wygeneruj raport…</td></tr></tbody>
    </table>
  </div>
</div>

<!-- Category breakdown -->
<div class="card">
  <div class="card-header">
    <div class="card-title">📋 Zestawienie kategorii</div>
    <button class="btn btn-ghost btn-sm" id="exportRptCSV">⬇ CSV</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Kategoria</th><th>Typ</th><th style="text-align:right">Suma</th><th style="text-align:right">Transakcji</th><th style="text-align:right">Śr. kwota</th><th style="text-align:right">% z wydatków</th></tr></thead>
      <tbody id="catBreakBody"><tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Wygeneruj raport…</td></tr></tbody>
    </table>
  </div>
</div>

<script>
let rTrend=null, rPie=null, rBar=null, rLine=null;
let lastReportData = null;

function setRange(preset) {
  const now   = new Date();
  const y     = now.getFullYear(), m = now.getMonth();
  const pad   = n => String(n).padStart(2,'0');
  const ymd   = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
  let from, to;
  if (preset==='thisMonth')  { from=new Date(y,m,1);   to=new Date(y,m+1,0); }
  if (preset==='lastMonth')  { from=new Date(y,m-1,1); to=new Date(y,m,0); }
  if (preset==='thisYear')   { from=new Date(y,0,1);   to=new Date(y,11,31); }
  if (preset==='last3')      { from=new Date(y,m-2,1); to=new Date(y,m+1,0); }
  if (preset==='last6')      { from=new Date(y,m-5,1); to=new Date(y,m+1,0); }
  document.getElementById('rFrom').value = ymd(from);
  document.getElementById('rTo').value   = ymd(to);
}

document.getElementById('runReport').addEventListener('click', runReport);

async function runReport() {
  const from = document.getElementById('rFrom').value;
  const to   = document.getElementById('rTo').value;
  if (!from || !to) { toast('Wybierz zakres dat.','error'); return; }

  const data = await apiFetch('transactions', { date_from:from, date_to:to, limit:9999 });
  const rows = data.data || [];
  lastReportData = rows;

  const income  = rows.filter(r=>r.type==='income').reduce((s,r)=>s+ +r.amount,0);
  const expense = rows.filter(r=>r.type==='expense').reduce((s,r)=>s+ +r.amount,0);
  const balance = income - expense;
  const count   = rows.length;

  // Days in range
  const days = Math.max(1, Math.ceil((new Date(to)-new Date(from))/(1000*86400))+1);
  const avgDay = expense/days;
  const maxExp = rows.filter(r=>r.type==='expense').reduce((mx,r)=>Math.max(mx,+r.amount),0);

  document.getElementById('r-income').textContent  = fmtPLN(income);
  document.getElementById('r-expense').textContent = fmtPLN(expense);
  const balEl = document.getElementById('r-balance');
  balEl.textContent = fmtPLN(balance);
  balEl.style.color = balance>=0?'var(--green)':'var(--red)';
  document.getElementById('r-count').textContent = count;
  document.getElementById('r-avg').textContent   = fmtPLN(avgDay);
  document.getElementById('r-max').textContent   = fmtPLN(maxExp);

  buildTrend(rows);
  buildPie(rows);
  buildBar(rows);
  buildLine(rows, from, to);
  buildTopExp(rows);
  buildCatBreak(rows, expense);
}

function buildTrend(rows) {
  const map = {};
  rows.forEach(r => {
    const k = r.date.substring(0,7);
    if (!map[k]) map[k]={income:0,expense:0};
    map[k][r.type] += +r.amount;
  });
  const labels=Object.keys(map).sort();
  if (rTrend) rTrend.destroy();
  rTrend = new Chart(document.getElementById('rTrendChart'),{
    type:'bar',
    data:{labels,datasets:[
      {label:'Przychody',data:labels.map(k=>map[k].income), backgroundColor:'rgba(16,185,129,.7)',borderRadius:6,borderSkipped:false},
      {label:'Wydatki',  data:labels.map(k=>map[k].expense),backgroundColor:'rgba(239,68,68,.7)', borderRadius:6,borderSkipped:false},
    ]},
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{labels:{color:'#f0f0f5',font:{family:'Inter'}}},tooltip:{callbacks:{label:ctx=>fmtPLN(ctx.raw)}}},
      scales:{x:{ticks:{color:'#8888aa'},grid:{color:'rgba(255,255,255,.05)'}},y:{ticks:{color:'#8888aa',callback:v=>fmtPLN(v)},grid:{color:'rgba(255,255,255,.05)'}}}}
  });
}

function buildPie(rows) {
  const catMap={};
  rows.filter(r=>r.type==='expense').forEach(r=>{
    const k=r.category_name||'Inne';
    if(!catMap[k]) catMap[k]={total:0,color:r.category_color||'#6366f1',icon:r.category_icon||'💰'};
    catMap[k].total += +r.amount;
  });
  const cats=Object.entries(catMap).sort((a,b)=>b[1].total-a[1].total);
  if (rPie) rPie.destroy();
  rPie = new Chart(document.getElementById('rPieChart'),{
    type:'doughnut',
    data:{labels:cats.map(([k,v])=>v.icon+' '+k),datasets:[{data:cats.map(([,v])=>v.total),backgroundColor:cats.map(([,v])=>v.color),borderColor:'#111118',borderWidth:3,hoverOffset:8}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'65%',
      plugins:{legend:{position:'right',labels:{color:'#f0f0f5',font:{family:'Inter',size:11},padding:10,boxWidth:12}},tooltip:{callbacks:{label:ctx=>`${ctx.label}: ${fmtPLN(ctx.raw)}`}}}}
  });
}

function buildBar(rows) {
  const catMap={};
  rows.filter(r=>r.type==='expense').forEach(r=>{
    const k=r.category_name||'Inne';
    if(!catMap[k]) catMap[k]={total:0,color:r.category_color||'#6366f1',icon:r.category_icon||'💰'};
    catMap[k].total += +r.amount;
  });
  const cats=Object.entries(catMap).sort((a,b)=>b[1].total-a[1].total).slice(0,10);
  if (rBar) rBar.destroy();
  rBar = new Chart(document.getElementById('rBarChart'),{
    type:'bar',
    data:{labels:cats.map(([k,v])=>v.icon+' '+k),datasets:[{label:'Wydatki',data:cats.map(([,v])=>v.total),backgroundColor:cats.map(([,v])=>v.color+'cc'),borderRadius:6,borderSkipped:false}]},
    options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>fmtPLN(ctx.raw)}}},
      scales:{x:{ticks:{color:'#8888aa',callback:v=>fmtPLN(v)},grid:{color:'rgba(255,255,255,.05)'}},y:{ticks:{color:'#f0f0f5'},grid:{display:false}}}}
  });
}

function buildLine(rows, from, to) {
  const dayMap={};
  const d = new Date(from);
  while (d<=new Date(to)) { dayMap[d.toISOString().split('T')[0]]={income:0,expense:0}; d.setDate(d.getDate()+1); }
  rows.forEach(r=>{ if(dayMap[r.date]) dayMap[r.date][r.type]+= +r.amount; });
  const labels=Object.keys(dayMap).sort();
  let cum=0;
  const balData=labels.map(k=>{ cum+=dayMap[k].income-dayMap[k].expense; return cum; });
  if (rLine) rLine.destroy();
  rLine = new Chart(document.getElementById('rLineChart'),{
    type:'line',
    data:{labels,datasets:[{label:'Bilans narastający',data:balData,borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,.1)',fill:true,tension:.3,pointRadius:2,borderWidth:2}]},
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{labels:{color:'#f0f0f5',font:{family:'Inter'}}},tooltip:{callbacks:{label:ctx=>fmtPLN(ctx.raw)}}},
      scales:{x:{ticks:{color:'#8888aa',maxTicksLimit:8},grid:{color:'rgba(255,255,255,.05)'}},y:{ticks:{color:'#8888aa',callback:v=>fmtPLN(v)},grid:{color:'rgba(255,255,255,.05)'}}}}
  });
}

function buildTopExp(rows) {
  const top=rows.filter(r=>r.type==='expense').sort((a,b)=>+b.amount - +a.amount).slice(0,5);
  document.getElementById('topExpBody').innerHTML = top.length
    ? top.map((r,i)=>`<tr><td><strong>#${i+1}</strong></td><td style="color:var(--text-muted);font-size:12px">${fmtDate(r.date)}</td><td>${r.description||'—'}</td><td>${r.category_icon||''}${r.category_name||'—'}</td><td style="text-align:right" class="amount-expense">−${fmtPLN(r.amount)}</td></tr>`).join('')
    : `<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted)">Brak danych</td></tr>`;
}

function buildCatBreak(rows, totalExp) {
  const catMap={};
  rows.forEach(r=>{
    const k=(r.category_name||'Bez kategorii')+'||'+r.type;
    if(!catMap[k]) catMap[k]={name:r.category_name||'Bez kategorii',icon:r.category_icon||'',type:r.type,total:0,count:0};
    catMap[k].total += +r.amount;
    catMap[k].count++;
  });
  const sorted=Object.values(catMap).sort((a,b)=>b.total-a.total);
  document.getElementById('catBreakBody').innerHTML = sorted.length
    ? sorted.map(c=>`<tr>
        <td><span class="cat-pill">${c.icon} ${c.name}</span></td>
        <td><span class="badge ${c.type==='income'?'badge-income':'badge-expense'}">${c.type==='income'?'Przychód':'Wydatek'}</span></td>
        <td style="text-align:right;font-weight:700" class="${c.type==='income'?'amount-income':'amount-expense'}">${fmtPLN(c.total)}</td>
        <td style="text-align:right;color:var(--text-muted)">${c.count}</td>
        <td style="text-align:right;color:var(--text-muted)">${fmtPLN(c.total/c.count)}</td>
        <td style="text-align:right;color:var(--text-muted)">${c.type==='expense'&&totalExp?((c.total/totalExp)*100).toFixed(1)+'%':'—'}</td>
      </tr>`).join('')
    : `<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted)">Brak danych</td></tr>`;
  lastReportData = sorted;
}

// Export CSV
document.getElementById('exportRptCSV').addEventListener('click', () => {
  if (!lastReportData) { toast('Najpierw wygeneruj raport.','info'); return; }
  const from = document.getElementById('rFrom').value;
  const to   = document.getElementById('rTo').value;
  const header = ['Kategoria','Typ','Suma','Liczba transakcji','Średnia kwota'];
  const lines  = lastReportData.map ? lastReportData.map(c=>[c.name,c.type,c.total,c.count,(c.total/c.count).toFixed(2)].join(';')) : [];
  const csv = '\uFEFF' + [header.join(';'),...lines].join('\n');
  const a   = document.createElement('a');
  a.href    = URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8'}));
  a.download = `raport_${from}_${to}.csv`;
  a.click(); toast('Eksport CSV gotowy!','success');
});

// Auto-run on load
runReport();
</script>

<?php require_once 'footer.php'; ?>
