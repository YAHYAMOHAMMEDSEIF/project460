<?php
require 'config.php';
if (!empty($_SESSION['uid'])) { header('Location: dashboard.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $s = $pdo->prepare('SELECT id, password FROM users WHERE username=?');
  $s->execute([strtolower(trim($_POST['username']))]);
  $row = $s->fetch();
  if ($row && password_verify($_POST['password'], $row['password'])) {
    session_regenerate_id(true);
    $_SESSION['uid'] = $row['id'];
    header('Location: dashboard.php'); exit;
  }
  $err = 'Jina au nenosiri si sahihi.';
}
$pageTitle = 'Ingia';
include 'header.php';
?>
<div class="auth">
  <section class="auth-art">
    <span class="eyebrow">Ofisi ya Mtakwimu Mkuu wa Serikali Zanzibar</span>
    <h1>Mfumo wa Maombi ya Ruhusa</h1>
    <p>Omba, pitia na fuatilia ruhusa kwa njia ya haraka, wazi na salama.</p>
    <ul>
      <li>Chagua idara na divisheni yako, ombi linafuata njia sahihi ya idhini</li>
      <li>Meneja → Mkurugenzi → Mtakwimu Mkuu</li>
      <li>Vitengo maalum vinaomba moja kwa moja kwa Mtakwimu Mkuu</li>
    </ul>
  </section>
  <section class="auth-form">
    <form method="post">
      <img class="login-logo" src="assets/ocgs.png" alt="OCGS - Statistics for Development">
      <h2>Karibu tena</h2>
      <p class="sub">Ingia kwa akaunti yako kuendelea.</p>
      <?php if (isset($_GET['reg'])): ?><div class="alert good">Akaunti imeundwa. Ingia sasa.</div><?php endif; ?>
      <label>Jina la mtumiaji</label><input name="username" required autofocus>
      <label>Nenosiri</label><input type="password" name="password" required>
      <?php if ($err): ?><div class="alert bad"><?= e($err) ?></div><?php endif; ?>
      <button class="btn block">Ingia</button>
      <p class="hint">Huna akaunti? <a href="register.php"><b>Jisajili hapa</b></a></p>
    </form>
  </section>
</div>
<?php include 'footer.php'; ?>
