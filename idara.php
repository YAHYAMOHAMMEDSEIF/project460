<?php
require 'config.php';
$u = auth(['hr']);

$ci = (int)($_POST['i'] ?? $_GET['i'] ?? 0);    // idara iliyochaguliwa
$ck = (int)($_POST['k'] ?? $_GET['k'] ?? 0);    // kitengo maalum kilichochaguliwa

function back($m, $t = 'good', $i = null, $k = null){
  global $ci, $ck;
  $_SESSION['flash'] = [$t, $m];
  header('Location: idara.php?i=' . ($i === null ? $ci : (int)$i) . '&k=' . ($k === null ? $ck : (int)$k)); exit;
}
function num($pdo, $sql, $p){ $s = $pdo->prepare($sql); $s->execute($p); return (int)$s->fetchColumn(); }
function ctx($i, $k){ return '<input type="hidden" name="i" value="' . (int)$i . '"><input type="hidden" name="k" value="' . (int)$k . '">'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $do = $_POST['do'] ?? ''; $id = (int)($_POST['id'] ?? 0);
  $jina = trim($_POST['jina'] ?? ''); $aina = ($_POST['aina'] ?? '') === 'kitengo' ? 'kitengo' : 'idara';
  if (in_array($do, ['add_dept','edit_dept','add_div','edit_div']) && ($jina === '' || mb_strlen($jina) > 120))
    back('Jina ni lazima (hadi herufi 120).', 'bad');

  switch ($do) {
    case 'add_dept':
      if (num($pdo, 'SELECT COUNT(*) FROM departments WHERE jina=?', [$jina])) back('Jina hilo tayari lipo.', 'bad');
      $pdo->prepare('INSERT INTO departments(jina,aina) VALUES(?,?)')->execute([$jina, $aina]);
      $nid = (int)$pdo->lastInsertId();
      $orodha = $aina === 'idara' ? 'Idara' : 'Vitengo Maalum';
      back("\"$jina\" imeongezwa kwenye orodha ya $orodha na imechaguliwa.", 'good', $aina === 'idara' ? $nid : null, $aina === 'kitengo' ? $nid : null);

    case 'edit_dept':
      if (num($pdo, 'SELECT COUNT(*) FROM departments WHERE jina=? AND id<>?', [$jina, $id])) back('Jina hilo tayari lipo.', 'bad');
      $pdo->prepare('UPDATE departments SET jina=? WHERE id=?')->execute([$jina, $id]);
      back('Jina limehifadhiwa.');

    case 'del_dept':
      if (num($pdo, 'SELECT COUNT(*) FROM divisions WHERE department_id=?', [$id])
       || num($pdo, 'SELECT COUNT(*) FROM users WHERE department_id=?', [$id])
       || num($pdo, 'SELECT COUNT(*) FROM requests WHERE department_id=?', [$id]))
        back('Haiwezi kufutwa: ina divisheni, watumiaji au maombi.', 'bad');
      $pdo->prepare('DELETE FROM departments WHERE id=?')->execute([$id]);
      back('Imefutwa.', 'good', $id == $ci ? 0 : null, $id == $ck ? 0 : null);

    case 'add_div':
      if (!num($pdo, "SELECT COUNT(*) FROM departments WHERE id=? AND aina='idara'", [$id])) back('Idara si sahihi.', 'bad');
      if (num($pdo, 'SELECT COUNT(*) FROM divisions WHERE department_id=? AND jina=?', [$id, $jina])) back('Divisheni hiyo tayari ipo ndani ya idara hii.', 'bad');
      $pdo->prepare('INSERT INTO divisions(department_id,jina) VALUES(?,?)')->execute([$id, $jina]);
      back("Divisheni \"$jina\" imeongezwa.", 'good', $id);

    case 'edit_div':
      $dep = num($pdo, 'SELECT department_id FROM divisions WHERE id=?', [$id]);
      if (num($pdo, 'SELECT COUNT(*) FROM divisions WHERE department_id=? AND jina=? AND id<>?', [$dep, $jina, $id])) back('Divisheni hiyo tayari ipo ndani ya idara hii.', 'bad');
      $pdo->prepare('UPDATE divisions SET jina=? WHERE id=?')->execute([$jina, $id]);
      back('Jina la divisheni limehifadhiwa.');

    case 'del_div':
      if (num($pdo, 'SELECT COUNT(*) FROM users WHERE division_id=?', [$id])
       || num($pdo, 'SELECT COUNT(*) FROM requests WHERE division_id=?', [$id]))
        back('Haiwezi kufutwa: ina watumiaji au maombi.', 'bad');
      $pdo->prepare('DELETE FROM divisions WHERE id=?')->execute([$id]);
      back('Divisheni imefutwa.');
  }
  back('Kitendo hakikueleweka.', 'bad');
}

