<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=ruhusa;charset=utf8mb4', 'root', '', [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

const HATUA = ['meneja'=>'Meneja', 'mkurugenzi'=>'Mkurugenzi', 'chief'=>'Mtakwimu Mkuu'];

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function load_user($pdo, $id){
  $s = $pdo->prepare('SELECT u.id,u.username,u.jina,u.role,u.email,u.department_id,u.division_id,u.badilisha_nenosiri AS lazima,
      d.jina AS idara, d.aina AS idara_aina, v.jina AS divisheni
      FROM users u LEFT JOIN departments d ON d.id=u.department_id
      LEFT JOIN divisions v ON v.id=u.division_id WHERE u.id=?');
  $s->execute([$id]);
  return $s->fetch();
}

function auth($roles = null){
  global $pdo;
  if (empty($_SESSION['uid'])) { header('Location: index.php'); exit; }
  $u = load_user($pdo, $_SESSION['uid']);
  if (!$u) { session_destroy(); header('Location: index.php'); exit; }
  if (!empty($u['lazima']) && !in_array(basename($_SERVER['SCRIPT_NAME']), ['nenosiri.php','logout.php'])) { header('Location: nenosiri.php'); exit; }
  if ($roles && !in_array($u['role'], $roles)) { http_response_code(403); exit('Huruhusiwi.'); }
  return $u;
}

function notify($pdo, $uid, $msg){
  $pdo->prepare('INSERT INTO notifications(user_id, ujumbe) VALUES(?,?)')->execute([$uid, $msg]);
}

/* Mpokeaji wa hatua: meneja wa divisheni, mkurugenzi wa idara, au Mtakwimu Mkuu */
function notify_stage($pdo, $r, $stage, $msg){
  if ($stage === 'meneja') {
    $s = $pdo->prepare("SELECT id FROM users WHERE role='meneja' AND division_id=?"); $s->execute([$r['division_id']]);
  } elseif ($stage === 'mkurugenzi') {
    $s = $pdo->prepare("SELECT id FROM users WHERE role='mkurugenzi' AND department_id=?"); $s->execute([$r['department_id']]);
  } else {
    $s = $pdo->query("SELECT id FROM users WHERE role='chief'");
  }
  foreach ($s->fetchAll() as $x) notify($pdo, $x['id'], $msg);
}

function get_req($pdo, $id){
  $s = $pdo->prepare('SELECT r.*, u.jina, u.email, u.role AS u_role, d.jina AS idara, d.aina AS idara_aina, v.jina AS divisheni
      FROM requests r JOIN users u ON u.id=r.user_id
      LEFT JOIN departments d ON d.id=r.department_id
      LEFT JOIN divisions v ON v.id=r.division_id WHERE r.id=?');
  $s->execute([$id]);
  return $s->fetch();
}

/* Njia ya idhini kulingana na mwombaji */
function route_for($u){
  if (($u['idara_aina'] ?? '') === 'kitengo') return ['chief'];          // vitengo maalum: moja kwa moja
  if ($u['role'] === 'mkurugenzi') return ['chief'];
  if ($u['role'] === 'meneja')     return ['mkurugenzi', 'chief'];
  return ['meneja', 'mkurugenzi', 'chief'];
}

function next_stage($r){
  $route = explode(',', $r['njia']);
  $i = array_search($r['hatua'], $route);
  return ($i !== false && isset($route[$i + 1])) ? $route[$i + 1] : null;
}

function can_act($u, $r){
  if ($r['hali'] !== 'Inasubiri') return false;
  switch ($r['hatua']) {
    case 'meneja':     return $u['role'] === 'meneja' && $u['division_id'] && $u['division_id'] == $r['division_id'];
    case 'mkurugenzi': return $u['role'] === 'mkurugenzi' && $u['department_id'] && $u['department_id'] == $r['department_id'];
    case 'chief':      return $u['role'] === 'chief';
  }
  return false;
}

function can_view($u, $r){
  if (in_array($u['role'], ['hr', 'chief'])) return true;
  if ($u['id'] == $r['user_id']) return true;
  if ($u['role'] === 'meneja')     return $u['division_id'] && $u['division_id'] == $r['division_id'];
  if ($u['role'] === 'mkurugenzi') return $u['department_id'] && $u['department_id'] == $r['department_id'];
  return false;
}

function hali_label($r){
  return $r['hali'] === 'Inasubiri' ? 'Inasubiri ' . (HATUA[$r['hatua']] ?? '') : $r['hali'];
}

function role_label($r){
  return ['mfanyakazi'=>'Mfanyakazi','meneja'=>'Meneja wa Divisheni','mkurugenzi'=>'Mkurugenzi wa Idara','chief'=>'Mtakwimu Mkuu','hr'=>'Afisa HR'][$r] ?? $r;
}

function org_label($u){
  if (empty($u['idara'])) return 'Ofisi Kuu';
  return $u['idara'] . (!empty($u['divisheni']) ? ' · ' . $u['divisheni'] : '');
}

function dept_options($depts, $sel = null){
  $o = '<option value="">— chagua idara / kitengo —</option>';
  foreach (['idara'=>'Idara', 'kitengo'=>'Vitengo Maalum'] as $k => $lab) {
    $o .= '<optgroup label="' . $lab . '">';
    foreach ($depts as $d) if ($d['aina'] === $k)
      $o .= '<option value="' . $d['id'] . '"' . ($sel == $d['id'] ? ' selected' : '') . '>' . e($d['jina']) . '</option>';
    $o .= '</optgroup>';
  }
  return $o;
}

function org_script($depts, $divs){
  $kit = [];
  foreach ($depts as $d) if ($d['aina'] === 'kitengo') $kit[] = (int)$d['id'];
  $dv = [];
  foreach ($divs as $d) $dv[] = ['id'=>(int)$d['id'], 'department_id'=>(int)$d['department_id'], 'jina'=>$d['jina']];
  return '<script>window.DIVS=' . json_encode($dv) . ';window.KIT=' . json_encode($kit) . ';</script><script src="app.js"></script>';
}

function ic($n){
  $p = [
    'home'   => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    'plus'   => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',
    'file'   => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
    'bell'   => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'clock'  => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    'check'  => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
    'x'      => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
    'out'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
    'layers' => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
    'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
    'key'    => '<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
    'users'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  ];
  return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $p[$n] . '</svg>';
}
function siku($a, $b){ return (int)((strtotime($b) - strtotime($a)) / 86400) + 1; }
function tarehe_leo(){
  $s = ['Jumapili','Jumatatu','Jumanne','Jumatano','Alhamisi','Ijumaa','Jumamosi'];
  $m = ['Januari','Februari','Machi','Aprili','Mei','Juni','Julai','Agosti','Septemba','Oktoba','Novemba','Desemba'];
  return $s[date('w')] . ', ' . date('j') . ' ' . $m[date('n') - 1] . ' ' . date('Y');
}
