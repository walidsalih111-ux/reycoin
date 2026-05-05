(function() {
	'use strict';

	let currentPts = 78;
	let currentBottles = 54;
	let selectedBottle = null;

	window.showPage = function(name, tabEl) {
		document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
		document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
		const pg = document.getElementById('page-' + name);
		if (pg) pg.classList.add('active');
		const tb = tabEl || document.getElementById('tab-' + name);
		if (tb) tb.classList.add('active');
		// scroll to top
		document.querySelector('.scroll-area').scrollTop = 0;
	};

	window.selectBottle = function(el) {
		document.querySelectorAll('.bottle-card').forEach(c => c.classList.remove('selected'));
		el.classList.add('selected');
		selectedBottle = { size: el.dataset.size, pts: parseFloat(el.dataset.pts) };
		const newTotal = Math.round((currentPts + selectedBottle.pts) * 10) / 10;
		document.getElementById('sum-size').textContent = selectedBottle.size;
		document.getElementById('sum-pts').textContent = '+' + selectedBottle.pts + ' pts';
		document.getElementById('sum-total').textContent = newTotal + ' pts';
		document.getElementById('deposit-summary').style.display = 'block';
		document.getElementById('deposit-btn').disabled = false;
	};

	window.doDeposit = function() {
		if (!selectedBottle) return;
		currentPts = Math.round((currentPts + selectedBottle.pts) * 10) / 10;
		currentBottles += 1;

		// update dashboard
		document.getElementById('pts-display').textContent = currentPts;
		document.getElementById('bottles-display').textContent = currentBottles;
		const pct = Math.min(currentPts, 100);
		document.getElementById('main-progress').style.width = pct + '%';
		document.getElementById('prog-badge').textContent = currentPts + ' / 100';
		document.getElementById('prog-label').textContent = currentPts + ' pts';

		// update leaderboard row
		document.getElementById('lb-my-pts').textContent = currentPts + ' pts';
		document.getElementById('lb-my-bottles').textContent = currentBottles + ' bottles';

		// update redeem
		document.getElementById('qr-pts-big').textContent = currentPts;
		document.getElementById('qr-prog-bar').style.width = pct + '%';
		document.getElementById('qr-prog-label').textContent = currentPts + ' / 100 pts';

		// reset deposit
		document.getElementById('deposit-summary').style.display = 'none';
		document.getElementById('deposit-btn').disabled = true;
		document.querySelectorAll('.bottle-card').forEach(c => c.classList.remove('selected'));
		selectedBottle = null;

		// alert
		const alert = document.getElementById('deposit-alert');
		alert.style.display = 'flex';
		setTimeout(() => { alert.style.display = 'none'; }, 3000);

		// toast
		const toast = document.getElementById('toast');
		toast.classList.add('show');
		setTimeout(() => toast.classList.remove('show'), 2500);

		// unlock QR
		if (currentPts >= 100) {
			const qrImg = document.getElementById('qr-img');
			qrImg.className = 'qr-unlocked';
			const msg = document.getElementById('qr-locked-msg');
			msg.innerHTML = '<div class="qr-unlocked-label">QR Code Unlocked!</div><div class="qr-pts-label">Show to barangay personnel</div>';
			document.getElementById('qr-progress-wrap').style.display = 'none';
		}
	};
})();

