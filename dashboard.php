<?php
require 'config.php';
$u = auth();
$canApply   = in_array($u['role'], ['mfanyakazi','meneja','mkurugenzi']);
$isApprover = in_array($u['role'], ['meneja','mkurugenzi','chief']);

$c = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND imesomwa=0');
$c->execute([$u['id']]); $unread = (int)$c->fetchColumn();
$n = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 15');
$n->execute([$u['id']]);
$notes = $n->fetchAll();
$pdo->prepare('UPDATE notifications SET imesomwa=1 WHERE user_id=?')->execute([$u['id']]);

$base = 'SELECT r.*, u.jina, d.jina AS idara, v.jina AS divisheni FROM requests r
         JOIN users u ON u.id=r.user_id
         LEFT JOIN departments d ON d.id=r.department_id
         LEFT JOIN divisions v ON v.id=r.division_id';
if ($u['role']==='mfanyakazi')      { $sql = $base.' WHERE r.user_id=? ORDER BY r.id DESC'; $p = [$u['id']]; }
elseif ($u['role']==='meneja')      { $sql = $base.' WHERE r.division_id=? OR r.user_id=? ORDER BY r.id DESC'; $p = [$u['division_id'], $u['id']]; }
elseif ($u['role']==='mkurugenzi')  { $sql = $base.' WHERE r.department_id=? OR r.user_id=? ORDER BY r.id DESC'; $p = [$u['department_id'], $u['id']]; }
else                                { $sql = $base.' ORDER BY r.id DESC'; $p = []; }
$q = $pdo->prepare($sql); $q->execute($p); $reqs = $q->fetchAll();

$cnt = ['Inasubiri'=>0,'Imekubaliwa'=>0,'Imekataliwa'=>0]; $mine = 0;
foreach ($reqs as $r) { $cnt[$r['hali']]++; if (can_act($u, $r)) $mine++; }

$first = explode(' ', $u['jina'])[0];
$pageTitle = 'Karibu, ' . $first;
$aina = ['Likizo ya kawaida','Ugonjwa','Dharura','Uzazi','Masomo','Safari ya Kikazi','Nyingine'];
$vyombo = ['Gari la ofisi','Gari binafsi','Basi / Daladala','Ndege','Meli / Boti','Nyingine'];
$jumlaLabel = $u['role']==='mfanyakazi' ? 'Maombi yangu' : ($u['role']==='hr' || $u['role']==='chief' ? 'Maombi yote' : 'Maombi ya eneo langu');
include 'header.php';
?>

<section class="hero">
  <div>
    <span class="eyebrow">Ofisi ya Mtakwimu Mkuu wa Serikali Zanzibar</span>
    <h2>Takwimu kwa Maendeleo</h2>
    <p><?= e(role_label($u['role'])) ?> · <?= e(org_label($u)) ?></p>
  </div>
  <div class="hero-logo"><img src="assets/ocgs.png" alt="OCGS"></div>
</section>

<?php if (isset($_GET['pw'])): ?><div class="alert good">Nenosiri lako limebadilishwa kwa mafanikio.</div><?php endif; ?>
<?php if (isset($_GET['ok'])): ?><div class="alert good">Ombi lako limetumwa. Linasubiri hatua ya kwanza ya idhini.</div><?php endif; ?>
<?php if (isset($_GET['err'])): ?><div class="alert bad"><?= e($_GET['err']) ?></div><?php endif; ?>
<?php if ($isApprover && $mine > 0): ?>
  <div class="alert warn">Una maombi <b><?= $mine ?></b> yanayosubiri hatua yako.</div>
<?php endif; ?>

<section class="stats">
  <div class="stat"><span class="si blue"><?= ic('layers') ?></span><div><small><?= $jumlaLabel ?></small><b><?= count($reqs) ?></b></div></div>
  <div class="stat"><span class="si amber"><?= ic('clock') ?></span><div><small>Yanasubiri</small><b><?= $cnt['Inasubiri'] ?></b></div></div>
  <div class="stat"><span class="si green"><?= ic('check') ?></span><div><small>Yaliyokubaliwa</small><b><?= $cnt['Imekubaliwa'] ?></b></div></div>
  <div class="stat"><span class="si red"><?= ic('x') ?></span><div><small>Yaliyokataliwa</small><b><?= $cnt['Imekataliwa'] ?></b></div></div>
</section>

