<?php
require 'config.php';
$u = auth(['hr']);
$roles = ['mfanyakazi','meneja','mkurugenzi','chief','hr'];
$depts = $pdo->query('SELECT * FROM departments ORDER BY id')->fetchAll();
$divs  = $pdo->query('SELECT id, department_id, jina FROM divisions ORDER BY jina')->fetchAll();
$msg = ''; $err = '';

/* Hurekebisha idara/divisheni: kitengo maalum hakina divisheni; divisheni lazima iwe ya idara husika */
function fix_org($pdo, &$dep, &$div){
  if (!$dep) { $div = null; return; }
  $d = $pdo->prepare('SELECT aina FROM departments WHERE id=?'); $d->execute([$dep]); $d = $d->fetch();
  if (!$d) { $dep = null; $div = null; return; }
  if ($d['aina'] === 'kitengo') { $div = null; return; }
  if ($div) {
    $v = $pdo->prepare('SELECT id FROM divisions WHERE id=? AND department_id=?'); $v->execute([$div, $dep]);
    if (!$v->fetch()) $div = null;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'reset') {   // ===== RESET NENOSIRI =====
  $rid = (int)$_POST['id'];
  $t = $pdo->prepare('SELECT jina FROM users WHERE id=?'); $t->execute([$rid]); $tj = $t->fetchColumn();
  if (!$tj) $err = 'Mtumiaji hajapatikana.';
  elseif ($rid === (int)$u['id']) $err = 'Nenosiri lako mwenyewe libadilishe kupitia ukurasa wa "Nenosiri".';
  else {
    $pdo->prepare('UPDATE users SET password=?, badilisha_nenosiri=1 WHERE id=?')
        ->execute([password_hash('123456', PASSWORD_DEFAULT), $rid]);
    $msg = "Nenosiri la $tj limewekwa upya kuwa 123456. Ataombwa kuweka jipya akiingia.";
  }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $role = $_POST['role'] ?? '';
  $dep = (int)($_POST['department_id'] ?? 0) ?: null;
  $div = (int)($_POST['division_id'] ?? 0) ?: null;
  if (!in_array($role, $roles)) exit('Nafasi si sahihi.');

  if (($_POST['do'] ?? '') === 'create') {                       // ===== KUSAJILI MTUMIAJI MPYA =====
    $jina = trim($_POST['jina']); $user = strtolower(trim($_POST['username']));
    $email = trim($_POST['email']); $pass = $_POST['password'];
    fix_org($pdo, $dep, $div);
    $idara = false;
    if ($dep) { $q = $pdo->prepare("SELECT aina FROM departments WHERE id=?"); $q->execute([$dep]); $idara = ($q->fetchColumn() === 'idara'); }

    if ($jina === '' || !preg_match('/^[a-z0-9._-]{3,30}$/', $user)) $err = 'Jina kamili na jina la mtumiaji (herufi ndogo/namba, angalau 3) vinahitajika.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Barua pepe si sahihi.';
    elseif (strlen($pass) < 6) $err = 'Nenosiri la muda liwe na angalau herufi 6.';
    elseif (in_array($role, ['mfanyakazi','meneja','mkurugenzi']) && !$dep) $err = 'Chagua idara au kitengo cha mtumiaji huyu.';
    elseif ($idara && in_array($role, ['mfanyakazi','meneja']) && !$div) $err = 'Chagua divisheni ya mtumiaji huyu.';
    else {
      try {
        $pdo->prepare('INSERT INTO users(username,password,jina,role,department_id,division_id,email,badilisha_nenosiri) VALUES(?,?,?,?,?,?,?,1)')
            ->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $jina, $role, $dep, $div, $email]);
        $msg = "Mtumiaji $jina amesajiliwa. Mpe jina la mtumiaji \"$user\" na nenosiri la muda ulilomwekea. Ataombwa kuweka nenosiri lake mwenyewe akiingia mara ya kwanza.";
        $_POST = [];
      } catch (PDOException $ex) { $err = 'Jina hilo la mtumiaji tayari limetumika.'; }
    }
  } else {                                                      // ===== KUBADILISHA MTUMIAJI ALIYEPO =====
    $id = (int)$_POST['id'];
    if ($id === (int)$u['id']) $role = 'hr';                     // usijiondolee nafasi ya HR
    fix_org($pdo, $dep, $div);
    $pdo->prepare('UPDATE users SET role=?, department_id=?, division_id=? WHERE id=?')->execute([$role, $dep, $div, $id]);
    $msg = 'Mabadiliko yamehifadhiwa.';
  }
}

$users = $pdo->query('SELECT * FROM users ORDER BY FIELD(role,"chief","mkurugenzi","meneja","mfanyakazi","hr"), jina')->fetchAll();
$pageTitle = 'Watumiaji'; $pageSub = 'Sajili watumiaji wapya na uweke nafasi, idara na divisheni'; $active = 'wat';
include 'header.php';
$keep = $err ? $_POST : [];
?>
<?php if ($msg): ?><div class="alert good"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert bad"><?= e($err) ?></div><?php endif; ?>

