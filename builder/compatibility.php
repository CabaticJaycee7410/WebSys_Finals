<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Compatibility Guide';
$checks = [
 ['Wheel size','The frame, fork, wheelset, and tires need to use matching wheel diameter standards. Check the printed tire and rim markings, not only the marketing name.'],
 ['Brake system','Confirm the frame and fork have the correct disc or rim brake mounts. A disc-brake wheel also needs a compatible rotor attachment.'],
 ['Drivetrain speeds','Shifters, rear derailleur, cassette, and chain need to be compatible as a system. Matching speed count alone does not guarantee compatibility.'],
 ['Handlebar and stem','The handlebar clamp diameter must match the stem clamp diameter, such as 31.8 mm or 35 mm.'],
 ['Seatpost diameter','The seatpost diameter must match the frame seat tube requirement. Check the frame marking or manufacturer specification.'],
 ['Tire clearance','Check the maximum tire width allowed by both the frame and fork. Leave enough clearance for mud and wheel flex.'],
 ['Axles and hubs','Check axle diameter, axle length, and hub spacing against the frame and fork.'],
 ['Bottom bracket','Check the frame shell standard and the crankset spindle requirements before choosing a bottom bracket.']
];
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><p class="eyebrow">BUILD WITH CONFIDENCE</p><h1>Compatibility Guide</h1><p>Use these checks before adding components to your bicycle plan.</p></div></div>
<div class="compatibility-notice"><strong>Important:</strong> This guide is a beginner checklist, not a complete mechanic inspection. Product listings may omit specifications. Confirm exact standards with the frame and component manufacturer before buying or installing parts.</div>
<div class="compatibility-grid"><?php foreach ($checks as $check): ?><article class="compatibility-card"><h3><?= h($check[0]) ?></h3><p><?= h($check[1]) ?></p></article><?php endforeach; ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
