<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Mfumo wa Maombi ya Ruhusa') ?> · OCGS</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="<?= empty($u) ? 'guest' : '' ?>">
<?php if (!empty($u)):
  if (!isset($unread)) {
    $c = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND imesomwa=0');
    $c->execute([$u['id']]); $unread = (int)$c->fetchColumn();
  }
  $init = mb_strtoupper(mb_substr($u['jina'], 0, 1));
  $apply = in_array($u['role'], ['mfanyakazi','meneja','mkurugenzi']);
?>
<div class="app">
  <aside class="side">
    <div class="brand"><span class="logo-img"><img src="assets/ocgs.png" alt="OCGS"></span><div><b>OCGS</b><small>Mfumo wa Ruhusa</small></div></div>
    <nav>
      <a class="<?= ($active ?? 'dash') === 'dash' ? 'active' : '' ?>" href="dashboard.php"><?= ic('home') ?>Dashboard</a>
      <?php if ($apply): ?><a href="dashboard.php#fomu"><?= ic('plus') ?>Omba Ruhusa</a><?php endif; ?>
      <a href="dashboard.php#maombi"><?= ic('file') ?>Maombi</a>
      <a href="dashboard.php#taarifa"><?= ic('bell') ?>Taarifa<?php if ($unread): ?><span class="count"><?= $unread ?></span><?php endif; ?></a>
      <a class="<?= ($active ?? '') === 'pw' ? 'active' : '' ?>" href="nenosiri.php"><?= ic('key') ?>Nenosiri</a>
      <?php if ($u['role'] === 'hr'): ?><a class="<?= ($active ?? '') === 'wat' ? 'active' : '' ?>" href="watumiaji.php"><?= ic('users') ?>Watumiaji</a><a href="watumiaji.php#mpya"><?= ic('plus') ?>Sajili Mtumiaji</a><a class="<?= ($active ?? '') === 'org' ? 'active' : '' ?>" href="idara.php"><?= ic('layers') ?>Idara & Divisheni</a><?php endif; ?>
    </nav>
    <div class="userbox">
      <span class="avatar"><?= e($init) ?></span>
      <div><b><?= e($u['jina']) ?></b><small><?= e(role_label($u['role'])) ?></small></div>
      <a class="logout" href="logout.php" title="Toka"><?= ic('out') ?></a>
    </div>
  </aside>
  <div class="content">
    <div class="topbar">
      <div>
        <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
        <p><?= e($pageSub ?? tarehe_leo()) ?></p>
      </div>
      <a class="bell" href="dashboard.php#taarifa"><?= ic('bell') ?><?php if ($unread): ?><i><?= $unread ?></i><?php endif; ?></a>
    </div>
    <main>
<?php endif; ?>
