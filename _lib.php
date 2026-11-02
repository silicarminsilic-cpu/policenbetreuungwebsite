<?php
// Gemeinsame Funktionen. Einstellungen hier anpassen.
const MAIL_TO   = 'anfrage@policenbetreuung.ch';   // Empfänger (bei Infomaniak angelegtes Postfach)
const MAIL_FROM = 'anfrage@policenbetreuung.ch';   // Absender: muss ein Postfach dieser Domain sein
const MAX_BODY  = 6000000;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
function fail($code){ http_response_code($code); echo json_encode(['ok'=>false]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405);

// einfache Ratenbegrenzung pro IP (20 Anfragen / 10 Minuten)
$f = sys_get_temp_dir().'/pb_rl_'.md5($_SERVER['REMOTE_ADDR'] ?? '').'.json';
$h = @json_decode(@file_get_contents($f), true) ?: [];
$h = array_values(array_filter($h, fn($t)=>$t > time()-600));
if (count($h) >= 20) fail(429);
$h[] = time(); @file_put_contents($f, json_encode($h));

$raw = file_get_contents('php://input', false, null, 0, MAX_BODY + 1);
if (!$raw || strlen($raw) > MAX_BODY) fail(400);
$in = json_decode($raw, true);
if (!is_array($in)) fail(400);

function s($v, $m = 300){ return mb_substr(trim((string)($v ?? '')), 0, $m); }
function line($v){ return preg_replace('/[\r\n]+/', ' ', s($v, 200)); }
function valid_lead($l){
  if (!is_array($l)) return false;
  if (s($l['first_name'] ?? '') === '') return false;
  if (!filter_var(s($l['email'] ?? ''), FILTER_VALIDATE_EMAIL)) return false;
  return strlen(preg_replace('/\D/', '', s($l['phone'] ?? ''))) >= 9;
}
function lead_text($l){
  $o = '';
  foreach ($l as $k => $v) { if ($k === 'mandate') continue; $o .= $k.': '.(is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v)."\n"; }
  return $o;
}
function png_bytes($u){ return (is_string($u) && preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $u, $m)) ? base64_decode($m[1]) : null; }

// E-Mail mit Text und Anhängen (multipart/mixed, UTF-8)
function send_mail($to, $subject, $text, $reply = null, $attach = []){
  $b = 'pb'.bin2hex(random_bytes(8));
  $hd = "From: ".MAIL_FROM."\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"$b\"\r\n";
  if ($reply && filter_var($reply, FILTER_VALIDATE_EMAIL)) $hd .= "Reply-To: $reply\r\n";
  $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($text))."\r\n";
  foreach ($attach as $name => [$mime, $bytes]) {
    $body .= "--$b\r\nContent-Type: $mime; name=\"$name\"\r\nContent-Disposition: attachment; filename=\"$name\"\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($bytes))."\r\n";
  }
  $body .= "--$b--";
  $subj = '=?UTF-8?B?'.base64_encode($subject).'?=';
  if (getenv('PB_TEST')) { file_put_contents(getenv('PB_TEST').'/'.bin2hex(random_bytes(3)).'.eml', "To: $to\r\nSubject: $subject\r\n$hd\r\n$body"); return true; }
  return mail($to, $subj, $body, $hd, '-f'.MAIL_FROM);
}
