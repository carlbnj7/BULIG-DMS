// Read the SF1 (School Form 1) in the browser, then send only the needed columns to the server for the preview.
(function () {
  var f = document.querySelector('[data-sf1]'); if (!f) return;
  var file = f.querySelector('input[type=file]'), out = f.querySelector('[name=rows]'), msg = f.querySelector('[data-sf1-msg]'), btn = f.querySelector('button[type=submit]');
  function cell(r, i) { var v = r[i]; return v === undefined || v === null ? '' : String(v).trim(); }
  function after(rows, label) {
    for (var y = 0; y < rows.length; y++) for (var x = 0; x < rows[y].length; x++) {
      if (cell(rows[y], x).replace(/\s+/g, ' ').toLowerCase().indexOf(label) === 0) {
        for (var k = x + 1; k < rows[y].length; k++) if (cell(rows[y], k) !== '') return cell(rows[y], k);
      }
    }
    return '';
  }
  file.addEventListener('change', function () {
    btn.disabled = true; msg.textContent = 'Reading the file…';
    var fr = new FileReader();
    fr.onload = function () {
      try {
        var wb = XLSX.read(new Uint8Array(fr.result), { type: 'array', cellDates: false });
        var ws = wb.Sheets[wb.SheetNames[0]];
        var rows = XLSX.utils.sheet_to_json(ws, { header: 1, raw: false, defval: '' });
        var head = { school_id: after(rows, 'school id'), school_name: after(rows, 'school name'), school_year: after(rows, 'school year'), grade: after(rows, 'grade level'), section: after(rows, 'section') };
        var list = [], sex = '';
        rows.forEach(function (r) {
          var joined = r.join(' ').toUpperCase();
          if (/TOTAL\s+MALE|<=+\s*TOTAL MALE/.test(joined)) sex = 'F';
          var lrn = cell(r, 0).replace(/\D/g, '');
          if (lrn.length !== 12) return;
          var name = '', s = '', bd = '';
          for (var i = 1; i < r.length; i++) {
            var v = cell(r, i);
            if (!name && v.indexOf(',') > 0) name = v;
            else if (!s && /^(M|F|MALE|FEMALE)$/i.test(v)) s = v.charAt(0).toUpperCase();
            else if (!bd && /^\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}$/.test(v)) bd = v;
          }
          if (name) list.push({ lrn: lrn, name: name, sex: s || sex || 'M', bd: bd });
        });
        if (!list.length) { msg.textContent = 'No learners found. Is this the SF1 (School Register) from LIS?'; return; }
        out.value = JSON.stringify({ head: head, rows: list });
        msg.textContent = list.length + ' learners found · ' + (head.grade || '') + ' · ' + (head.section || '') + '. Press Next to check them.';
        btn.disabled = false;
      } catch (e) { msg.textContent = 'This file could not be read. Save it again from LIS and try once more.'; }
    };
    fr.readAsArrayBuffer(file.files[0]);
  });
})();
