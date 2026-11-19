<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Parts Guide';
$parts = [
 ['Frame','The main structure of the bicycle. It determines the bike style, fit, and many compatibility standards.'],
 ['Fork','Holds the front wheel and connects it to the frame. Some forks include suspension.'],
 ['Handlebar','The part you hold to steer the bicycle. Shape and width affect comfort and control.'],
 ['Stem','Connects the handlebar to the fork steerer tube. Check both clamp diameters.'],
 ['Headset','The bearings and parts that allow the fork and handlebar to turn smoothly.'],
 ['Wheelset','The front and rear wheels. Check wheel diameter, axle type, and brake compatibility.'],
 ['Tires','The rubber contact surface with the road or trail. Tire size must match the wheel and frame clearance.'],
 ['Crankset','The crank arms and chainrings that transfer your pedaling power to the drivetrain.'],
 ['Bottom Bracket','The bearing assembly that lets the crankset rotate. It must match the frame and crank spindle.'],
 ['Pedals','The parts your feet push. Check the thread standard and riding style.'],
 ['Chain','Transfers power from the chainring to the cassette. Match it to the drivetrain speed.'],
 ['Cassette','The rear gear cluster. Its speed count and freehub fit must match the drivetrain and rear wheel.'],
 ['Shifter','The control used to change gears. It must work with the derailleur and drivetrain system.'],
 ['Derailleur','Moves the chain between gears. Check speed compatibility and capacity.'],
 ['Brakes','Slow and stop the bicycle. The frame, fork, wheels, and brake type must work together.'],
 ['Saddle','The seat you sit on. Shape and width are mostly comfort choices.'],
 ['Seatpost','Connects the saddle to the frame. Its diameter must match the frame seat tube.'],
 ['Grips','The material fitted to the handlebar ends for hand comfort and control.']
];
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><p class="eyebrow">BEGINNER FRIENDLY</p><h1>Bicycle Parts Guide</h1><p>Learn what each component does before choosing products for your build.</p></div></div>
<div class="parts-guide-grid"><?php foreach ($parts as $index=>$part): ?><article class="content-card"><span class="guide-number"><?= sprintf('%02d',$index+1) ?></span><h3><?= h($part[0]) ?></h3><p><?= h($part[1]) ?></p></article><?php endforeach; ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
