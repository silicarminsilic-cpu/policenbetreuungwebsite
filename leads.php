<?php
require __DIR__.'/_lib.php';
$l = $in;
if (!valid_lead($l)) fail(400);
$ok = send_mail(MAIL_TO, 'Neue Anfrage '.line($l['lead_id'] ?? '').' ('.line($l['campaign_type'] ?? '').')', lead_text($l), s($l['email']));
if (!$ok) fail(500);
echo json_encode(['ok'=>true, 'lead_id'=>line($l['lead_id'] ?? '')]);
