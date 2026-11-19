<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Bicycle Builder';
$pdo = require __DIR__ . '/../config/database.php';
$userId = current_user_id();
$projectId = max(0, (int)($_GET['project_id'] ?? 0));
$project = null;
$initialParts = [];

if ($projectId > 0) {
    $stmt = $pdo->prepare("SELECT p.*, bp.bicycle_type AS plan_bicycle_type, bp.bicycle_brand, bp.bicycle_model, bp.build_name, b.bicycle_type AS saved_bicycle_type, b.brand AS saved_bicycle_brand FROM projects p LEFT JOIN builder_plans bp ON bp.project_id = p.project_id LEFT JOIN bicycles b ON b.bicycle_id = p.bicycle_id AND b.user_id = p.user_id WHERE p.project_id = ? AND p.user_id = ? LIMIT 1");
    $stmt->execute([$projectId, $userId]);
    $project = $stmt->fetch();

    if (!$project) {
        set_flash('error', 'Build plan not found.');
        redirect('projects.php');
    }

    $stmt = $pdo->prepare('SELECT * FROM project_build_parts WHERE project_id = ? ORDER BY component_label');
    $stmt->execute([$projectId]);

    foreach ($stmt->fetchAll() as $part) {
        $compatibility = json_decode((string)$part['compatibility_json'], true);
        $initialParts[$part['component_key']] = [
            'component_key' => $part['component_key'],
            'component_label' => $part['component_label'],
            'title' => $part['product_title'],
            'brand' => $part['brand'],
            'price' => (float)$part['price'],
            'image' => $part['image_url'],
            'url' => $part['product_url'],
            'source' => $part['source'],
            'item_id' => $part['provider_item_id'],
            'sku_id' => $part['provider_sku_id'],
            'compatibility' => is_array($compatibility) ? $compatibility : [],
            'compatibility_status' => $part['compatibility_status'],
            'forced_incompatible' => (bool)$part['forced_incompatible']
        ];
    }
}

$components = [
    'frame' => 'Frame',
    'fork' => 'Fork',
    'handlebar' => 'Handlebar',
    'stem' => 'Stem',
    'headset' => 'Headset',
    'grips' => 'Grips',
    'saddle' => 'Saddle',
    'seatpost' => 'Seatpost',
    'wheelset' => 'Wheelset',
    'front_tire' => 'Front Tire',
    'rear_tire' => 'Rear Tire',
    'crankset' => 'Crankset',
    'bottom_bracket' => 'Bottom Bracket',
    'pedals' => 'Pedals',
    'chain' => 'Chain',
    'cassette' => 'Cassette / Rear Cog',
    'shifter' => 'Shifter',
    'front_derailleur' => 'Front Derailleur',
    'rear_derailleur' => 'Rear Derailleur',
    'front_brake' => 'Front Brake',
    'rear_brake' => 'Rear Brake',
    'motor' => 'Electric Motor',
    'battery' => 'Battery',
    'controller' => 'Controller / Display',
    'cargo_rack' => 'Cargo Rack'
];

$markers = [
    'frame' => [315, 190], 'fork' => [475, 190], 'handlebar' => [565, 91],
    'stem' => [455, 119], 'headset' => [428, 142], 'grips' => [625, 105],
    'saddle' => [225, 100], 'seatpost' => [245, 150], 'wheelset' => [545, 300],
    'front_tire' => [565, 205], 'rear_tire' => [135, 205], 'crankset' => [315, 260],
    'bottom_bracket' => [270, 242], 'pedals' => [360, 295], 'chain' => [390, 275],
    'cassette' => [175, 278], 'shifter' => [600, 125], 'front_derailleur' => [350, 235],
    'rear_derailleur' => [185, 310], 'front_brake' => [535, 185], 'rear_brake' => [165, 185]
];

$bikeTypes = [
    'Mountain Bike', 'BMX', 'Road Bike', 'Gravel Bike', 'Hybrid Bike', 'Commuter Bike',
    'City Bike', 'Touring Bike', 'Folding Bike', 'Fat Bike', 'Track / Fixed Gear',
    'Cyclocross Bike', 'Downhill Bike', 'Enduro Bike', 'Cross-Country Bike',
    'Electric Bike', 'Cargo Bike', 'Tandem Bike', 'Recumbent Bike', "Kids' Bike", 'Custom Build'
];