<div class="card" id="mpya">
  <div class="card-h"><h2>Sajili mtumiaji mpya</h2><span>Mpe nenosiri la muda, kisha amjulishe mtumiaji</span></div>
  <form method="post">
    <input type="hidden" name="do" value="create">
    <div class="row">
      <div><label>Jina kamili</label><input name="jina" required value="<?= e($keep['jina'] ?? '') ?>"></div>
      <div><label>Barua pepe</label><input type="email" name="email" required value="<?= e($keep['email'] ?? '') ?>"></div>
    </div>
    <div class="row">
      <div><label>Jina la mtumiaji</label><input name="username" required value="<?= e($keep['username'] ?? '') ?>"></div>
      <div><label>Nenosiri la muda</label><input type="text" name="password" required autocomplete="off"></div>
    </div>
    <label>Nafasi</label>
    <select name="role"><?php foreach ($roles as $ro) echo '<option value="'.$ro.'"'.(($keep['role'] ?? 'mfanyakazi')===$ro?' selected':'').'>'.e(role_label($ro)).'</option>'; ?></select>
    <div class="row">
      <div><label>Idara / Kitengo</label><select name="department_id" id="ndep"><?= dept_options($depts, $keep['department_id'] ?? null) ?></select></div>
      <div><label>Divisheni</label><select name="division_id" id="ndiv" data-sel="<?= (int)($keep['division_id'] ?? 0) ?>"></select></div>
    </div>
    <p class="hint" style="text-align:left">Meneja huwekwa kwenye divisheni yake, Mkurugenzi kwenye idara yake. Mtakwimu Mkuu na HR hawahitaji idara.</p>
    <button class="btn">Sajili mtumiaji</button>
  </form>
</div>

<div class="card">
  <div class="card-h"><h2>Watumiaji wote</h2>
    <div class="tools">
      <span id="cnt"><?= count($users) ?> kwa jumla</span>
      <div class="search"><?= ic('search') ?><input id="uq" placeholder="Tafuta jina, jina la mtumiaji, barua pepe, idara..." autocomplete="off"></div>
    </div>
  </div>
  <div class="tw"><table>
    <thead><tr><th>Mtumiaji</th><th>Nafasi</th><th>Idara / Kitengo</th><th>Divisheni</th><th></th></tr></thead>
    <tbody>
    <?php
      $dn = []; foreach ($depts as $d) $dn[$d['id']] = $d['jina'];
      $vn = []; foreach ($divs as $d) $vn[$d['id']] = $d['jina'];
      foreach ($users as $x): $fid = 'f' . $x['id'];
        $hay = mb_strtolower(implode(' ', [$x['jina'], $x['username'], $x['email'], role_label($x['role']),
               $dn[$x['department_id']] ?? '', $vn[$x['division_id']] ?? '']));
    ?>
      <tr class="urow" data-s="<?= e($hay) ?>">
        <td><b><?= e($x['jina']) ?></b><small><?= e($x['username']) ?> · <?= e($x['email']) ?></small></td>
        <td><select name="role" form="<?= $fid ?>"><?php foreach ($roles as $ro) echo '<option value="'.$ro.'"'.($x['role']===$ro?' selected':'').'>'.e(role_label($ro)).'</option>'; ?></select></td>
        <td><select class="dsel" name="department_id" form="<?= $fid ?>"><?= dept_options($depts, $x['department_id']) ?></select></td>
        <td><select class="vsel" name="division_id" form="<?= $fid ?>" data-sel="<?= (int)$x['division_id'] ?>"></select></td>
        <td><form id="<?= $fid ?>" method="post"><input type="hidden" name="id" value="<?= $x['id'] ?>"><button class="btn sm">Hifadhi</button></form>
          <?php if ($x['id'] != $u['id']): ?>
          <form method="post" style="margin-top:6px" onsubmit="return confirm('Weka upya nenosiri la <?= e(addslashes($x['jina'])) ?> kuwa 123456?')">
            <input type="hidden" name="do" value="reset"><input type="hidden" name="id" value="<?= $x['id'] ?>">
            <button class="btn sm dg">Reset nenosiri</button>
          </form>
          <?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p class="empty" id="nores" style="display:none">Hakuna mtumiaji anayelingana na ulichotafuta.</p></div>
</div>
<?= org_script($depts, $divs) ?>
<script>
linkDept(document.getElementById('ndep'), document.getElementById('ndiv'));
document.querySelectorAll('.dsel').forEach(d=>linkDept(d,d.closest('tr').querySelector('.vsel')));
const urows=[...document.querySelectorAll('.urow')];
document.getElementById('uq').addEventListener('input',e=>{
  const q=e.target.value.trim().toLowerCase(); let n=0;
  urows.forEach(r=>{const ok=!q||r.dataset.s.includes(q); r.style.display=ok?'':'none'; if(ok)n++;});
  document.getElementById('cnt').textContent=q?(n+' kati ya '+urows.length):(urows.length+' kwa jumla');
  document.getElementById('nores').style.display=n?'none':'';
});
</script>
<?php include 'footer.php'; ?>
