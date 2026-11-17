document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-peek]'); if (!b) return;
  var i = document.getElementById(b.getAttribute('data-peek'));
  var show = i.type === 'password'; i.type = show ? 'text' : 'password'; b.textContent = show ? 'Hide' : 'Show';
});