$optionalComponents = ['motor', 'battery', 'controller', 'cargo_rack'];
$baseComponents = array_values(array_diff(array_keys($components), $optionalComponents));
$singleSpeedComponents = array_values(array_diff($baseComponents, [
    'cassette', 'shifter', 'front_derailleur', 'rear_derailleur'
]));
$bikeComponentMap = [];
foreach ($bikeTypes as $type) {
    $bikeComponentMap[$type] = $baseComponents;
}
$basicCityComponents = array_values(array_diff($baseComponents, ['front_derailleur']));
$downhillComponents = array_values(array_diff($baseComponents, ['front_derailleur']));
$kidsComponents = array_values(array_diff($baseComponents, ['front_derailleur', 'rear_derailleur']));
$bikeComponentMap['BMX'] = $singleSpeedComponents;
$bikeComponentMap['Track / Fixed Gear'] = $singleSpeedComponents;
$bikeComponentMap['City Bike'] = $basicCityComponents;
$bikeComponentMap['Commuter Bike'] = $basicCityComponents;
$bikeComponentMap['Downhill Bike'] = $downhillComponents;
$bikeComponentMap['Enduro Bike'] = $downhillComponents;
$bikeComponentMap["Kids' Bike"] = $kidsComponents;
$bikeComponentMap['Electric Bike'] = array_merge($baseComponents, ['motor', 'battery', 'controller']);
$bikeComponentMap['Cargo Bike'] = array_merge($baseComponents, ['cargo_rack']);
$bikeComponentMap['Touring Bike'] = array_merge($baseComponents, ['cargo_rack']);
$bikeComponentMap['Custom Build'] = array_keys($components);

$initial = [
    'projectId' => $projectId,
    'projectName' => $project['build_name'] ?? $project['project_name'] ?? '',
    'budget' => (float)($project['budget_amount'] ?? 0),
    'bikeType' => $project['plan_bicycle_type'] ?? $project['saved_bicycle_type'] ?? '',
    'bikeBrand' => $project['bicycle_brand'] ?? $project['saved_bicycle_brand'] ?? '',
    'bikeModel' => $project['bicycle_model'] ?? '',
    'parts' => $initialParts
];

