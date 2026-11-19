<?php
require 'config.php';
$u = auth();
$r = get_req($pdo, (int)($_GET['id'] ?? 0));
if (!$r || !$r['kiambatisho'] || !can_view($u, $r)) { http_response_code(403); exit('Huruhusiwi.'); }

$path = __DIR__ . '/uploads/' . basename($r['kiambatisho']);
if (!is_file($path)) { http_response_code(404); exit('Faili halipo kwenye folda ya uploads (upload ilishindikana).'); }

$types = ['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png'];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$name = preg_replace('/[^A-Za-z0-9._-]/', '_', $r['jina_asili'] ?: ('kiambatisho.' . $ext));
$mode = isset($_GET['dl']) ? 'attachment' : 'inline';

while (ob_get_level()) ob_end_clean();   // futa output yoyote isiyotakiwa
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header("Content-Disposition: $mode; filename=\"$name\"");
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
