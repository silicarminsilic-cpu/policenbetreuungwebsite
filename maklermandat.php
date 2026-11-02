<?php
require __DIR__.'/_lib.php';
$l = $in['lead'] ?? null; $m = $in['mandate'] ?? null;
if (!valid_lead($l) || !is_array($m)) fail(400);
$c = $m['consents'] ?? [];
$s1 = png_bytes($m['signature1_png'] ?? null);
if (!$s1 || empty($c['mandate_read']) || empty($c['agb_art45']) || empty($c['data_transfer'])) fail(400);
$s2 = png_bytes($m['signature2_png'] ?? null);
$id = preg_replace('/[^A-Za-z0-9-]/', '', s($l['lead_id'] ?? ''));
$m1 = $m['mandant1'] ?? [];
$t = $m['text_snapshot'] ?? [];

$txt  = "MAKLERMANDAT / VOLLMACHT UND AUFTRAG FÜR DIE VERWALTUNG DES VERSICHERUNGSPORTEFEUILLES\n\n";
$txt .= "Mandant: ".s($m1['name'] ?? '').($m['mandant2'] ? ' und '.s($m['mandant2']) : '')."\n".s($m1['street'] ?? '').", ".s($m1['zip'] ?? '')." ".s($m1['city'] ?? '')."\n";
$txt .= "Makler: ".s($m['party']['name'] ?? '').", ".s($m['party']['addr'] ?? '')."\n\n";
$txt .= "Betreffende Policen / Verträge / Bereiche: ".implode('; ', array_map('s', (array)($m['areas'] ?? [])))."\nReporting: ".s($m['reporting'] ?? '')."\n\n";
foreach ((array)($t['boxes'] ?? []) as $b) $txt .= s($b, 4000)."\n\n";
$txt .= "Dieses Maklermandat hat ab dem Datum der Unterzeichnung volle Gültigkeit.\n\n";
$txt .= s($m['place'] ?? '').", den ".s($m['date'] ?? '')."\n";
$txt .= "Online unterzeichnet am ".s($m['signed_at'] ?? '')." (Mandatsversion ".s($m['version'] ?? '').")\nSHA-256 des Textes: ".s($m['text_sha256'] ?? '')."\n";
$txt .= "Unterschriften siehe Anhang. Gegenzeichnung der Wertenburg GmbH folgt nach Prüfung.\n";

$att = ['Unterschrift-Mandant-1.png' => ['image/png', $s1], 'Maklermandat-'.$id.'.txt' => ['text/plain; charset=UTF-8', $txt]];
if ($s2) $att['Unterschrift-Mandant-2.png'] = ['image/png', $s2];

$body = "Online unterzeichnetes Maklermandat.\n\n".lead_text($l)."\n----\n\n".$txt;
if (!send_mail(MAIL_TO, 'Unterzeichnetes Maklermandat '.$id, $body, s($l['email']), $att)) fail(500);
// Kopie an den Kunden (Fehler hier ändern nichts am Erfolg)
send_mail(s($l['email']), 'Ihr Maklermandat – policenbetreuung.ch', "Guten Tag ".line($l['first_name'])."\n\nVielen Dank. Nachfolgend erhalten Sie eine Kopie Ihres online unterzeichneten Maklermandats. Wir melden uns bei Ihnen.\n\nPolicenbetreuung.ch\n".MAIL_TO."\n\n----\n\n".$txt, null, $att);
echo json_encode(['ok'=>true, 'lead_id'=>$id]);
