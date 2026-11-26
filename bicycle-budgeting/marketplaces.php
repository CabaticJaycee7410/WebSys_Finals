<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Marketplace';
require __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div>
        <p class="eyebrow">LIVE PRODUCT DISCOVERY</p>
        <h1>Marketplace</h1>
        <p>Search live Lazada listings and add ideas to your bicycle plan.</p>
    </div>
    <button type="button" class="user-guide-trigger" id="startUserGuide">ⓘ How to Use</button>
</div>
<section class="content-card marketplace-page-card">
    <form id="marketplaceSearchForm" class="marketplace-search-form">
        <div class="form-group" data-guide="marketplace-keyword">
            <label for="marketplaceKeyword">What bicycle part are you looking for?</label>
            <input id="marketplaceKeyword" name="q" placeholder="Example: Shimano hydraulic brakes" required>
        </div>
        <div class="form-grid">
            <div class="form-group" data-guide="marketplace-brand">
                <label for="marketplaceBrand">Brand filter (optional)</label>
                <input id="marketplaceBrand" name="brand" placeholder="Example: Shimano">
            </div>
            <div class="form-group" data-guide="marketplace-budget">
                <label for="marketplaceBudget">Maximum price (optional)</label>
                <input id="marketplaceBudget" name="max_budget" type="number" min="0" step="0.01" placeholder="Example: 1000">
            </div>
        </div>
        <button class="btn" type="submit" data-guide="marketplace-search">Search Live Products</button>
    </form>
    <p id="marketplaceMessage" class="builder-message" role="status"></p>
    <div id="marketplaceResults" class="marketplace-results" data-guide="marketplace-results"></div>
</section>
<div class="user-guide-backdrop" id="userGuideBackdrop" hidden></div>
<div class="user-guide-panel" id="userGuidePanel" role="dialog" aria-modal="true" aria-labelledby="userGuideTitle" hidden>
    <div class="user-guide-panel-top">
        <div>
            <p class="user-guide-label" id="userGuideCount">HOW TO USE</p>
            <h3 id="userGuideTitle">Marketplace Guide</h3>
        </div>
        <button type="button" class="user-guide-close" id="userGuideClose" aria-label="Close guide">×</button>
    </div>
    <p id="userGuideText"></p>
    <div class="user-guide-progress">
        <div class="user-guide-progress-fill" id="userGuideProgress"></div>
    </div>
    <div class="user-guide-panel-actions">
        <span class="user-guide-counter" id="userGuideCounter"></span>
        <div class="user-guide-buttons">
            <button type="button" class="user-guide-button" id="userGuidePrevious">Previous</button>
            <button type="button" class="user-guide-button primary" id="userGuideNext">Next</button>
        </div>
    </div>
</div>
<script src="<?= h(url('assets/js/marketplace.js')) ?>?v=<?= APP_VERSION ?>"></script>
<script src="<?= h(url('assets/js/user-guide.js')) ?>?v=<?= APP_VERSION ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>