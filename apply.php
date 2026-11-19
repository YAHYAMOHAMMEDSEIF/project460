<?php
require 'config.php';
$u = auth(['mfanyakazi','meneja','mkurugenzi']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }
function back($m){ header('Location: dashboard.php?err=' . urlencode($m)); exit; }

if (!$u['department_id']) back('Hujawekwa kwenye idara. Wasiliana na HR.');
if (($u['idara_aina'] ?? '') === 'idara' && $u['role'] === 'mfanyakazi' && !$u['division_id']) back('Hujawekwa kwenye divisheni. Wasiliana na HR.');

$d1 = $_POST['d1']; $d2 = $_POST['d2'];
if ($d2 < $d1) back('Tarehe ya kumaliza haiwezi kuwa kabla ya kuanza.');

$chombo = $madhumuni = null;
if (($_POST['aina'] ?? '') === 'Safari ya Kikazi') {
  $chombo = trim($_POST['chombo'] ?? ''); $madhumuni = trim($_POST['madhumuni'] ?? '');
  if ($chombo === '' || $madhumuni === '') back('Safari ya kikazi: jaza chombo cha usafiri na madhumuni ya safari.');
}

$jina = null; $kiamb = null;
if (!empty($_FILES['faili']['name'])) {
  $f = $_FILES['faili'];
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if ($f['error'] !== 0 || $f['size'] > 3*1024*1024 || !in_array($ext, ['pdf','jpg','jpeg','png'])) back('Kiambatisho si sahihi (PDF/JPG/PNG, hadi 3MB).');
  $kiamb = bin2hex(random_bytes(12)) . '.' . $ext;
  move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $kiamb);
  $jina = $f['name'];
}

$route = route_for($u);
$pdo->prepare('INSERT INTO requests(user_id,department_id,division_id,aina,tarehe_kuanza,tarehe_kumaliza,maelezo,kiambatisho,jina_asili,njia,hatua,chombo,madhumuni)
               VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')
    ->execute([$u['id'], $u['department_id'], $u['division_id'], $_POST['aina'], $d1, $d2, $_POST['maelezo'], $kiamb, $jina, implode(',', $route), $route[0], $chombo, $madhumuni]);

$r = get_req($pdo, $pdo->lastInsertId());
notify_stage($pdo, $r, $route[0], "Ombi jipya la {$_POST['aina']} kutoka kwa {$u['jina']} linasubiri hatua yako.");

header('Location: dashboard.php?ok=1');
