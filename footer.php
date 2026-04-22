<?php // footer.php ?>
    </div><!-- /.page-content -->
  </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ── Helpers ──────────────────────────────────────────────────
const API = (resource, params = {}) => {
  const url = new URL('api.php', location.href);
  url.searchParams.set('resource', resource);
  Object.entries(params).forEach(([k,v]) => v !== undefined && url.searchParams.set(k, v));
  return url.toString();
};

async function apiFetch(resource, params = {}, options = {}) {
  const url = API(resource, params);
  const res = await fetch(url, options);
  return res.json();
}

function toast(msg, type = 'info') {
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<span>${type==='success'?'✅':type==='error'?'❌':'ℹ️'}</span><span>${msg}</span>`;
  document.getElementById('toastContainer').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

function fmtPLN(val) {
  return new Intl.NumberFormat('pl-PL',{style:'currency',currency:'PLN'}).format(val ?? 0);
}
function fmtDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('pl-PL',{day:'2-digit',month:'short',year:'numeric'});
}

// Live date in topbar
const topDate = document.getElementById('topbar-date');
if (topDate) {
  const update = () => {
    topDate.textContent = new Date().toLocaleDateString('pl-PL',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
  };
  update(); setInterval(update, 60000);
}

// Close sidebar on outside click (mobile)
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (window.innerWidth <= 768 && sb.classList.contains('open') && !sb.contains(e.target)) {
    sb.classList.remove('open');
  }
});
</script>
</body>
</html>
