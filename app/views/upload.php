<div class="page-head"><div><h1>Upload your SF1</h1><p class="muted">Use the School Form 1 (School Register) downloaded from LIS. The file is read on this device; only names, LRN, sex and birthdate are saved.</p></div></div>
<form method="post" action="<?= url('upload') ?>" class="card upload-card" data-sf1><?= csrf_field() ?><input type="hidden" name="step" value="read"><input type="hidden" name="rows">
  <div class="steps"><span class="on"><b>1</b> Choose SF1</span><span><b>2</b> Check list and set BULIG levels</span><span><b>3</b> Save</span></div>
  <label class="drop"><?= icon('upload', 34) ?><b>Choose the SF1 file</b><small>.xls or .xlsx · <?= e($school['name']) ?></small><input type="file" accept=".xls,.xlsx" required></label>
  <p class="muted small center" data-sf1-msg></p>
  <button type="submit" class="btn primary big" disabled>Next: check the list <?= icon('arrow', 18) ?></button>
</form>
