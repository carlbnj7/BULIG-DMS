// Monitoring screen: every change saves right away. Also the "set all" helpers on the SF1 preview.
(function () {
  document.querySelectorAll('[data-fill]').forEach(function (s) {
    s.addEventListener('change', function () { if (!s.value) return; document.querySelectorAll('[data-col="' + s.getAttribute('data-fill') + '"]').forEach(function (t) { t.value = s.value; }); });
  });
  var box = document.querySelector('[data-monitor]'); if (!box) return;
  var cls = box.getAttribute('data-class'), ap = box.getAttribute('data-ap'), P = JSON.parse(box.getAttribute('data-profiles')), state = box.querySelector('[data-save-state]'), csrf = document.body.getAttribute('data-csrf');
  function send(learner, field, value, done) {
    state.textContent = 'Saving…'; state.className = 'save-state';
    var fd = new FormData(); fd.append('class', cls); fd.append('learner', learner || 0); fd.append('field', field); fd.append('value', value); fd.append('ap', ap);
    fetch('index.php?p=api', { method: 'POST', body: fd, headers: { 'X-CSRF': csrf }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j.error) throw new Error(j.error); state.textContent = 'Saved'; state.className = 'save-state ok'; if (done) done(j); })
      .catch(function (e) { state.textContent = e.message || 'Not saved. Check your internet.'; state.className = 'save-state bad'; });
  }
  function show(tr, j) { tr.querySelector('[data-level]').textContent = j.level || '—'; tr.querySelector('[data-area]').textContent = j.area || ''; tr.querySelector('[data-before]').textContent = j.before || '—'; }
  box.addEventListener('change', function (e) {
    var s = e.target;
    if (s.matches('[data-term]')) { send(0, 'term', s.value); return; }
    var tr = s.closest('tr[data-learner]'); if (!tr || !s.matches('[data-f]')) return;
    var f = s.getAttribute('data-f');
    if (f === 'key_stage') {
      tr.querySelectorAll('[data-f=bosy],[data-f=mosy],[data-f=eosy]').forEach(function (p) {
        p.innerHTML = '<option value="">—</option>'; (P[s.value] || []).forEach(function (o) { var op = document.createElement('option'); op.textContent = o; p.appendChild(op); });
      });
    }
    send(tr.getAttribute('data-learner'), f, s.value, function (j) { show(tr, j); });
  });
  box.addEventListener('click', function (e) {
    var b = e.target.closest('[data-remark]'); if (!b || b.disabled) return;
    var tr = b.closest('tr[data-learner]'), on = b.classList.contains('on'), v = on ? '' : b.getAttribute('data-remark');
    tr.querySelectorAll('[data-remark]').forEach(function (x) { x.classList.toggle('on', x === b && !on); });
    send(tr.getAttribute('data-learner'), 'remark', v, function (j) { show(tr, j); });
  });
})();
