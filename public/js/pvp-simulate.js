function simulatePvP(btn) {
    const csrfToken    = btn.dataset.csrf;
    const originalText = btn.value;
    const loadingText  = btn.dataset.loading;
    const resultEl     = document.getElementById('pvp-simulate-result');

    btn.disabled = true;
    btn.value    = loadingText;
    resultEl.hidden = true;

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);

    fetch('/api/pvp-simulate', { method: 'POST', body: formData })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success) {
                resultEl.className   = 'alert alert-danger';
                resultEl.textContent = data.error || btn.dataset.errorText;
                resultEl.hidden      = false;
                return;
            }

            var pct        = data.total > 0 ? Math.round((data.won / data.total) * 100) : 0;
            var alertClass = data.won >= data.lost ? 'alert-success' : 'alert-danger';
            var noteText   = data.mirror
                ? resultEl.dataset.mirrorNote
                : resultEl.dataset.opponentsNote
                    .replace('{count}', data.total)
                    .replace('{min}', data.floor_range.min)
                    .replace('{max}', data.floor_range.max);

            resultEl.className = 'alert ' + alertClass;
            resultEl.innerHTML =
                '<strong>' +
                resultEl.dataset.winsLabel   + ': ' + data.won  + ' | ' +
                resultEl.dataset.lossesLabel + ': ' + data.lost + ' | ' +
                resultEl.dataset.winRateLabel + ': ' + pct + '%' +
                '</strong><br><small>' + noteText + '</small>';
            resultEl.hidden = false;
        })
        .catch(function () {
            resultEl.className   = 'alert alert-danger';
            resultEl.textContent = btn.dataset.errorText;
            resultEl.hidden      = false;
        })
        .finally(function () {
            btn.disabled = false;
            btn.value    = originalText;
        });
}
