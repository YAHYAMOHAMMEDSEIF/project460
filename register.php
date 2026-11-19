<?php
require 'config.php';
if (!empty($_SESSION['uid'])) { header('Location: dashboard.php'); exit; }
$depts = $pdo->query('SELECT * FROM departments ORDER BY id')->fetchAll();
$divs  = $pdo->query('SELECT id, department_id, jina FROM divisions ORDER BY jina')->fetchAll();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $jina = trim($_POST['jina']); $user = strtolower(trim($_POST['username'])); $email = trim($_POST['email']);
  $pass = $_POST['password']; $dep = (int)$_POST['department_id']; $div = (int)($_POST['division_id'] ?? 0);

  $d = $pdo->prepare('SELECT * FROM departments WHERE id=?'); $d->execute([$dep]); $d = $d->fetch();
  if ($jina === '' || !preg_match('/^[a-z0-9._-]{3,30}$/', $user)) $err = 'Jina kamili na jina la mtumiaji (herufi ndogo/namba, angalau 3) vinahitajika.';
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Barua pepe si sahihi.';
  elseif (strlen($pass) < 6) $err = 'Nenosiri liwe na angalau herufi 6.';
  elseif (!$d) $err = 'Chagua idara au kitengo unachofanyia kazi.';
  else {
    if ($d['aina'] === 'idara') {
      $v = $pdo->prepare('SELECT id FROM divisions WHERE id=? AND department_id=?'); $v->execute([$div, $dep]);
      if (!$v->fetch()) $err = 'Chagua divisheni yako ndani ya idara hiyo.';
    } else { $div = null; }
  }
  if (!$err) {
    try {
      $pdo->prepare('INSERT INTO users(username,password,jina,role,department_id,division_id,email) VALUES(?,?,?,?,?,?,?)')
          ->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $jina, 'mfanyakazi', $dep, $div, $email]);
      header('Location: index.php?reg=1'); exit;
    } catch (PDOException $ex) {
      $err = 'Jina hilo la mtumiaji tayari limetumika.';
    }
  }
}
$pageTitle = 'Jisajili';
include 'header.php';
?>
<div class="auth">
  <section class="auth-art">
    <span class="eyebrow">Ofisi ya Mtakwimu Mkuu wa Serikali Zanzibar</span>
    <h1>Fungua akaunti yako</h1>
    <p>Chagua idara na divisheni unayofanyia kazi. Maombi yako yatapitia kwa meneja wa divisheni yako kisha mkurugenzi wa idara, na kuidhinishwa na Mtakwimu Mkuu.</p>
    <ul>
      <li>Wafanyakazi wa vitengo maalum huomba moja kwa moja kwa Mtakwimu Mkuu</li>
    </ul>
  </section>
  <section class="auth-form">
    <form method="post">
      <img class="login-logo" src="assets/ocgs.png" alt="OCGS">
      <h2>Jisajili</h2>
      <?php if ($err): ?><div class="alert bad"><?= e($err) ?></div><?php endif; ?>
      <label>Jina kamili</label><input name="jina" required value="<?= e($_POST['jina'] ?? '') ?>">
      <div class="row">
        <div><label>Jina la mtumiaji</label><input name="username" required value="<?= e($_POST['username'] ?? '') ?>"></div>
        <div><label>Barua pepe</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
      </div>
      <label>Nenosiri</label><input type="password" name="password" required>
      <label>Idara / Kitengo</label>
      <select name="department_id" id="dep" required><?= dept_options($depts, $_POST['department_id'] ?? null) ?></select>
      <label>Divisheni</label>
      <select name="division_id" id="div" data-sel="<?= (int)($_POST['division_id'] ?? 0) ?>"></select>
      <button class="btn block">Fungua akaunti</button>
      <p class="hint">Tayari una akaunti? <a href="index.php"><b>Ingia</b></a></p>
    </form>
  </section>
</div>
<?= org_script($depts, $divs) ?>
<script>linkDept(document.getElementById('dep'), document.getElementById('div'));</script>
<?php include 'footer.php'; ?>
