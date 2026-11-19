<?php
require 'config.php';
// [username, jina, role, department_id, division_id, email]
$users = [
  ['amina',    'Amina Hassan',   'mfanyakazi', 2, 3,    'amina@ocgs.go.tz'],
  ['juma',     'Juma Omar',      'mfanyakazi', 2, 4,    'juma@ocgs.go.tz'],
  ['baraka',   'Baraka Mushi',   'mfanyakazi', 7, null, 'baraka@ocgs.go.tz'],   // kitengo maalum (TEHAMA)
  ['neema',    'Neema Joseph',   'meneja',     2, 3,    'neema@ocgs.go.tz'],
  ['hamad',    'Hamad Ali',      'meneja',     2, 4,    'hamad@ocgs.go.tz'],
  ['saidi',    'Saidi Khamis',   'mkurugenzi', 2, null, 'saidi@ocgs.go.tz'],
  ['mtakwimu', 'Mtakwimu Mkuu',  'chief',      null, null, 'mtakwimu@ocgs.go.tz'],
  ['hr',       'Afisa HR',       'hr',         1, 1,    'hr@ocgs.go.tz'],
];
$st = $pdo->prepare('INSERT IGNORE INTO users(username,password,jina,role,department_id,division_id,email) VALUES(?,?,?,?,?,?,?)');
foreach ($users as $x) {
  $st->execute([$x[0], password_hash('1234', PASSWORD_DEFAULT), $x[1], $x[2], $x[3], $x[4], $x[5]]);
}
echo 'Imekamilika. Futa faili hili sasa. Nenosiri la wote: 1234';