$f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$all = $pdo->query('SELECT * FROM departments ORDER BY id')->fetchAll();
$idaras = array_values(array_filter($all, function($d){ return $d['aina'] === 'idara'; }));
$vitengo = array_values(array_filter($all, function($d){ return $d['aina'] === 'kitengo'; }));
$divs = []; foreach ($pdo->query('SELECT * FROM divisions ORDER BY jina') as $d) $divs[$d['department_id']][] = $d;
$uD = $uV = $rD = $rV = [];
foreach ($pdo->query('SELECT department_id d, division_id v, COUNT(*) c FROM users GROUP BY department_id, division_id') as $x) {
  if ($x['d']) $uD[$x['d']] = ($uD[$x['d']] ?? 0) + $x['c'];
  if ($x['v']) $uV[$x['v']] = $x['c'];
}
foreach ($pdo->query('SELECT department_id d, COUNT(*) c FROM requests GROUP BY department_id') as $x) if ($x['d']) $rD[$x['d']] = $x['c'];
foreach ($pdo->query('SELECT division_id v, COUNT(*) c FROM requests GROUP BY division_id') as $x) if ($x['v']) $rV[$x['v']] = $x['c'];

$curI = null; foreach ($idaras as $d) if ($d['id'] == $ci) $curI = $d;
$curK = null; foreach ($vitengo as $d) if ($d['id'] == $ck) $curK = $d;
$ci = $curI ? $curI['id'] : 0; $ck = $curK ? $curK['id'] : 0;

$pageTitle = 'Idara na Divisheni'; $pageSub = 'Chagua idara au kitengo, kisha ubadilishe au uongeze divisheni'; $active = 'org';
include 'header.php';
$conf = "onsubmit=\"return confirm('Una uhakika unataka kufuta?')\"";
?>
<?php if ($f): ?><div class="alert <?= e($f[0]) ?>"><?= e($f[1]) ?></div><?php endif; ?>

