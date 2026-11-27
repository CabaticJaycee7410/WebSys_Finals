
<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Marketplace';
require __DIR__ . '/includes/header.php';
?>

<style>
.marketplace-saved-sections{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:18px}
.marketplace-saved-card{min-width:0}
.marketplace-saved-card h2{font-size:18px;margin:0 0 12px}
.marketplace-saved-list{display:flex;flex-direction:column;gap:9px}
.marketplace-saved-item{display:flex;align-items:center;gap:11px;padding:11px;background:rgba(10,43,64,.5);border:1px solid rgba(120,205,220,.2);border-radius:9px;min-width:0}
.marketplace-saved-item img{width:65px;height:65px;object-fit:contain;background:#fff;border-radius:7px;flex-shrink:0}
.marketplace-saved-item-info{min-width:0;flex:1}
.marketplace-saved-item-info strong{display:block;font-size:12px;color:#e0f5f8;overflow-wrap:anywhere}
.marketplace-saved-item-info p{font-size:10px;color:#a9cbd6;margin:4px 0}
.marketplace-saved-item-actions{display:flex;gap:7px;align-items:center;flex-wrap:wrap;margin-top:7px}
.marketplace-saved-item-actions a,.marketplace-saved-item-actions button{font-size:10px}
.marketplace-saved-remove,.marketplace-action-button{border:1px solid rgba(120,205,220,.3);border-radius:7px;background:rgba(35,101,122,.4);color:#d5f1f4;padding:8px 10px;cursor:pointer;font-size:11px}
.marketplace-saved-remove{border-color:rgba(220,83,100,.35);background:rgba(220,53,69,.15);color:#ffb9c1}
.marketplace-saved-remove:hover{background:rgba(185,54,74,.7);color:#ffe9ec}
.marketplace-product-plan{display:flex;flex-direction:column;gap:5px;margin-top:10px}
.marketplace-product-plan label{font-size:10px;color:#a9cbd6}
.marketplace-product-plan select{width:100%;max-width:350px;padding:9px;border-radius:7px}
.marketplace-product-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.marketplace-action-button.is-saved{border-color:#45d4a3;background:rgba(20,78,76,.55);color:#8ff0c1}
.marketplace-saved-empty{padding:13px;background:rgba(10,43,64,.35);border:1px dashed rgba(120,205,220,.25);border-radius:8px;color:#a9cbd6;font-size:11px}
.marketplace-saved-status{font-size:11px;color:#a9cbd6;margin-top:8px}
@media(max-width:750px){.marketplace-saved-sections{grid-template-columns:1fr}}
@media(max-width:480px){.marketplace-saved-item{align-items:flex-start;flex-wrap:wrap}.marketplace-saved-item-actions{width:100%}}
</style>

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
        <input type="hidden" id="marketplaceCsrfToken" value="<?= h(csrf_token()) ?>">
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

<div class="marketplace-saved-sections">
    <section class="content-card marketplace-saved-card">
        <h2>♡ My Wishlist</h2>
        <div id="marketplaceWishlist" class="marketplace-saved-list">
            <p class="marketplace-saved-empty">Loading wishlist...</p>
        </div>
    </section>
    <section class="content-card marketplace-saved-card">
        <h2>★ My Favorites</h2>
        <div id="marketplaceFavorites" class="marketplace-saved-list">
            <p class="marketplace-saved-empty">Loading favorites...</p>
        </div>
    </section>
</div>
<p id="marketplaceSavedMessage" class="marketplace-saved-status" role="status"></p>

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
