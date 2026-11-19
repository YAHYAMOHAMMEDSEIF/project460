<?php
require 'config.php';
$u = auth();
$forced = !empty($u['lazima']);
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? ''; $conf = $_POST['confirm'] ?? '';
  $h = $pdo->prepare('SELECT password FROM users WHERE id=?'); $h->execute([$u['id']]);
  if (!password_verify($cur, $h->fetchColumn())) $err = 'Nenosiri la sasa si sahihi.';
  elseif (strlen($new) < 6)                       $err = 'Nenosiri jipya liwe na angalau herufi 6.';
  elseif ($new !== $conf)                         $err = 'Nenosiri jipya na la kuthibitisha havilingani.';
  elseif ($new === $cur)                          $err = 'Nenosiri jipya lisifanane na la sasa.';
  else {
    $pdo->prepare('UPDATE users SET password=?, badilisha_nenosiri=0 WHERE id=?')
        ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
    session_regenerate_id(true);
    header('Location: dashboard.php?pw=1'); exit;
  }
}

$pageTitle = 'Badilisha Nenosiri'; $pageSub = 'Linda akaunti yako kwa nenosiri unalolijua wewe peke yako'; $active = 'pw';
include 'header.php';
?>
<?php if ($forced): ?>
  <div class="alert warn"><b>Karibu!</b> Kwa usalama wa akaunti yako, tafadhali weka nenosiri lako mwenyewe kabla ya kuendelea.</div>
<?php endif; ?>
<div class="card" style="max-width:520px">
  <div class="card-h"><h2>Nenosiri jipya</h2></div>
  <?php if ($err): ?><div class="alert bad"><?= e($err) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <label><?= $forced ? 'Nenosiri la muda ulilopewa' : 'Nenosiri la sasa' ?></label>
    <input type="password" name="current" class="pw" required>
    <label>Nenosiri jipya (angalau herufi 6)</label>
    <input type="password" name="new" class="pw" required minlength="6">
    <label>Rudia nenosiri jipya</label>
    <input type="password" name="confirm" class="pw" required minlength="6">
    <label class="chk"><input type="checkbox" id="show"> Onyesha nenosiri</label>
    <button class="btn">Hifadhi nenosiri</button>
  </form>
</div>
<script>
document.getElementById('show').onchange=e=>document.querySelectorAll('.pw').forEach(i=>i.type=e.target.checked?'text':'password');
</script>
<?php include 'footer.php'; ?>
