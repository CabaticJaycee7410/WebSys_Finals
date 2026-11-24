
<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'User Guide';

$sections = array(
    array(
        'number' => '01',
        'title' => 'Dashboard',
        'description' => 'The Dashboard gives you an overview of your bicycle plans and budgeting activities.',
        'steps' => array(
            'Log in to your account to open the Dashboard.',
            'View your saved build plans and bicycle budget summary.',
            'Check your recorded expenses and savings progress.',
            'Use the navigation menu to open other system functions.'
        )
    ),
    array(
        'number' => '02',
        'title' => 'My Garage',
        'description' => 'My Garage is where you manage your bicycles and their saved build plans.',
        'steps' => array(
            'Open My Garage from the navigation menu.',
            'View your existing bicycle profiles and their saved build plans.',
            'Select Edit Plan to modify an existing build.',
            'Select Delete Plan to remove a build plan. Confirm the deletion when asked.',
            'Use the Bicycle Builder to create another build plan.'
        )
    ),
    array(
        'number' => '03',
        'title' => 'Bicycle Builder',
        'description' => 'Use the Bicycle Builder to create a bicycle plan, select components, and calculate the estimated build cost.',
        'steps' => array(
            'Open Bicycle Builder from the navigation menu.',
            'Choose an existing bicycle or create a new bicycle build.',
            'Select the bicycle type you want to build.',
            'Enter the Bicycle Name and Build Plan Name.',
            'Optionally enter the bicycle brand and model.',
            'Enter your Target Budget.',
            'Click Continue to Bicycle Builder.'
        )
    ),
    array(
        'number' => '04',
        'title' => 'Selecting Bicycle Parts',
        'description' => 'The second step of the builder lets you select components using the interactive 2D bicycle model or component list.',
        'steps' => array(
            'Click a marker on the 2D bicycle model or select a component from the component list.',
            'The system will open the search page for your selected component.',
            'Choose a product keyword if you want to narrow your search.',
            'Select Search by Brand and enter a brand name, or select Search by Budget and choose a maximum price.',
            'Click Search Marketplace Listings.',
            'Review the available product listings and use the sorting options if needed.',
            'Select a product to view its details.'
        )
    ),
    array(
        'number' => '05',
        'title' => 'Confirming Selected Parts',
        'description' => 'Review a product before adding it to your bicycle plan.',
        'steps' => array(
            'Check the product name, details, and listed price.',
            'Review any compatibility warning shown by the system.',
            'If the product is suitable, click Add to My Bike.',
            'If you need another option, select Find Compatible Alternatives.',
            'You may cancel the selection if you do not want to add the product.',
            'Repeat the process for other components.'
        )
    ),
    array(
        'number' => '06',
        'title' => 'Reviewing and Saving a Build',
        'description' => 'The final step displays your selected components and estimated budget.',
        'steps' => array(
            'Click Review Build after selecting your components.',
            'Review your bicycle type and target budget.',
            'Check the selected parts and their estimated prices.',
            'Review the estimated parts total and remaining budget.',
            'Click Save Build Plan to save your new plan.',
            'When editing an existing plan, use the update option to save your changes.'
        )
    ),
    array(
        'number' => '07',
        'title' => 'Parts Catalog',
        'description' => 'The Parts Catalog stores bicycle products and custom parts for your reference.',
        'steps' => array(
            'Open Parts Catalog from the navigation menu.',
            'Use the Add a Custom Part form for a part that is not available in the live marketplace.',
            'Select its component category.',
            'Enter the part name, brand, and estimated price.',
            'You may also enter a product URL.',
            'Click Add Custom Part to save it.',
            'View your saved items in the Saved Parts section.',
            'Click View Listing to open a product link, or Remove to delete an item from your catalog.'
        )
    ),
    array(
        'number' => '08',
        'title' => 'Marketplace',
        'description' => 'Use Marketplace to browse available bicycle products and compare listings.',
        'steps' => array(
            'Open Marketplace from the navigation menu.',
            'Browse the available product listings.',
            'Review product information and displayed prices.',
            'Open a listing to view more information from the seller.',
            'Use the information to compare products before making a purchase decision.'
        )
    ),
    array(
        'number' => '09',
        'title' => 'Expenses',
        'description' => 'Use Expenses to record and monitor your bicycle-related spending.',
        'steps' => array(
            'Open Expenses from the navigation menu.',
            'Use the expense form to enter a new expense.',
            'Provide the requested expense details and amount.',
            'Save the record.',
            'Review your recorded expenses and update or remove entries using the available actions.'
        )
    ),
    array(
        'number' => '10',
        'title' => 'Savings',
        'description' => 'Use Savings to monitor the money you are setting aside for your bicycle plans.',
        'steps' => array(
            'Open Savings from the navigation menu.',
            'View your current savings information.',
            'Use the available form to record or update your savings.',
            'Review your progress toward your savings goal.'
        )
    )
);

require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div>
        <p class="eyebrow">SYSTEM INSTRUCTIONS</p>
        <h1>User Guide</h1>
        <p>Learn how to navigate and use the different features of the Bicycle Budgeting System.</p>
    </div>
</div>

<section class="content-card" style="margin-bottom:24px;">
    <p class="eyebrow">WELCOME</p>
    <h2>Getting Started</h2>
    <p>This guide provides instructions for using the main features of the system. Follow each section to learn how to manage your bicycles, create build plans, browse products, and monitor your budget.</p>
    <ol>
        <li>Log in using your registered account.</li>
        <li>Open the Dashboard to view your system overview.</li>
        <li>Use the navigation menu to access the feature you need.</li>
        <li>Follow the instructions for each feature below.</li>
    </ol>
</section>

<div class="guide-sections">
    <?php foreach ($sections as $section): ?>
        <section class="content-card guide-section" id="guide-<?= h($section['number']) ?>">
            <div class="guide-section-heading">
                <span class="guide-number"><?= h($section['number']) ?></span>
                <div>
                    <h2><?= h($section['title']) ?></h2>
                    <p><?= h($section['description']) ?></p>
                </div>
            </div>
            <ol>
                <?php foreach ($section['steps'] as $step): ?>
                    <li><?= h($step) ?></li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endforeach; ?>
</div>

<section class="content-card" style="margin-top:24px;">
    <p class="eyebrow">IMPORTANT REMINDERS</p>
    <h2>Using the System Properly</h2>
    <ul>
        <li>Review your information before saving changes.</li>
        <li>Check product prices and specifications because marketplace listings may change.</li>
        <li>The Bicycle Builder is for planning and budgeting. It does not automatically purchase products.</li>
        <li>Deleting a build plan may also remove its associated bicycle profile when no other plans remain.</li>
        <li>Keep your account information secure.</li>
    </ul>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
