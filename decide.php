<?php
require 'config.php';
$u = auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }

$r = get_req($pdo, (int)$_POST['id']);
$uamuzi = $_POST['action'] ?? '';
$maoni = trim($_POST['maoni'] ?? '');

if (!$r || !can_act($u, $r) || !in_array($uamuzi, ['Imekubaliwa','Imekataliwa'])) exit('Huna ruhusa ya kuchukua hatua hii.');
if ($uamuzi === 'Imekataliwa' && $maoni === '') exit('Andika sababu ya kukataa. <a href="javascript:history.back()">Rudi</a>');

$stage = $r['hatua'];
$cheo  = HATUA[$stage];
$ombi  = "{$r['aina']} ({$r['tarehe_kuanza']} – {$r['tarehe_kumaliza']})";
$mail  = false;

$pdo->beginTransaction();
$pdo->prepare('INSERT INTO approvals(request_id,hatua,user_id,uamuzi,maoni) VALUES(?,?,?,?,?)')
    ->execute([$r['id'], $stage, $u['id'], $uamuzi, $maoni]);

if ($uamuzi === 'Imekataliwa') {
  $pdo->prepare("UPDATE requests SET hali='Imekataliwa', hatua='imekamilika', maoni=?, mwamuzi_id=? WHERE id=?")
      ->execute([$maoni, $u['id'], $r['id']]);
  $msg = "Ombi lako la $ombi limekataliwa na $cheo ({$u['jina']}). Sababu: $maoni";
  notify($pdo, $r['user_id'], $msg); $mail = true;
} else {
  $next = next_stage($r);
  if ($next) {
    $pdo->prepare('UPDATE requests SET hatua=? WHERE id=?')->execute([$next, $r['id']]);
    notify($pdo, $r['user_id'], "Ombi lako la $ombi limethibitishwa na $cheo ({$u['jina']}). Sasa linasubiri " . HATUA[$next] . '.' . ($maoni ? " Maoni: $maoni" : ''));
    notify_stage($pdo, $r, $next, "Ombi la {$r['aina']} la {$r['jina']} limethibitishwa na $cheo na linasubiri hatua yako.");
  } else {
    $pdo->prepare("UPDATE requests SET hali='Imekubaliwa', hatua='imekamilika', maoni=?, mwamuzi_id=? WHERE id=?")
        ->execute([$maoni, $u['id'], $r['id']]);
    $msg = "Hongera! Ombi lako la $ombi limeidhinishwa na Mtakwimu Mkuu." . ($maoni ? " Maoni: $maoni" : '');
    notify($pdo, $r['user_id'], $msg); $mail = true;
  }
}
$pdo->commit();

if ($mail) {
  @mail($r['email'], 'Jibu la ombi lako la ruhusa', $msg,
        "Content-Type: text/plain; charset=UTF-8\r\nFrom: ruhusa@ocgs.go.tz");
  $final = ($uamuzi === 'Imekataliwa') ? 'limekataliwa' : 'limeidhinishwa';
  foreach ($pdo->query("SELECT id FROM users WHERE role='hr'") as $h)
    notify($pdo, $h['id'], "Ombi la {$r['aina']} la {$r['jina']} $final.");
}

header('Location: dashboard.php');