require __DIR__ . '/../includes/header.php';
?>
<div class="builder-wizard" id="builderWizard">
    <div class="builder-wizard-heading">
        <div>
            <p class="eyebrow">BICYCLE PLANNING WORKSPACE</p>
            <h1><?= $projectId ? 'Edit Bicycle Plan' : 'Build Your Bicycle' ?></h1>
            <p>Choose a bicycle type, select its components, compare listings, and save your plan.</p>
        </div>
        <div class="builder-heading-actions">
            <span class="builder-autosave-indicator" id="draftSaveStatus">Draft saved locally</span>
            <a class="builder-exit-link" href="<?= h(url('dashboard.php')) ?>">Back to Dashboard</a>
        </div>
    </div>

    <div class="builder-progress">
        <div class="builder-progress-step active" data-progress="type"><span>1</span> Bike</div>
        <div class="builder-progress-step" data-progress="model"><span>2</span> Build</div>
        <div class="builder-progress-step" data-progress="search"><span>3</span> Find Parts</div>
        <div class="builder-progress-step" data-progress="confirm"><span>4</span> Confirm</div>
        <div class="builder-progress-step" data-progress="review"><span>5</span> Save</div>
    </div>

    <section class="builder-screen active" id="step-type" data-step="type">
        <div class="builder-section-heading">
            <div>
                <p class="builder-eyebrow">STEP 1 OF 5</p>
                <h2>What bicycle do you want to build?</h2>
                <p>Choose the bicycle type that matches your planned build.</p>
            </div>
        </div>

        <div class="bike-type-grid" id="bikeTypeGrid">
            <?php foreach ($bikeTypes as $type): ?>
                <button type="button" class="bike-type-card" data-bike-type="<?= h($type) ?>">
                    <span class="bike-type-icon">🚲</span>
                    <strong><?= h($type) ?></strong>
                    <span class="bike-type-check">✓</span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="builder-details-grid">
            <label>Build Plan Name<input type="text" id="buildName" maxlength="150" placeholder="Example: My First Mountain Bike"></label>
            <label>Bicycle Brand (Optional)<input type="text" id="bikeBrand" maxlength="120" list="bikeBrandOptions" placeholder="Trek, Giant, Specialized"><datalist id="bikeBrandOptions"><option value="Trek"><option value="Giant"><option value="Specialized"><option value="Merida"><option value="Cannondale"><option value="Scott"><option value="Marin"><option value="Canyon"><option value="Polygon"><option value="Santa Cruz"></datalist></label>
            <label>Bicycle Model (Optional)<input type="text" id="bikeModel" maxlength="120" placeholder="Example: Marlin 5"></label>
            <label>Target Budget (₱)<input type="number" id="targetBudget" min="0" step="0.01" placeholder="Example: 15000"></label>
        </div>

        <div class="builder-step-actions">
            <span class="builder-note">You can edit these details later. This is a planning tool, not a checkout page.</span>
            <button type="button" class="builder-primary-button" id="toBikeModel">Continue to Bicycle Builder</button>
        </div>
        <p class="builder-message" id="typeMessage" role="status"></p>
    </section>

    <section class="builder-screen" id="step-model" data-step="model" hidden>
        <div class="builder-section-heading builder-model-heading">
            <div>
                <p class="builder-eyebrow">STEP 2 OF 5</p>
                <h2 id="modelTitle">Build your bicycle</h2>
                <p>Click a component marker on the 2D bicycle or choose from the component list.</p>
            </div>
            <div class="builder-type-chip" id="selectedBikeType"></div>
        </div>

        <div class="builder-live-summary">
            <div><span>Selected Parts</span><strong id="modelPartCount">0</strong></div>
            <div><span>Estimated Build Cost</span><strong id="modelPartTotal">₱0.00</strong></div>
            <div><span>Target Budget</span><strong id="modelTargetBudget">Not set</strong></div>
        </div>

        <div class="builder-workspace">
            <div class="builder-bike-panel">
                <div class="builder-panel-heading">
                    <h3 id="bikeDiagramLabel">Your 2D Bicycle</h3>
                    <span class="builder-hint">Green markers show selected components. Amber markers need a fit check.</span>
                </div>

                <div class="interactive-bike-canvas">
                    <svg id="interactiveBikeSvg" viewBox="0 0 700 390" class="bike-svg interactive-bike-svg" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Interactive bicycle model">
                        <defs>
                            <linearGradient id="framePaint" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#25a76b"/><stop offset="1" stop-color="#11613d"/></linearGradient>
                        </defs>
                        <g data-component="front_tire"><circle id="frontTireShape" cx="545" cy="255" r="91" class="bike-wheel-outer"/></g>
                        <g data-component="rear_tire"><circle id="rearTireShape" cx="155" cy="255" r="91" class="bike-wheel-outer"/></g>
                        <g data-component="wheelset">
                            <circle cx="155" cy="255" r="83" class="bike-wheel-inner"/><circle cx="545" cy="255" r="83" class="bike-wheel-inner"/>
                            <circle cx="155" cy="255" r="5" class="bike-hub"/><circle cx="545" cy="255" r="5" class="bike-hub"/>
                            <g class="bike-spokes"><path d="M155 164 V346 M64 255 H246 M91 191 L219 319 M91 319 L219 191"/><path d="M545 164 V346 M454 255 H636 M481 191 L609 319 M481 319 L609 191"/></g>
                        </g>
                        <g data-component="frame" class="bike-component-drawing">
                            <path id="bikeFrameShape" d="M155 255 L285 255 L235 125 L155 255 M285 255 L390 125 L545 255 L285 255 M235 125 L390 125" class="bike-frame-line"/><circle id="bikeHinge" cx="320" cy="190" r="11" class="bike-hinge" style="display:none"/>
                        </g>
                        <g data-component="fork" class="bike-component-drawing"><path id="bikeForkShape" d="M390 125 L545 255" class="bike-fork-line"/></g>
                        <g data-component="stem" class="bike-component-drawing"><path id="bikeStemShape" d="M390 125 L420 89 L455 89" class="bike-stem-line"/></g>
                        <g data-component="handlebar" class="bike-component-drawing"><path id="bikeHandlebarShape" d="M455 89 L610 108" class="bike-handlebar-line"/></g>
                        <g data-component="headset" class="bike-component-drawing"><circle cx="390" cy="125" r="10" class="bike-detail-shape"/></g>
                        <g data-component="grips" class="bike-component-drawing"><path d="M455 89 L443 87 M610 108 L623 110" class="bike-detail-line"/></g>
                        <g data-component="seatpost" class="bike-component-drawing"><path d="M235 125 L235 102" class="bike-seatpost-line"/></g>
                        <g data-component="saddle" class="bike-component-drawing"><path d="M195 102 L260 102" class="bike-saddle-line"/></g>
                        <g data-component="bottom_bracket" class="bike-component-drawing"><circle cx="285" cy="255" r="12" class="bike-detail-shape"/></g>
                        <g data-component="crankset" class="bike-component-drawing"><circle cx="285" cy="255" r="19" class="bike-crank-ring"/><path d="M285 255 L328 292" class="bike-crank-arm"/></g>
                        <g data-component="pedals" class="bike-component-drawing"><path d="M320 292 L343 292" class="bike-detail-line"/></g>
                        <g data-component="chain" class="bike-component-drawing"><path d="M315 255 L155 255" class="bike-chain-line"/></g>
                        <g data-component="cassette" class="bike-component-drawing"><circle cx="155" cy="255" r="18" class="bike-detail-shape"/></g>
                        <g data-component="shifter" class="bike-component-drawing"><rect x="585" y="94" width="13" height="8" rx="3" class="bike-detail-shape"/></g>
                        <g data-component="front_derailleur" class="bike-component-drawing"><path d="M325 245 L345 240 L350 255" class="bike-derailleur-line"/></g>
                        <g data-component="rear_derailleur" class="bike-component-drawing"><path d="M155 255 L175 281 L160 300" class="bike-derailleur-line"/></g>
                        <g data-component="front_brake" class="bike-component-drawing"><path d="M535 181 L550 181" class="bike-brake-line"/></g>
                        <g data-component="rear_brake" class="bike-component-drawing"><path d="M145 181 L160 181" class="bike-brake-line"/></g>
                        <g data-component="motor" class="bike-component-drawing"><circle cx="285" cy="225" r="20" class="bike-detail-shape"/></g>
                        <g data-component="battery" class="bike-component-drawing"><rect x="305" y="150" width="26" height="75" rx="6" class="bike-detail-shape"/></g>
                        <g data-component="controller" class="bike-component-drawing"><rect x="590" y="78" width="18" height="10" rx="3" class="bike-detail-shape"/></g>
                        <g data-component="cargo_rack" class="bike-component-drawing"><path d="M92 145 L210 145 L220 160 M110 145 L110 175" class="bike-detail-line"/></g>
                        <?php foreach ($markers as $key => $position): ?>
                            <g class="bike-part-marker" data-component="<?= h($key) ?>" tabindex="0" role="button" aria-label="Choose <?= h($components[$key]) ?>" transform="translate(<?= (int)$position[0] ?> <?= (int)$position[1] ?>)">
                                <circle class="bike-marker-circle" cx="0" cy="0" r="13"/>
                                <text class="bike-marker-text" x="0" y="4"><?= h(strtoupper(substr($components[$key], 0, 1))) ?></text>
                                <circle class="bike-marker-warning" cx="12" cy="-12" r="7"/>
                                <text class="bike-marker-warning-text" x="12" y="-9">!</text>
                                <title><?= h($components[$key]) ?></title>
                            </g>
                        <?php endforeach; ?>
                    </svg>
                </div>

                <div class="builder-model-legend">
                    <span><i class="legend-dot legend-selected"></i> Part selected</span>
                    <span><i class="legend-dot legend-empty"></i> Not selected</span>
                    <span><i class="legend-dot legend-warning"></i> Check fit</span>
                </div>

                <div class="component-list-heading">
                    <h3 class="component-list-title">Components for this bicycle</h3>
                    <span id="componentTypeNote">The list changes based on bicycle type.</span>
                </div>
                <div class="component-list builder-component-list" id="builderComponentList">
                    <?php foreach ($components as $key => $label): ?>
                        <button type="button" class="component-button" data-component="<?= h($key) ?>">
                            <span><?= h($label) ?></span>
                            <small data-component-status="<?= h($key) ?>">Choose</small>
                        </button>
                    <?php endforeach; ?>
                </div>
                <p class="builder-message" id="modelMessage" role="status"></p>
            </div>

            <aside class="builder-side-panel">
                <h3>Your Current Build</h3>
                <p class="builder-hint">Your selected parts and their planned prices appear here.</p>
                <div id="modelSelectedParts" class="model-selected-parts"></div>
                <div class="builder-side-total"><span>Current Total</span><strong id="modelSideTotal">₱0.00</strong></div>
                <button type="button" class="builder-primary-button builder-full-button" id="toReviewFromModel">Review Build</button>
                <button type="button" class="builder-secondary-button builder-full-button" id="backToBikeType">Back to Bike Details</button>
            </aside>
        </div>
    </section>

    <section class="builder-screen" id="step-search" data-step="search" hidden>
        <div class="builder-section-heading">
            <div><p class="builder-eyebrow">STEP 3 OF 5</p><h2>Find Your <span id="searchComponentTitle">Bicycle Part</span></h2><p>Search marketplace listings, then compare the item and its listed price. The website does not purchase items.</p></div>
            <button type="button" class="builder-secondary-button" id="backToModelFromSearch">← Back to 2D Bike</button>
        </div>

        <div class="selected-component-box builder-search-selected"><span>Selected Component</span><strong id="searchSelectedComponent">None selected</strong></div>
        <p class="marketplace-connection-note" id="marketplaceConnectionNote" role="status">Checking marketplace connection...</p>

        <form id="builderSearchForm" class="builder-live-search-form">
            <label for="productKeyword">Product Keyword (Optional)</label>
            <input type="text" id="productKeyword" placeholder="Example: Aluminum, Hydraulic, 31.8mm">
            <div class="builder-filter-heading"><strong>How do you want to find this part?</strong><span>Choose one filter.</span></div>
            <div class="builder-filter-tabs">
                <button type="button" class="builder-filter-tab active" data-filter-mode="brand">Search by Brand</button>
                <button type="button" class="builder-filter-tab" data-filter-mode="budget">Search by Budget</button>
            </div>
            <div id="brandFilterPanel" class="builder-filter-panel">
                <label for="productBrand">Brand Name</label>
                <input type="text" id="productBrand" list="productBrandOptions" placeholder="Example: Shimano, SRAM, Prowheel">
                <datalist id="productBrandOptions"><option value="Shimano"><option value="SRAM"><option value="Prowheel"><option value="RaceFace"><option value="Truvativ"><option value="Maxxis"><option value="Schwalbe"><option value="Continental"><option value="Kenda"><option value="RockShox"><option value="Fox"><option value="Zoom"></datalist>
                <p class="builder-hint">Type a brand name or choose a suggestion.</p>
            </div>
            <div id="budgetFilterPanel" class="builder-filter-panel" hidden>
                <label for="budgetPreset">Maximum Price</label>
                <select id="budgetPreset">
                    <option value="300">Below ₱300</option><option value="500" selected>Below ₱500</option>
                    <option value="1000">Below ₱1,000</option><option value="2000">Below ₱2,000</option>
                    <option value="5000">Below ₱5,000</option><option value="custom">Custom Budget</option><option value="any">Any Price</option>
                </select>
                <label for="customBudget" id="customBudgetLabel" hidden>Enter Maximum Price (₱)</label>
                <input type="number" id="customBudget" min="1" step="0.01" placeholder="Example: 750" hidden>
            </div>
            <button type="submit" class="builder-primary-button">Search Marketplace Listings</button>
        </form>

        <div class="compatibility-filter-banner" id="compatibilityFilterBanner" hidden>
            <span id="compatibilityFilterMessage"></span>
            <label><input type="checkbox" id="showAllSizesAnyway"> Show all results anyway</label>
        </div>

        <div class="builder-results-toolbar" id="builderResultsToolbar" hidden>
            <label for="productSort">Sort results
                <select id="productSort">
                    <option value="relevance">Marketplace order</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="rating">Highest Rating</option>
                </select>
            </label>
            <button type="button" class="builder-secondary-button" id="loadMoreResults" hidden>Load More Results</button>
        </div>

        <div id="builderMessage" class="builder-search-status" role="status" aria-live="polite"></div>
        <div id="builderResults" class="builder-product-results"></div>
    </section>

    <section class="builder-screen" id="step-confirm" data-step="confirm" hidden>
        <div class="builder-section-heading">
            <div><p class="builder-eyebrow">STEP 4 OF 5</p><h2>Confirm Your Selected Part</h2><p>Review this listing before adding its estimated price to your plan.</p></div>
        </div>
        <div id="confirmProductCard" class="confirm-product-card"></div>
        <div id="compatibilityWarning" class="compatibility-warning" hidden>
            <h3>⚠️ Let's Check That Fit First!</h3>
            <p>This part may not match something already selected on your bicycle.</p>
            <ul id="compatibilityWarningList"></ul>
            <button type="button" class="builder-secondary-button" id="findCompatibleParts">Find Compatible Alternatives</button>
            <button type="button" class="builder-warning-button" id="addAnyway">Add Anyway — Mark for Fit Review</button>
        </div>
        <div id="compatibilityReviewNote" class="compatibility-review-note" hidden><strong>Specifications Not Verified</strong><p>We couldn't verify all fit details for this listing. Check the product specifications before adding it.</p></div>
        <div class="builder-step-actions">
            <button type="button" class="builder-secondary-button" id="cancelProductSelection">Cancel</button>
            <button type="button" class="builder-primary-button" id="confirmAddProduct">Add to My Bike</button>
        </div>
    </section>

    <section class="builder-screen" id="step-review" data-step="review" hidden>
        <div class="builder-section-heading">
            <div><p class="builder-eyebrow">STEP 5 OF 5</p><h2>Review Your Bicycle Plan</h2><p>Check your selected products and estimated total before saving.</p></div>
            <button type="button" class="builder-secondary-button" id="backToModelFromReview">← Continue Building</button>
        </div>
        <div class="builder-review-summary">
            <div><span>Bicycle Type</span><strong id="reviewBikeType">Not selected</strong></div>
            <div><span>Target Budget</span><strong id="reviewBudget">₱0.00</strong></div>
            <div><span>Estimated Parts Total</span><strong id="reviewTotal">₱0.00</strong></div>
            <div><span>Remaining Budget</span><strong id="reviewRemaining">₱0.00</strong></div>
        </div>
        <div class="builder-review-heading"><h3>Selected Bicycle Parts</h3><span id="reviewPartCount">0 parts</span></div>
        <div id="reviewPartsList" class="builder-review-parts"></div>
        <p id="reviewBudgetMessage" class="builder-budget-message"></p>
        <p class="builder-message" id="savePlanMessage" role="status"></p>
        <div class="builder-step-actions builder-save-actions">
            <button type="button" class="builder-secondary-button" id="deletePlanButton" hidden>Delete This Plan</button>
            <button type="button" class="builder-primary-button" id="savePlanButton">Save Build Plan</button>
        </div>
    </section>
</div>

<script>
window.BICYCLE_BUILDER = <?= json_encode([
    'projectId' => $projectId,
    'userId' => $userId,
    'csrfToken' => csrf_token(),
    'dashboardUrl' => url('dashboard.php'),
    'components' => $components,
    'bikeTypes' => $bikeTypes,
    'bikeComponentMap' => $bikeComponentMap,
    'initial' => $initial
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= h(url('assets/js/builder.js')) ?>?v=<?= APP_VERSION ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
