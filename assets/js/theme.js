(function () {
  var k = 'buligdms-theme', t = null;
  try { t = localStorage.getItem(k); } catch (e) {}
  if (!t) t = (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', t);
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-theme-toggle]'); if (!b) return;
    var n = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', n);
    try { localStorage.setItem(k, n); } catch (e) {}
  });
  document.addEventListener('submit', function (ev) {
    var m = ev.target.getAttribute('data-confirm');
    if (m && !confirm(m)) ev.preventDefault();
  });
  // PIN boxes: numbers only, 4 at most.
  document.addEventListener('input', function (ev) {
    var i = ev.target; if (!i.matches || !i.matches('[data-pin]')) return;
    var v = i.value.replace(/\D/g, '').slice(0, 4); if (v !== i.value) i.value = v;
  });
  document.addEventListener('click', function (ev) {
    var p = ev.target.closest('[data-print]'); if (p) { ev.preventDefault(); window.print(); }
  });
})();
// Duplicates: the record not checked is the one merged away.
document.addEventListener('change', function (e) {
  var r = e.target; if (!r.matches || !r.matches('.dup-card input[name=keep]')) return;
  var d = r.form.querySelector('[data-drop]'), ids = d.getAttribute('data-ids').split(',');
  d.value = ids.filter(function (x) { return x !== r.value; })[0];
});
// Sidebar search: show only menu items that match.
document.addEventListener('input', function (e) {
  if (!e.target.matches || !e.target.matches('[data-navsearch]')) return;
  var q = e.target.value.trim().toLowerCase();
  document.querySelectorAll('[data-navitem]').forEach(function (a) { a.hidden = q !== '' && a.textContent.toLowerCase().indexOf(q) < 0; });
  document.querySelectorAll('.nav-h').forEach(function (h) { h.hidden = q !== ''; });
});