<!-- ============ IDARA NA DIVISHENI ============ -->
<div class="card">
  <div class="card-h"><h2>Idara na Divisheni</h2>
  </div>

  <label>Chagua idara</label>
  <select id="si" onchange="go()">
    <option value="0">— chagua idara —</option>
    <?php foreach ($idaras as $d) echo '<option value="'.$d['id'].'"'.($d['id']==$ci?' selected':'').'>'.e($d['jina']).' ('.count($divs[$d['id']] ?? []).' divisheni)</option>'; ?>
  </select>
  <form method="post" class="inl addbox"><?= ctx($ci, $ck) ?>
    <input type="hidden" name="do" value="add_dept"><input type="hidden" name="aina" value="idara">
    <input name="jina" placeholder="Andika jina la idara mpya (itaingia kwenye orodha ya Idara)" required maxlength="120">
    <button class="btn sm">+ Ongeza idara</button>
  </form>

  <?php if ($curI): $id = $curI['id']; $dv = $divs[$id] ?? [];
        $used = !empty($uD[$id]) || !empty($rD[$id]) || $dv; ?>
    <div class="panel">
      <h3>Jina la idara</h3>
      <form method="post" class="inl"><?= ctx($ci, $ck) ?>
        <input type="hidden" name="do" value="edit_dept"><input type="hidden" name="id" value="<?= $id ?>">
        <input name="jina" value="<?= e($curI['jina']) ?>" required maxlength="120">
        <button class="btn sm">Hifadhi jina</button>
      </form>
      <?php if (!$used): ?>
      <form method="post" class="inl" style="margin-top:8px" <?= $conf ?>><?= ctx($ci, $ck) ?>
        <input type="hidden" name="do" value="del_dept"><input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn sm dg">Futa idara hii</button>
      </form>
      <?php endif; ?>

      <h3>Divisheni za <?= e($curI['jina']) ?> <small>(<?= (int)($uD[$id] ?? 0) ?> watumiaji)</small></h3>
      <?php foreach ($dv as $v): $vid = $v['id']; $vused = !empty($uV[$vid]) || !empty($rV[$vid]); ?>
        <div class="divrow">
          <form method="post" class="inl"><?= ctx($ci, $ck) ?>
            <input type="hidden" name="do" value="edit_div"><input type="hidden" name="id" value="<?= $vid ?>">
            <input name="jina" value="<?= e($v['jina']) ?>" required maxlength="120">
            <small><?= (int)($uV[$vid] ?? 0) ?> watumiaji</small>
            <button class="btn sm g">Hifadhi</button>
          </form>
          <?php if (!$vused): ?>
          <form method="post" <?= $conf ?>><?= ctx($ci, $ck) ?>
            <input type="hidden" name="do" value="del_div"><input type="hidden" name="id" value="<?= $vid ?>">
            <button class="btn sm dg">Futa</button>
          </form>
          <?php endif; ?>
        </div>
      <?php endforeach; if (!$dv) echo '<p class="empty" style="padding:8px 0">Hakuna divisheni bado. Iandike hapa chini.</p>'; ?>

      <label>Andika divisheni mpya</label>
      <form method="post" class="inl"><?= ctx($ci, $ck) ?>
        <input type="hidden" name="do" value="add_div"><input type="hidden" name="id" value="<?= $id ?>">
        <input name="jina" placeholder="Mfano: Divisheni ya Takwimu za Kilimo" required maxlength="120">
        <button class="btn sm">+ Ongeza divisheni</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<!-- ============ VITENGO MAALUM ============ -->
<div class="card">
  <div class="card-h"><h2>Vitengo Maalum</h2>
  </div>
  <p class="hint" style="text-align:left;margin:0 0 6px">Wafanyakazi wa vitengo maalum huomba ruhusa moja kwa moja kwa Mtakwimu Mkuu.</p>

  <label>Chagua kitengo maalum</label>
  <select id="sk" onchange="go()">
    <option value="0">— chagua kitengo —</option>
    <?php foreach ($vitengo as $d) echo '<option value="'.$d['id'].'"'.($d['id']==$ck?' selected':'').'>'.e($d['jina']).'</option>'; ?>
  </select>
  <form method="post" class="inl addbox"><?= ctx($ci, $ck) ?>
    <input type="hidden" name="do" value="add_dept"><input type="hidden" name="aina" value="kitengo">
    <input name="jina" placeholder="Andika jina la kitengo kipya (kitaingia kwenye orodha ya Vitengo Maalum)" required maxlength="120">
    <button class="btn sm">+ Ongeza kitengo</button>
  </form>

  <?php if ($curK): $id = $curK['id']; $used = !empty($uD[$id]) || !empty($rD[$id]); ?>
    <div class="panel">
      <h3>Jina la kitengo <small>(<?= (int)($uD[$id] ?? 0) ?> watumiaji)</small></h3>
      <form method="post" class="inl"><?= ctx($ci, $ck) ?>
        <input type="hidden" name="do" value="edit_dept"><input type="hidden" name="id" value="<?= $id ?>">
        <input name="jina" value="<?= e($curK['jina']) ?>" required maxlength="120">
        <button class="btn sm">Hifadhi jina</button>
      </form>
      <?php if (!$used): ?>
      <form method="post" class="inl" style="margin-top:8px" <?= $conf ?>><?= ctx($ci, $ck) ?>
        <input type="hidden" name="do" value="del_dept"><input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn sm dg">Futa kitengo hiki</button>
      </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<script>
function go(){
  location = 'idara.php?i=' + document.getElementById('si').value + '&k=' + document.getElementById('sk').value;
}
</script>
<?php include 'footer.php'; ?>
