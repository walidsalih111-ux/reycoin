// encapsulate app logic to avoid polluting global scope
(function (window, document) {
  'use strict';

  let currentPts = 78;
  let currentBottles = 54;
  let selectedBottle = null;

  function showPage(name) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    const page = document.getElementById('page-' + name);
    if (page) page.classList.add('active');
    try {
      if (typeof event !== 'undefined' && event && event.target) {
        event.target.classList.add('active');
      }
    } catch (e) { /* ignore */ }
  }

  function selectBottle(el) {
    document.querySelectorAll('.bottle-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selectedBottle = { size: el.dataset.size, pts: parseFloat(el.dataset.pts) };
    const newTotal = currentPts + selectedBottle.pts;
    const sumSize = document.getElementById('sum-size');
    const sumPts = document.getElementById('sum-pts');
    const sumTotal = document.getElementById('sum-total');
    if (sumSize) sumSize.textContent = selectedBottle.size;
    if (sumPts) sumPts.textContent = '+' + selectedBottle.pts + ' pts';
    if (sumTotal) sumTotal.textContent = newTotal + ' pts';
    const depositSummary = document.getElementById('deposit-summary');
    if (depositSummary) depositSummary.style.display = 'block';
    const depositBtn = document.getElementById('deposit-btn');
    if (depositBtn) depositBtn.disabled = false;
  }

  function doDeposit() {
    if (!selectedBottle) return;
    currentPts = Math.round((currentPts + selectedBottle.pts) * 10) / 10;
    currentBottles += 1;

    const ptsDisplay = document.getElementById('pts-display');
    const bottlesDisplay = document.getElementById('bottles-display');
    const mainProgress = document.getElementById('main-progress');
    if (ptsDisplay) ptsDisplay.textContent = currentPts;
    if (bottlesDisplay) bottlesDisplay.textContent = currentBottles;
    if (mainProgress) mainProgress.style.width = Math.min(currentPts, 100) + '%';

    const qrPtsBig = document.getElementById('qr-pts-big');
    if (qrPtsBig) qrPtsBig.textContent = currentPts + ' pts';

    const depositSummary = document.getElementById('deposit-summary');
    if (depositSummary) depositSummary.style.display = 'none';
    const depositBtn = document.getElementById('deposit-btn');
    if (depositBtn) depositBtn.disabled = true;
    document.querySelectorAll('.bottle-card').forEach(c => c.classList.remove('selected'));
    selectedBottle = null;

    const alert = document.getElementById('deposit-alert');
    if (alert) {
      alert.style.display = 'flex';
      setTimeout(() => { alert.style.display = 'none'; }, 3000);
    }

    const toast = document.getElementById('toast');
    if (toast) {
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2500);
    }

    if (currentPts >= 100) {
      const qrLocked = document.querySelector('.qr-locked');
      if (qrLocked) {
        qrLocked.style.filter = 'none';
        qrLocked.style.opacity = '1';
      }
      const lockedMsg = document.getElementById('qr-locked-msg');
      if (lockedMsg) lockedMsg.innerHTML = '<div style="font-size:13px;color:#0F6E56;font-weight:500;margin-bottom:6px">QR code unlocked!</div><div style="font-size:11px;color:var(--color-text-secondary)">Show this to barangay personnel</div>';
    }
  }

  // Expose for inline handlers and debugging
  window.showPage = showPage;
  window.selectBottle = selectBottle;
  window.doDeposit = doDeposit;
  window.recycoinApp = {
    get currentPts() { return currentPts; },
    get currentBottles() { return currentBottles; }
  };

})(window, document);
