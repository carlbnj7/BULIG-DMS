// Make-an-account form: show only the place list that fits the chosen account type.
(function () {
  var f = document.querySelector('[data-userform]'); if (!f) return;
  var r = f.querySelector('[data-role]');
  function sync() { f.querySelectorAll('[data-for]').forEach(function (l) { var on = l.getAttribute('data-for').split(' ').indexOf(r.value) >= 0; l.hidden = !on; l.querySelector('select').disabled = !on; }); }
  r.addEventListener('change', sync); sync();
})();
