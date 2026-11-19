document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('marketplaceSearchForm');
    if (!form) return;
    const message = document.getElementById('marketplaceMessage');
    const results = document.getElementById('marketplaceResults');
    const money = value => '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const connectionNote = document.getElementById('marketplaceConnectionNote');
    if (connectionNote) {
        fetch('api/marketplace_status.php', { headers: { Accept: 'application/json' } })
            .then(response => response.json())
            .then(data => {
                connectionNote.textContent = data.message || 'Marketplace status is unavailable.';
                connectionNote.classList.toggle('is-ready', Boolean(data.configured));
                connectionNote.classList.toggle('is-offline', !data.configured);
            })
            .catch(() => {
                connectionNote.textContent = 'Could not check the marketplace connection status.';
                connectionNote.classList.add('is-offline');
            });
    }
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const params = new URLSearchParams();
        params.set('q', document.getElementById('marketplaceKeyword').value.trim());
        const brand = document.getElementById('marketplaceBrand').value.trim();
        const budget = document.getElementById('marketplaceBudget').value.trim();
        if (brand) params.set('brand', brand);
        if (budget) params.set('max_budget', budget);
        message.textContent = 'Searching Lazada...';
        results.replaceChildren();
        try {
            const response = await fetch(`api/marketplace_search.php?${params.toString()}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Search failed.');
            if (!data.products.length) { message.textContent = 'No matching products found.'; return; }
            message.textContent = `${data.count} products found.`;
            data.products.forEach(product => {
                const card = document.createElement('article'); card.className = 'marketplace-product-card';
                if (product.image) { const image = document.createElement('img'); image.src = product.image; image.alt = product.title; image.loading = 'lazy'; card.appendChild(image); }
                const content = document.createElement('div');
                const title = document.createElement('h3'); title.textContent = product.title;
                const price = document.createElement('strong'); price.textContent = money(product.price);
                const seller = document.createElement('p'); seller.textContent = `Brand: ${product.brand} · Seller: ${product.seller_name}`;
                content.append(title, price, seller);
                if (product.url) { const link = document.createElement('a'); link.href = product.url; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.textContent = 'View listing'; content.appendChild(link); }
                card.appendChild(content); results.appendChild(card);
            });
        } catch (error) { message.textContent = error.message; }
    });
});
