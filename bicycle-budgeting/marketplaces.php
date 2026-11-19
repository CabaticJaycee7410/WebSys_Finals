<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Marketplace';
require __DIR__ . '/includes/header.php';
?>
<div class="page-header"><div><p class="eyebrow">LIVE PRODUCT DISCOVERY</p><h1>Marketplace</h1><p>Search live Lazada listings and add ideas to your bicycle plan.</p></div></div>
<section class="content-card">
<form id="marketplaceSearchForm" class="marketplace-search-form">
    <div class="form-group"><label for="marketplaceKeyword">What bicycle part are you looking for?</label><input id="marketplaceKeyword" name="q" placeholder="Example: Shimano hydraulic brakes" required></div>
    <div class="form-grid"><div class="form-group"><label for="marketplaceBrand">Brand filter (optional)</label><input id="marketplaceBrand" name="brand" placeholder="Example: Shimano"></div><div class="form-group"><label for="marketplaceBudget">Maximum price (optional)</label><input id="marketplaceBudget" name="max_budget" type="number" min="0" step="0.01" placeholder="Example: 1000"></div></div>
    <button class="btn" type="submit">Search Live Products</button>
</form>
<p id="marketplaceMessage" class="builder-message" role="status"></p>
<div id="marketplaceResults" class="marketplace-results"></div>
</section>
<script src="<?= h(url('assets/js/marketplace.js')) ?>?v=<?= APP_VERSION ?>"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