<?php if ($canApply): ?>
<div class="grid">
  <section class="card" id="fomu">
    <div class="card-h"><h2>Omba Ruhusa</h2><span>Jaza fomu kisha tuma</span></div>
    <?php if (!$u['department_id']): ?>
      <div class="alert warn">Hujawekwa kwenye idara. Wasiliana na HR ili akuweke kwenye idara na divisheni yako.</div>
    <?php else:
      $route = route_for($u);
      $chain = implode(' → ', array_map(function($s){ return HATUA[$s]; }, $route));
    ?>
    <form method="post" action="apply.php" enctype="multipart/form-data">
      <div class="row">
        <div><label>Jina</label><input value="<?= e($u['jina']) ?>" disabled></div>
        <div><label>Idara / Kitengo</label><input value="<?= e($u['idara']) ?>" disabled></div>
      </div>
      <label>Divisheni</label><input value="<?= e($u['divisheni'] ?: 'Hakuna (kitengo maalum)') ?>" disabled>
      <div class="route"><b>Njia ya idhini:</b> <?= $route === ['chief'] ? 'Moja kwa moja kwa Mtakwimu Mkuu' : e($chain) ?></div>
      <label>Aina ya ruhusa</label>
      <select name="aina" id="aina"><?php foreach ($aina as $a) echo '<option>'.e($a).'</option>'; ?></select>
      <div class="row">
        <div><label>Tarehe ya kuanza</label><input type="date" name="d1" required></div>
        <div><label>Tarehe ya kumaliza</label><input type="date" name="d2" required></div>
      </div>
      <div id="safari" class="safari" style="display:none">
        <div class="safari-h">Taarifa za Safari ya Kikazi</div>
        <label>Chombo cha usafiri</label>
        <select name="chombo" id="chombo"><option value="">— chagua chombo cha usafiri —</option>
          <?php foreach ($vyombo as $v) echo '<option>'.e($v).'</option>'; ?></select>
        <label>Madhumuni ya safari</label>
        <textarea name="madhumuni" id="madhumuni" rows="3" placeholder="Eleza kwa ufupi sababu na lengo la safari hii"></textarea>
      </div>
      <label>Maelezo (hiari)</label><textarea name="maelezo" rows="2"></textarea>
      <label>Barua / kiambatisho (PDF, JPG, PNG hadi 3MB)</label>
      <input type="file" name="faili" accept=".pdf,.jpg,.jpeg,.png">
      <button class="btn">Tuma ombi</button>
    </form>
    <?php endif; ?>
  </section>

  <section class="card" id="taarifa">
    <div class="card-h"><h2>Taarifa</h2><span><?= $unread ?> mpya</span></div>
    <?php foreach ($notes as $x): ?>
      <div class="note <?= $x['imesomwa'] ? '' : 'new' ?>"><?= e($x['ujumbe']) ?><small><?= e($x['created_at']) ?></small></div>
    <?php endforeach; if (!$notes) echo '<p class="empty">Hakuna taarifa bado.</p>'; ?>
  </section>
</div>
<?php endif; ?>

<section class="card" id="maombi">
  <div class="card-h">
    <h2><?= $jumlaLabel ?></h2>
    <div class="tools"><div class="search"><?= ic('search') ?><input id="q" placeholder="Tafuta..."></div></div>
  </div>
  <div class="chips">
    <button class="chip on" data-f="all">Yote</button>
    <?php if ($isApprover): ?><button class="chip" data-f="mine">Yanayonisubiri (<?= $mine ?>)</button><?php endif; ?>
    <button class="chip" data-f="Inasubiri">Yanasubiri</button>
    <button class="chip" data-f="Imekubaliwa">Yaliyokubaliwa</button>
    <button class="chip" data-f="Imekataliwa">Yaliyokataliwa</button>
  </div>
  <div class="tw"><table id="tbl">
    <thead><tr><th>Mfanyakazi</th><th>Aina</th><th>Muda</th><th>Hali</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($reqs as $r): $act = can_act($u, $r); ?>
      <tr data-h="<?= e($r['hali']) ?>" data-a="<?= $act ? 1 : 0 ?>">
        <td><div class="who"><span class="avatar sm"><?= e(mb_strtoupper(mb_substr($r['jina'],0,1))) ?></span>
          <div><b><?= e($r['jina']) ?></b><small><?= e($r['idara'] ?: '—') ?><?= $r['divisheni'] ? ' · '.e($r['divisheni']) : '' ?></small></div></div></td>
        <td><?= e($r['aina']) ?></td>
        <td><?= e($r['tarehe_kuanza']) ?> – <?= e($r['tarehe_kumaliza']) ?><small>siku <?= siku($r['tarehe_kuanza'], $r['tarehe_kumaliza']) ?></small></td>
        <td><span class="b <?= e($r['hali']) ?>"><?= e(hali_label($r)) ?></span>
          <?php if ($r['maoni'] && $r['hali'] !== 'Inasubiri'): ?><small class="rem"><?= e($r['maoni']) ?></small><?php endif; ?></td>
        <td><a class="btn sm <?= $act ? '' : 'g' ?>" href="view.php?id=<?= $r['id'] ?>"><?= $act ? 'Pitia' : 'Angalia' ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (!$reqs) echo '<p class="empty">Hakuna maombi bado.</p>'; ?>
  </div>
</section>

<?php if (!$canApply): ?>
<section class="card" id="taarifa">
  <div class="card-h"><h2>Taarifa</h2><span><?= $unread ?> mpya</span></div>
  <?php foreach ($notes as $x): ?>
    <div class="note <?= $x['imesomwa'] ? '' : 'new' ?>"><?= e($x['ujumbe']) ?><small><?= e($x['created_at']) ?></small></div>
  <?php endforeach; if (!$notes) echo '<p class="empty">Hakuna taarifa bado.</p>'; ?>
</section>
<?php endif; ?>

<script>
(function(){
  const a=document.getElementById('aina'); if(!a) return;
  const box=document.getElementById('safari'), c=document.getElementById('chombo'), m=document.getElementById('madhumuni');
  function t(){ const on=a.value==='Safari ya Kikazi'; box.style.display=on?'':'none'; c.required=on; m.required=on; }
  a.addEventListener('change',t); t();
})();
</script>
<script>
let f='all',q='';
const rows=[...document.querySelectorAll('#tbl tbody tr')];
function ap(){rows.forEach(r=>{
  const okF = f==='all' || (f==='mine' ? r.dataset.a==='1' : r.dataset.h===f);
  r.style.display=(okF && r.textContent.toLowerCase().includes(q))?'':'none';})}
document.querySelectorAll('.chip').forEach(c=>c.onclick=()=>{document.querySelectorAll('.chip').forEach(x=>x.classList.remove('on'));c.classList.add('on');f=c.dataset.f;ap()});
document.getElementById('q').oninput=e=>{q=e.target.value.toLowerCase();ap()};
</script>
<?php include 'footer.php'; ?>
