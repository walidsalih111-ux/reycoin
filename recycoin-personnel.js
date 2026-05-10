(function() {
  'use strict';

  let curRes = null;
  let chosenReward = null;

  // ─── TABS ───
  window.switchTab = function(name, btn) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('page-' + name).classList.add('active');
    (btn || document.getElementById('tab-' + name)).classList.add('active');
    document.querySelector('.scroll').scrollTop = 0;
  };

  // ─── STEPS ───
  function setStep(n) {
    const dots = [document.getElementById('sd1'), document.getElementById('sd2'), document.getElementById('sd3')];
    const lines = [document.getElementById('sl1'), document.getElementById('sl2')];
    dots.forEach((d, i) => {
      d.className = 'step-dot' + (i + 1 < n ? ' done' : i + 1 === n ? ' active' : '');
    });
    lines.forEach((l, i) => {
      l.className = 'step-line' + (i + 1 < n ? ' done' : '');
    });
  }

  // ─── SCAN ───
  window.simScan = function(type) {
    const codes = { valid: 'RC-2026-00142', used: 'RC-2026-00139', invalid: 'RC-FAKE-99999' };
    document.getElementById('qr-input').value = codes[type];
    doScan(type);
  };

  window.doScan = function(forceType) {
    const val = document.getElementById('qr-input').value.trim().toUpperCase();
    const type = forceType || (val === 'RC-2026-00142' ? 'valid' : val === 'RC-2026-00139' ? 'used' : 'invalid');

    hideAllResults();
    setStep(2);
    document.getElementById('qr-preview').classList.add('lit');

    if (type === 'valid') {
      curRes = {
        name: 'You (09171234567)', mobile: '09171234567',
        av: 'YO', avBg: '#C8F0E3', avCol: '#042c1e',
        qr: 'RC-2026-00142', gen: 'May 3 · 8:00 AM', bal: '0 pts after'
      };
      document.getElementById('r-av').textContent = curRes.av;
      document.getElementById('r-av').style.background = curRes.avBg;
      document.getElementById('r-av').style.color = curRes.avCol;
      document.getElementById('r-name').textContent = curRes.name;
      document.getElementById('r-mobile').textContent = curRes.mobile;
      document.getElementById('r-qr').textContent = curRes.qr;
      document.getElementById('r-gen').textContent = curRes.gen;
      document.getElementById('r-bal').textContent = curRes.bal;
      chosenReward = null;
      document.querySelectorAll('.reward-opt').forEach(o => o.classList.remove('picked'));
      document.getElementById('btn-dist').disabled = true;
      show('res-valid');
    } else if (type === 'used') {
      show('res-used');
    } else {
      show('res-invalid');
    }
  };

  window.pickReward = function(el, name) {
    document.querySelectorAll('.reward-opt').forEach(o => o.classList.remove('picked'));
    el.classList.add('picked');
    chosenReward = name;
    document.getElementById('btn-dist').disabled = false;
  };

  window.doDistribute = function() {
    if (!curRes || !chosenReward) return;
    const txid = 'TXN-' + Math.floor(Math.random() * 90000 + 10000);
    const now = new Date();
    const ts = 'May 3, 2026 · ' + now.getHours() + ':' + String(now.getMinutes()).padStart(2, '0');

    document.getElementById('d-av').textContent = curRes.av;
    document.getElementById('d-av').style.background = curRes.avBg;
    document.getElementById('d-av').style.color = curRes.avCol;
    document.getElementById('d-name').textContent = curRes.name;
    document.getElementById('d-reward').textContent = chosenReward + ' distributed';
    document.getElementById('d-txid').textContent = txid;
    document.getElementById('d-time').textContent = ts;

    hideAllResults();
    show('res-done');
    setStep(3);

    // update log counts
    const tc = document.getElementById('today-cnt');
    tc.textContent = parseInt(tc.textContent) + 1;
    const mc = document.getElementById('month-cnt');
    mc.textContent = parseInt(mc.textContent) + 1;

    // prepend log entry
    const ll = document.getElementById('log-list');
    const row = document.createElement('div');
    row.className = 'log-row';
    row.innerHTML = `
      <div class="log-av" style="background:${curRes.avBg};color:${curRes.avCol}">${curRes.av}</div>
      <div class="log-info"><div class="log-name">${curRes.name}</div><div class="log-detail">${curRes.qr} · ${chosenReward}</div></div>
      <div class="log-right"><div class="log-status" style="color:var(--g700)">Distributed</div><div class="log-time">Just now</div></div>
    `;
    ll.prepend(row);
    showToast('✅ Transaction archived. Reward given to resident.');
  };

  window.resetScan = function() {
    hideAllResults();
    document.getElementById('qr-input').value = '';
    document.getElementById('qr-preview').classList.remove('lit');
    curRes = null; chosenReward = null;
    setStep(1);
    document.querySelector('.scroll').scrollTop = 0;
  };

  // ─── RESIDENTS SEARCH ───
  window.filterResidents = function(q) {
    const term = q.toLowerCase();
    document.querySelectorAll('#res-list .log-row').forEach(r => {
      r.style.display = r.dataset.search.includes(term) ? '' : 'none';
    });
  };

  // ─── TOAST ───
  window.showToast = function(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
  };

  // ─── HELPERS ───
  function show(id) { document.getElementById(id).classList.add('show'); }
  function hideAllResults() {
    ['res-valid','res-used','res-invalid','res-done'].forEach(id => {
      document.getElementById(id).classList.remove('show');
    });
  }
})();
