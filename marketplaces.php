<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$page_title = 'Marketplace Products';

require __DIR__ . '/includes/header.php';
?>

<style>
.marketplace-container {
    max-width: 1100px;
    margin: 25px auto;
    padding: 20px;
}

.marketplace-search {
    display: flex;
    gap: 10px;
    margin: 20px 0;
}

.marketplace-search input {
    flex: 1;
    min-width: 0;
    padding: 13px;
    border: 1px solid #64748b;
    border-radius: 8px;
}

.marketplace-search button {
    padding: 12px 22px;
    border: 0;
    border-radius: 8px;
    cursor: pointer;
    background: #16a085;
    color: white;
    font-weight: bold;
}

.provider-status {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin: 15px 0;
}

.provider-status span {
    padding: 8px 12px;
    border-radius: 7px;
    background: #e2e8f0;
    color: #172033;
    font-size: 13px;
}

.marketplace-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 18px;
    margin-top: 20px;
}

.market-product {
    overflow: hidden;
    border: 1px solid #475569;
    border-radius: 12px;
    background: #142b2b;
    color: white;
}

.market-product img {
    width: 100%;
    height: 180px;
    object-fit: contain;
    background: white;
}

.market-product-content {
    padding: 14px;
}

.market-product h3 {
    font-size: 15px;
    margin: 5px 0 10px;
}

.market-price {
    font-size: 20px;
    font-weight: bold;
    color: #5ee0b5;
}

.market-meta {
    font-size: 12px;
    color: #cbd5e1;
    margin: 7px 0;
}

.market-link {
    display: inline-block;
    margin-top: 10px;
    color: #5ee0b5;
}

#marketplaceMessage {
    margin: 15px 0;
}

@media(max-width: 550px) {
    .marketplace-search {
        flex-direction: column;
    }
}
</style>

<div class="marketplace-container">

    <h1>Marketplace Products</h1>

    <p>
        Search bicycle parts from connected product sources.
        Prices are displayed in Philippine pesos (₱).
    </p>

    <div class="provider-status" id="providerStatus">
        <span>Checking eBay...</span>
        <span>Checking Shopify...</span>
    </div>

    <form class="marketplace-search" id="marketplaceForm">
        <input
            type="search"
            id="marketplaceKeyword"
            placeholder="Search bicycle frame, tires, brakes..."
            maxlength="100"
            required
        >

        <button type="submit">Search Products</button>
    </form>

    <div id="marketplaceMessage" aria-live="polite">
        Enter a bicycle part to search.
    </div>

    <div class="marketplace-grid" id="marketplaceResults"></div>

</div>

<script src="<?= h(url('assets/js/marketplace.js')) ?>"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>