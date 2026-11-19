<?php
require 'config.php';
$u = auth();
$r = get_req($pdo, (int)($_GET['id'] ?? 0));
if (!$r || !can_view($u, $r)) { http_response_code(403); exit('Huruhusiwi.'); }

$ap = $pdo->prepare('SELECT a.*, x.jina FROM approvals a JOIN users x ON x.id=a.user_id WHERE a.request_id=? ORDER BY a.id');
$ap->execute([$r['id']]); $hist = $ap->fetchAll();
$by = []; foreach ($hist as $h) $by[$h['hatua']] = $h;
$route = explode(',', $r['njia']);
$act = can_act($u, $r);

$pageTitle = 'Maelezo ya Ombi'; $pageSub = 'Hatua za idhini na kiambatisho';
include 'header.php';
$ext = strtolower(pathinfo($r['kiambatisho'] ?? '', PATHINFO_EXTENSION));
?>
<div class="card">
  <div class="card-h"><h2><?= e($r['aina']) ?> – <?= e($r['jina']) ?></h2>
    <span class="b <?= e($r['hali']) ?>"><?= e(hali_label($r)) ?></span></div>

  <div class="steps">
  <?php foreach ($route as $i => $st):
    $a = $by[$st] ?? null; $cls = '';
    if ($a) $cls = $a['uamuzi'] === 'Imekubaliwa' ? 'done' : 'bad';
    elseif ($r['hali'] === 'Inasubiri' && $r['hatua'] === $st) $cls = 'cur';
  ?>
    <div class="step <?= $cls ?>">
      <span class="sdot"><?= $cls === 'done' ? '✓' : ($cls === 'bad' ? '✕' : $i + 1) ?></span>
      <b><?= e(HATUA[$st]) ?></b>
      <small><?= $a ? e($a['jina']) : ($cls === 'cur' ? 'Inasubiri' : '—') ?></small>
    </div>
  <?php endforeach; ?>
  </div>

  <p>Idara/Kitengo: <b><?= e($r['idara'] ?: '—') ?></b><?= $r['divisheni'] ? ' · ' . e($r['divisheni']) : '' ?><br>
     Tarehe: <?= e($r['tarehe_kuanza']) ?> hadi <?= e($r['tarehe_kumaliza']) ?> (siku <?= siku($r['tarehe_kuanza'], $r['tarehe_kumaliza']) ?>)<br>
     Maelezo: <?= e($r['maelezo']) ?: '—' ?>
     <?php if (!empty($r['chombo'])): ?><br>Chombo cha usafiri: <b><?= e($r['chombo']) ?></b>
     <br>Madhumuni ya safari: <?= nl2br(e($r['madhumuni'])) ?><?php endif; ?></p>

  <?php if ($hist): ?>
    <h3>Historia ya maamuzi</h3>
    <?php foreach ($hist as $h): ?>
      <div class="note"><b><?= e(HATUA[$h['hatua']] ?? $h['hatua']) ?> – <?= e($h['jina']) ?>:</b>
        <span class="b <?= e($h['uamuzi']) ?>"><?= e($h['uamuzi']) ?></span>
        <?php if ($h['maoni']) echo '<br>' . e($h['maoni']); ?><small><?= e($h['created_at']) ?></small></div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($r['kiambatisho']): ?>
    <h3>Kiambatisho: <?= e($r['jina_asili']) ?></h3>
    <p class="actions">
      <a class="btn" href="file.php?id=<?= $r['id'] ?>" target="_blank">Fungua kwenye tab mpya</a>
      <a class="btn g" href="file.php?id=<?= $r['id'] ?>&dl=1">Pakua</a>
    </p>
    <?php if ($ext === 'pdf'): ?>
      <object class="pdf" data="file.php?id=<?= $r['id'] ?>" type="application/pdf">
        <p>Kivinjari chako hakioneshi PDF hapa. Tumia kitufe cha <b>Fungua kwenye tab mpya</b> au <b>Pakua</b>.</p>
      </object>
    <?php else: ?>
      <img src="file.php?id=<?= $r['id'] ?>" alt="Kiambatisho">
    <?php endif; ?>
  <?php else: echo '<p>Hakuna kiambatisho.</p>'; endif; ?>

  <?php if ($act): ?>
  <form method="post" action="decide.php">
    <input type="hidden" name="id" value="<?= $r['id'] ?>">
    <label>Maoni / sababu (lazima ukikataa)</label><textarea name="maoni" rows="3"></textarea>
    <p class="actions">
      <button class="ok" name="action" value="Imekubaliwa"><?= $r['hatua'] === 'chief' ? 'Idhinisha (Approve)' : 'Thibitisha (Clear)' ?></button>
      <button class="no" name="action" value="Imekataliwa">Kataa</button>
    </p>
  </form>
  <?php endif; ?>
  <p><a class="btn g" href="dashboard.php">← Rudi kwenye Dashboard</a></p>
</div>
<?php include 'footer.php'; ?>
