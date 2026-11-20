
document.addEventListener('DOMContentLoaded', () => {
    const config = window.BICYCLE_BUILDER || {};
    const initial = config.initial || {};
    const components = config.components || {};
    const componentMap = config.bikeComponentMap || {};
    const $ = id => document.getElementById(id);
    const screens = [...document.querySelectorAll('.builder-screen')];
    const draftKey = `bicycle-builder-draft:${config.userId || 'user'}:${config.projectId || 'new'}`;
    const money = value => '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const allowedComponents = (type = bikeType()) => componentMap[type] || Object.keys(components);
    let restoredDraft = null;

    try {
        restoredDraft = JSON.parse(localStorage.getItem(draftKey) || 'null');
    } catch (_) {}

    const draft = { ...(initial.parts || {}), ...((restoredDraft && restoredDraft.parts) || {}) };
    let selectedBicycleId = Number(restoredDraft && restoredDraft.bicycleId ? restoredDraft.bicycleId : initial.bicycleId || 0);
    let activeComponent = '';
    let pendingProduct = null;
    let compatibilityResult = null;
    let filterMode = 'brand';
    let compatibilityFilters = {};
    let showAllResults = false;
    let searchPage = 1;
    let searchParams = null;
    let searchProducts = [];
    let isLoadingMore = false;

    function bikeType() {
        return document.querySelector('.bike-type-card.selected')?.dataset.bikeType || initial.bikeType || '';
    }

    function safeUrl(value) {
        try {
            const url = new URL(value, window.location.href);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
        } catch (_) {
            return '';
        }
    }

    function jsonCompatibility(part) {
        let value = part && part.compatibility ? part.compatibility : {};
        if (typeof value === 'string') {
            try {
                value = JSON.parse(value);
            } catch (_) {
                value = {};
            }
        }
        return value && typeof value === 'object' ? value : {};
    }

    function meta(part, field) {
        const value = jsonCompatibility(part)[field];
        return value === undefined || value === null || value === '' ? null : value;
    }

    function setText(id, value) {
        const element = $(id);
        if (element) element.textContent = value;
    }

    function saveDraftLocally() {
        const status = $('draftSaveStatus');
        try {
            localStorage.setItem(draftKey, JSON.stringify({
                projectId: Number(config.projectId || 0),
                bicycleId: selectedBicycleId,
                bicycleName: $('bicycleName').value,
                bikeType: bikeType(),
                buildName: $('buildName').value,
                bikeBrand: $('bikeBrand').value,
                bikeModel: $('bikeModel').value,
                budget: $('targetBudget').value,
                parts: draft,
                savedAt: new Date().toISOString()
            }));
            if (status) status.textContent = 'Draft saved on this device';
        } catch (_) {
            if (status) status.textContent = 'Local draft storage is unavailable';
        }
    }

    function showStep(step) {
        screens.forEach(screen => {
            const active = screen.dataset.step === step;
            screen.hidden = !active;
            screen.classList.toggle('active', active);
        });

        const order = ['type', 'model', 'search', 'confirm', 'review'];
        document.querySelectorAll('[data-progress]').forEach(item => {
            const index = order.indexOf(item.dataset.progress);
            const current = order.indexOf(step);
            item.classList.toggle('active', index === current);
            item.classList.toggle('completed', index < current);
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function applyBikeAppearance(type) {
        const svg = $('interactiveBikeSvg');
        if (!svg) return;

        const normalized = String(type || '').toLowerCase();
        const frame = $('bikeFrameShape');
        const handlebar = $('bikeHandlebarShape');
        const fork = $('bikeForkShape');
        const tires = [$('frontTireShape'), $('rearTireShape')];
        const hinge = $('bikeHinge');

        let framePath = 'M155 255 L285 255 L235 125 L155 255 M285 255 L390 125 L545 255 L285 255 M235 125 L390 125';
        let handlebarPath = 'M455 89 L610 108';
        let forkPath = 'M390 125 L545 255';
        let tireRadius = 91;
        let tireWidth = 10;

        if (normalized.includes('bmx')) {
            framePath = 'M155 255 L300 255 L255 155 L155 255 M300 255 L395 155 L545 255 L300 255 M255 155 L395 155';
            handlebarPath = 'M455 115 L455 78 L510 78 M510 78 L540 92';
            forkPath = 'M395 155 L545 255';
            tireRadius = 86;
            tireWidth = 12;
        } else if (normalized.includes('road') || normalized.includes('cyclocross') || normalized.includes('gravel')) {
            framePath = 'M155 255 L292 255 L242 125 L155 255 M292 255 L390 125 L545 255 L292 255 M242 125 L390 125';
            handlebarPath = 'M455 89 L600 100 Q630 103 623 126 L605 145 L585 133';
            forkPath = 'M390 125 Q450 185 545 255';
            tireRadius = 88;
            tireWidth = normalized.includes('gravel') || normalized.includes('cyclocross') ? 12 : 8;
        } else if (normalized.includes('fat bike')) {
            tireRadius = 96;
            tireWidth = 21;
        } else if (normalized.includes('mountain') || normalized.includes('downhill') || normalized.includes('enduro') || normalized.includes('cross-country')) {
            framePath = 'M155 255 L300 255 L245 145 L155 255 M300 255 L390 125 L545 255 L300 255 M245 145 L390 125';
            handlebarPath = 'M455 89 L610 95';
            forkPath = 'M390 125 Q445 190 545 255';
            tireRadius = 94;
            tireWidth = 15;
        }

        if (frame) frame.setAttribute('d', framePath);
        if (handlebar) handlebar.setAttribute('d', handlebarPath);
        if (fork) fork.setAttribute('d', forkPath);

        tires.forEach(tire => {
            if (!tire) return;
            tire.setAttribute('r', String(tireRadius));
            tire.style.strokeWidth = `${tireWidth}px`;
        });

        if (hinge) hinge.style.display = normalized.includes('folding') ? 'block' : 'none';
        svg.dataset.bikeType = type;
    }

    function setBikeType(type) {
        document.querySelectorAll('.bike-type-card').forEach(card => {
            card.classList.toggle('selected', card.dataset.bikeType === type);
        });

        if (!type) return;

        setText('modelTitle', `Build your ${type}`);
        setText('selectedBikeType', type);
        setText('bikeDiagramLabel', `${type} 2D Model`);
        setText('componentTypeNote', type === 'BMX' || type === 'Track / Fixed Gear'
            ? 'Single-speed component options are shown for this bicycle type.'
            : 'The list changes based on bicycle type.');

        const allowed = new Set(allowedComponents(type));

        document.querySelectorAll('.component-button, .bike-part-marker, .bike-component-drawing, .interactive-bike-svg > g[data-component]').forEach(element => {
            element.hidden = !allowed.has(element.dataset.component);
        });

        applyBikeAppearance(type);
        updateSummary();
        saveDraftLocally();
    }

    function budgetValue() {
        const value = Number($('targetBudget').value || 0);
        return Number.isFinite(value) && value > 0 ? value : 0;
    }

    function totalCost() {
        return Object.values(draft).reduce((sum, part) => sum + Number(part.price || 0), 0);
    }

    const compatibilityGroups = [
        { field: 'wheel_size', slots: ['frame', 'fork', 'wheelset', 'front_tire', 'rear_tire'] },
        { field: 'brake_mount', slots: ['frame', 'fork', 'wheelset', 'front_brake', 'rear_brake'] },
        { field: 'speed_count', slots: ['shifter', 'rear_derailleur', 'cassette', 'chain'] },
        { field: 'clamp_diameter', slots: ['handlebar', 'stem'] },
        { field: 'seatpost_diameter', slots: ['frame', 'seatpost'] },
        { field: 'axle_type', slots: ['fork', 'frame', 'wheelset'] },
        { field: 'hub_spacing', slots: ['frame', 'fork', 'wheelset'] },
        { field: 'bottom_bracket_type', slots: ['frame', 'bottom_bracket'] },
        { field: 'freehub_type', slots: ['wheelset', 'cassette'] }
    ];

    function checkCompatibility(candidate, slot, current) {
        const warnings = [];
        const candidateMeta = jsonCompatibility(candidate);
        let unverified = Object.keys(candidateMeta).length === 0;

        compatibilityGroups.forEach(group => {
            if (!group.slots.includes(slot)) return;

            const value = meta(candidate, group.field);

            group.slots.forEach(otherSlot => {
                if (otherSlot === slot || !current[otherSlot]) return;

                const existingValue = meta(current[otherSlot], group.field);

                if (value === null || existingValue === null) {
                    unverified = true;
                    return;
                }

                if (String(value).trim().toLowerCase() === String(existingValue).trim().toLowerCase()) return;

                const newLabel = components[slot] || slot;
                const oldLabel = components[otherSlot] || otherSlot;
                let message = `Fit check: This ${newLabel} has a different ${group.field.replaceAll('_', ' ')} value from your ${oldLabel}.`;

                const messages = {
                    wheel_size: `Wheel-size mismatch: This ${newLabel} is ${candidateMeta.wheel_size_label || value}, but your ${oldLabel} is ${jsonCompatibility(current[otherSlot]).wheel_size_label || existingValue}.`,
                    brake_mount: `Brake fit mismatch: This ${newLabel} uses ${value}, but your ${oldLabel} uses ${existingValue}.`,
                    speed_count: `Gear mismatch: This ${newLabel} is ${value}-speed, but your ${oldLabel} is ${existingValue}-speed.`,
                    clamp_diameter: `Handlebar fit mismatch: The ${newLabel} uses ${value}mm, but your ${oldLabel} uses ${existingValue}mm.`,
                    seatpost_diameter: `Seatpost fit mismatch: This ${newLabel} is ${value}mm, but your ${oldLabel} requires ${existingValue}mm.`,
                    axle_type: `Wheel and axle fit mismatch: This ${newLabel} has ${value}, but your ${oldLabel} has ${existingValue}.`,
                    hub_spacing: `Wheel and axle fit mismatch: This ${newLabel} has ${value}, but your ${oldLabel} has ${existingValue}.`,
                    bottom_bracket_type: `Bottom bracket fit mismatch: This ${newLabel} uses ${value}, but your ${oldLabel} uses ${existingValue}.`,
                    freehub_type: `Cassette fit mismatch: This ${newLabel} needs ${value}, but your ${oldLabel} has ${existingValue}.`
                };

                warnings.push({
                    field: group.field,
                    relatedSlot: otherSlot,
                    message: messages[group.field] || message
                });
            });
        });

        const tireSlots = ['front_tire', 'rear_tire'];
        const frameSlots = ['frame', 'fork'];
        const tires = tireSlots.includes(slot)
            ? [{ slot, part: candidate }]
            : frameSlots.includes(slot)
                ? tireSlots.filter(key => current[key]).map(key => ({ slot: key, part: current[key] }))
                : [];

        const frames = frameSlots.includes(slot)
            ? [{ slot, part: candidate }]
            : tireSlots.includes(slot)
                ? frameSlots.filter(key => current[key]).map(key => ({ slot: key, part: current[key] }))
                : [];

        tires.forEach(tire => frames.forEach(frame => {
            const width = Number(meta(tire.part, 'tire_width_mm'));
            const clearance = Number(meta(frame.part, 'max_tire_width_mm'));

            if (!width || !clearance) {
                unverified = true;
            } else if (width > clearance) {
                warnings.push({
                    field: 'tire_clearance',
                    relatedSlot: frame.slot,
                    message: `Tire clearance mismatch: This tire is ${width}mm wide, but your ${components[frame.slot] || frame.slot} allows up to ${clearance}mm.`
                });
            }
        }));

        return { warnings, unverified };
    }

    function suggestedFilters(slot) {
        const filters = {};

        compatibilityGroups.forEach(group => {
            if (!group.slots.includes(slot)) return;

            const existing = group.slots.find(key =>
                key !== slot && draft[key] && meta(draft[key], group.field) !== null
            );

            if (existing) filters[group.field] = meta(draft[existing], group.field);
        });

        if (['front_tire', 'rear_tire'].includes(slot)) {
            const frame = ['frame', 'fork'].find(key =>
                draft[key] && meta(draft[key], 'max_tire_width_mm') !== null
            );

            if (frame) filters.max_tire_width_mm = meta(draft[frame], 'max_tire_width_mm');
        }

        return filters;
    }

    function updateWarningMarkers() {
        const flagged = new Set();

        Object.entries(draft).forEach(([slot, part]) => {
            if (part.forced_incompatible) flagged.add(slot);

            const current = { ...draft };
            delete current[slot];

            checkCompatibility(part, slot, current).warnings.forEach(warning => {
                flagged.add(slot);
                flagged.add(warning.relatedSlot);
            });
        });

        document.querySelectorAll('.bike-part-marker').forEach(marker => {
            marker.classList.toggle('has-fit-warning', flagged.has(marker.dataset.component));
        });
    }

    function updateSummary() {
        const entries = Object.entries(draft);
        const total = totalCost();
        const allowed = new Set(allowedComponents());

        setText('modelPartCount', entries.length);
        setText('modelPartTotal', money(total));
        setText('modelSideTotal', money(total));
        setText('modelTargetBudget', budgetValue() ? money(budgetValue()) : 'Not set');

        const list = $('modelSelectedParts');
        list.replaceChildren();

        if (!entries.length) {
            const empty = document.createElement('p');
            empty.className = 'builder-empty-note';
            empty.textContent = 'No parts added yet. Click a part on the bicycle to begin.';
            list.appendChild(empty);
        }

        entries.forEach(([key, part]) => {
            const row = document.createElement('div');
            row.className = 'model-selected-part';

            const imageUrl = safeUrl(part.image);
            if (imageUrl) {
                const image = document.createElement('img');
                image.className = 'model-selected-part-image';
                image.src = imageUrl;
                image.alt = part.title || components[key];
                image.loading = 'lazy';
                row.appendChild(image);
            }

            const details = document.createElement('div');
            details.className = 'model-selected-part-details';

            const name = document.createElement('span');
            name.textContent = components[key] || key;

            const product = document.createElement('small');
            product.textContent = part.title || 'Selected listing';

            const price = document.createElement('strong');
            price.textContent = money(part.price);

            details.append(name, product);

            if (!allowed.has(key)) {
                const note = document.createElement('small');
                note.className = 'selected-part-warning';
                note.textContent = 'Not usually used for this bicycle type';
                details.appendChild(note);
            }

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'selected-part-remove';
            remove.textContent = '×';
            remove.title = `Remove ${components[key] || key}`;
            remove.addEventListener('click', () => {
                delete draft[key];
                updateSummary();
                saveDraftLocally();
            });

            row.append(details, price, remove);
            list.appendChild(row);
        });

        Object.keys(components).forEach(key => {
            const selected = Boolean(draft[key]);

            document.querySelectorAll(`[data-component-status="${key}"]`).forEach(node => {
                node.textContent = selected ? 'Selected' : 'Choose';
            });

            document.querySelectorAll(`.bike-part-marker[data-component="${key}"], .component-button[data-component="${key}"]`).forEach(node => {
                node.classList.toggle('has-part', selected);
            });

            document.querySelectorAll(`.bike-component-drawing[data-component="${key}"], .interactive-bike-svg > g[data-component="${key}"]`).forEach(node => {
                node.classList.toggle('is-selected', selected);
            });
        });

        updateWarningMarkers();
    }

    async function checkMarketplaceStatus() {
        const note = $('marketplaceConnectionNote');
        if (!note) return;

        try {
            const response = await fetch('../api/marketplace_status.php', {
                headers: { Accept: 'application/json' }
            });
            const data = await response.json();

            note.textContent = data.message || 'Marketplace status is unavailable.';
            note.classList.toggle('is-ready', Boolean(data.configured));
            note.classList.toggle('is-offline', !data.configured);
        } catch (_) {
            note.textContent = 'Could not check the marketplace connection status.';
            note.classList.add('is-offline');
        }
    }

    function setFilterMode(mode) {
        filterMode = mode;

        document.querySelectorAll('[data-filter-mode]').forEach(button => {
            button.classList.toggle('active', button.dataset.filterMode === mode);
        });

        $('brandFilterPanel').hidden = mode !== 'brand';
        $('budgetFilterPanel').hidden = mode !== 'budget';
    }

    function selectComponent(key) {
        if (!components[key] || !allowedComponents().includes(key)) return;

        activeComponent = key;
        pendingProduct = null;
        compatibilityResult = null;
        compatibilityFilters = {};
        showAllResults = false;
        searchPage = 1;
        searchParams = null;
        searchProducts = [];

        setText('searchComponentTitle', components[key]);
        setText('searchSelectedComponent', components[key]);

        $('productKeyword').value = '';
        $('productBrand').value = '';
        $('budgetPreset').value = '500';
        $('customBudget').value = '';
        $('customBudget').hidden = true;
        $('customBudgetLabel').hidden = true;
        $('showAllSizesAnyway').checked = false;
        $('compatibilityFilterBanner').hidden = true;
        $('builderResultsToolbar').hidden = true;
        $('builderMessage').textContent = '';
        $('builderResults').replaceChildren();

        document.querySelectorAll('.bike-part-marker, .component-button').forEach(node => {
            node.classList.toggle('active', node.dataset.component === key);
        });

        setFilterMode('brand');
        showStep('search');
    }

    function readBudget() {
        const value = $('budgetPreset').value;

        if (value === 'any') return '';
        if (value === 'custom') {
            const custom = Number($('customBudget').value);
            return custom > 0 ? String(custom) : '';
        }

        return value;
    }

    function matchesBrand(product, brand) {
        if (!brand) return true;

        const wanted = brand.toLowerCase();
        return String(product.brand || '').toLowerCase().includes(wanted) ||
            String(product.title || '').toLowerCase().includes(wanted);
    }

    function sortProducts(products) {
        const sorted = [...products];
        const mode = $('productSort').value;

        if (mode === 'price_low') sorted.sort((a, b) => Number(a.price || 0) - Number(b.price || 0));
        if (mode === 'price_high') sorted.sort((a, b) => Number(b.price || 0) - Number(a.price || 0));
        if (mode === 'rating') sorted.sort((a, b) => Number(b.rating || 0) - Number(a.rating || 0));

        return sorted;
    }

    function createImage(url, alt, className = '') {
        const imageUrl = safeUrl(url);
        if (!imageUrl) return null;

        const image = document.createElement('img');
        image.src = imageUrl;
        image.alt = alt;
        image.loading = 'lazy';
        if (className) image.className = className;
        return image;
    }

    function createProductCard(product) {
        const card = document.createElement('article');
        card.className = 'builder-product-card live-product-card';

        const image = createImage(product.image, product.title || 'Bicycle component');

        if (image) {
            card.appendChild(image);
        } else {
            const placeholder = document.createElement('div');
            placeholder.className = 'builder-product-placeholder';
            placeholder.textContent = 'No image';
            card.appendChild(placeholder);
        }

        const details = document.createElement('div');
        details.className = 'live-product-details';

        const title = document.createElement('h3');
        title.textContent = product.title || 'Unnamed product';

        const price = document.createElement('strong');
        price.className = 'live-product-price';
        price.textContent = money(product.price);

        const seller = document.createElement('p');
        seller.textContent = `Seller: ${product.seller_name || 'Marketplace seller'}`;

        const brand = document.createElement('p');
        brand.textContent = product.brand && product.brand !== 'No Brand'
            ? `Brand: ${product.brand}`
            : 'Brand not provided';

        details.append(title, price, seller, brand);

        if (Number(product.rating || 0) > 0) {
            const rating = document.createElement('p');
            rating.textContent = `Rating: ${Number(product.rating).toFixed(1)} / 5${product.review_count ? ` · ${product.review_count} review(s)` : ''}`;
            details.appendChild(rating);
        }

        const linkUrl = safeUrl(product.url);
        if (linkUrl) {
            const link = document.createElement('a');
            link.href = linkUrl;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = 'View original listing';
            link.className = 'live-product-link';
            details.appendChild(link);
        }

        const choose = document.createElement('button');
        choose.type = 'button';
        choose.className = 'builder-primary-button';
        choose.textContent = 'Choose This Part';
        choose.addEventListener('click', () => chooseProduct(product));

        details.appendChild(choose);
        card.appendChild(details);
        return card;
    }

    function renderSearchProducts() {
        const resultList = $('builderResults');
        resultList.replaceChildren();

        sortProducts(searchProducts).forEach(product => {
            resultList.appendChild(createProductCard(product));
        });

        $('builderResultsToolbar').hidden = searchProducts.length === 0;
    }

    async function searchMarketplace(append = false) {
        if (!activeComponent || isLoadingMore) return;

        const extra = $('productKeyword').value.trim();
        const brand = filterMode === 'brand' ? $('productBrand').value.trim() : '';
        const maxBudget = filterMode === 'budget' ? readBudget() : '';

        if (filterMode === 'budget' && $('budgetPreset').value === 'custom' && !maxBudget) {
            $('builderMessage').textContent = 'Enter a valid custom maximum price.';
            return;
        }

        if (!append) {
            const query = ['bicycle', bikeType(), components[activeComponent], extra, brand].filter(Boolean).join(' ');
            searchParams = new URLSearchParams({ q: query });

            if (brand) searchParams.set('brand', brand);
            if (maxBudget) searchParams.set('max_budget', maxBudget);

            searchPage = 1;
            searchProducts = [];
            $('builderResults').replaceChildren();
        }

        const params = new URLSearchParams(searchParams || '');
        params.set('page', String(append ? searchPage + 1 : 1));

        const searchButton = $('builderSearchForm').querySelector('button[type="submit"]');
        const loadMoreButton = $('loadMoreResults');

        if (append) {
            isLoadingMore = true;
            loadMoreButton.disabled = true;
            loadMoreButton.textContent = 'Loading...';
        } else {
            searchButton.disabled = true;
            searchButton.textContent = 'Searching...';
            $('builderMessage').textContent = 'Searching marketplace listings...';
            $('builderResultsToolbar').hidden = true;
        }

        try {
            const response = await fetch(`../api/marketplace_search.php?${params.toString()}`, {
                headers: { Accept: 'application/json' }
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Marketplace search failed.');
            }

            let products = Array.isArray(data.products) ? data.products : [];
            const brandValue = searchParams?.get('brand') || '';
            const budgetLimit = searchParams?.get('max_budget') || '';

            if (brandValue) products = products.filter(product => matchesBrand(product, brandValue));

            if (budgetLimit) {
                products = products.filter(product =>
                    Number(product.price || 0) > 0 && Number(product.price) <= Number(budgetLimit)
                );
            }

            if (Object.keys(compatibilityFilters).length && !showAllResults) {
                const tagged = products.filter(product => Object.keys(jsonCompatibility(product)).length > 0);

                if (tagged.length) {
                    products = products.filter(product => {
                        const metadata = jsonCompatibility(product);

                        return Object.entries(compatibilityFilters).every(([field, value]) => {
                            if (field === 'max_tire_width_mm') {
                                return !metadata.tire_width_mm || Number(metadata.tire_width_mm) <= Number(value);
                            }

                            return metadata[field] == null ||
                                String(metadata[field]).toLowerCase() === String(value).toLowerCase();
                        });
                    });
                }
            }

            searchProducts = append ? searchProducts.concat(products) : products;
            searchPage = Number(data.page || params.get('page') || 1);
            renderSearchProducts();

            if (!searchProducts.length) {
                $('builderMessage').textContent = 'No matching listings found. Try a different keyword, brand, or budget.';
                loadMoreButton.hidden = true;
            } else if (append && products.length === 0) {
                $('builderMessage').textContent = `No more listings were returned. ${searchProducts.length} listing(s) are shown.`;
                loadMoreButton.hidden = true;
            } else {
                $('builderMessage').textContent = `${searchProducts.length} listing(s) loaded. Prices are estimates from marketplace listings.`;
                loadMoreButton.hidden = products.length === 0;
            }
        } catch (error) {
            console.error(error);
            $('builderMessage').textContent = error.message || 'Unable to connect to the marketplace.';
            if (!append) loadMoreButton.hidden = true;
        } finally {
            searchButton.disabled = false;
            searchButton.textContent = 'Search Marketplace Listings';
            isLoadingMore = false;
            loadMoreButton.disabled = false;
            loadMoreButton.textContent = 'Load More Results';
        }
    }

    function chooseProduct(product) {
        pendingProduct = { ...product };

        const current = { ...draft };
        delete current[activeComponent];

        compatibilityResult = checkCompatibility(product, activeComponent, current);
        renderConfirmation();
        showStep('confirm');
    }

    function renderConfirmation() {
        if (!pendingProduct) return;

        const card = $('confirmProductCard');
        card.replaceChildren();

        const image = createImage(pendingProduct.image, pendingProduct.title || 'Selected product');
        if (image) card.appendChild(image);

        const details = document.createElement('div');
        const title = document.createElement('h3');
        title.textContent = pendingProduct.title || 'Unnamed product';

        const price = document.createElement('strong');
        price.className = 'live-product-price';
        price.textContent = money(pendingProduct.price);

        const component = document.createElement('p');
        component.textContent = `Component: ${components[activeComponent]}`;

        const seller = document.createElement('p');
        seller.textContent = `Seller: ${pendingProduct.seller_name || 'Marketplace seller'}`;

        details.append(title, price, component, seller);
        card.appendChild(details);

        const warning = $('compatibilityWarning');
        const list = $('compatibilityWarningList');
        const warnings = compatibilityResult.warnings || [];

        list.replaceChildren();

        warnings.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item.message;
            list.appendChild(li);
        });

        warning.hidden = warnings.length === 0;
        $('confirmAddProduct').hidden = warnings.length > 0;
        $('compatibilityReviewNote').hidden = warnings.length > 0 || !compatibilityResult.unverified;
    }

    function addPendingProduct(force = false) {
        if (!pendingProduct || !activeComponent) return;

        if (draft[activeComponent] && !window.confirm(`Replace your current ${components[activeComponent]}?`)) return;

        const status = compatibilityResult.warnings.length
            ? 'incompatible'
            : compatibilityResult.unverified
                ? 'needs_review'
                : 'compatible';

        draft[activeComponent] = {
            ...pendingProduct,
            component_key: activeComponent,
            component_label: components[activeComponent],
            title: pendingProduct.title || 'Unnamed product',
            price: Number(pendingProduct.price || 0),
            image: pendingProduct.image || '',
            url: pendingProduct.url || '',
            brand: pendingProduct.brand || '',
            source: pendingProduct.source || 'Lazada',
            item_id: pendingProduct.item_id || '',
            sku_id: pendingProduct.sku_id || '',
            compatibility: jsonCompatibility(pendingProduct),
            compatibility_status: status,
            forced_incompatible: Boolean(force && status === 'incompatible')
        };

        pendingProduct = null;
        compatibilityResult = null;

        updateSummary();
        saveDraftLocally();
        showStep('model');
        setText('modelMessage', `${components[activeComponent]} added to your bicycle plan.`);
    }

    function renderReview() {
        const entries = Object.entries(draft);
        const total = totalCost();
        const budget = budgetValue();
        const remaining = budget - total;

        setText('reviewBikeType', bikeType() || 'Not selected');
        setText('reviewBudget', budget ? money(budget) : 'No target set');
        setText('reviewTotal', money(total));
        setText('reviewRemaining', budget ? money(remaining) : 'Not applicable');
        setText('reviewPartCount', `${entries.length} part(s) selected`);

        const list = $('reviewPartsList');
        list.replaceChildren();

        if (!entries.length) {
            const empty = document.createElement('p');
            empty.className = 'builder-empty-note';
            empty.textContent = 'No parts selected. You can save the plan and continue editing later.';
            list.appendChild(empty);
        }

        entries.forEach(([key, part]) => {
            const row = document.createElement('article');
            row.className = 'builder-review-part';

            const image = createImage(part.image, part.title || components[key]);
            if (image) row.appendChild(image);

            const details = document.createElement('div');
            details.className = 'builder-review-part-details';

            const label = document.createElement('strong');
            label.textContent = components[key] || key;

            const title = document.createElement('span');
            title.textContent = part.title || 'Unnamed product';

            const price = document.createElement('b');
            price.textContent = money(part.price);

            details.append(label, title, price);

            if (part.forced_incompatible || part.compatibility_status === 'needs_review') {
                const note = document.createElement('small');
                note.className = part.forced_incompatible ? 'review-fit-warning' : 'review-fit-note';
                note.textContent = part.forced_incompatible
                    ? '⚠ Fit warning — added for later review'
                    : 'Specifications not verified';
                details.appendChild(note);
            }

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'review-remove-button';
            remove.textContent = 'Remove';
            remove.addEventListener('click', () => {
                delete draft[key];
                updateSummary();
                saveDraftLocally();
                renderReview();
            });

            row.append(details, remove);
            list.appendChild(row);
        });

        const message = $('reviewBudgetMessage');
        message.classList.remove('over-budget');

        if (!budget) {
            message.textContent = 'No target budget was entered. You can still save this plan.';
        } else if (remaining < 0) {
            message.textContent = `Your selected parts are ${money(Math.abs(remaining))} over budget.`;
            message.classList.add('over-budget');
        } else {
            message.textContent = `You are ${money(remaining)} below your target budget.`;
        }
    }

    async function savePlan() {
        if (!bikeType()) {
            showStep('type');
            setText('typeMessage', 'Choose a bicycle type first.');
            return;
        }

        if (!$('bicycleName').value.trim()) {
            showStep('type');
            setText('typeMessage', 'Enter a name for your bicycle before continuing.');
            $('bicycleName').focus();
            return;
        }

        const button = $('savePlanButton');
        button.disabled = true;
        button.textContent = 'Saving...';
        setText('savePlanMessage', 'Saving your bicycle plan...');

        const payload = {
            csrfToken: config.csrfToken,
            projectId: Number(config.projectId || 0),
            bicycleId: selectedBicycleId,
            bicycleName: $('bicycleName').value.trim(),
            bicycleType: bikeType(),
            bicycleBrand: $('bikeBrand').value.trim(),
            bicycleModel: $('bikeModel').value.trim(),
            buildName: $('buildName').value.trim() || `${bikeType()} Build`,
            budget: budgetValue(),
            parts: Object.entries(draft).map(([key, part]) => ({
                ...part,
                component_key: key,
                component_label: components[key] || key
            }))
        };

        try {
            const response = await fetch('save_plan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to save your plan.');
            }

            try {
                localStorage.removeItem(draftKey);
            } catch (_) {}

            setText('savePlanMessage', 'Saved! Returning to My Garage...');
            window.location.href = config.dashboardUrl || '../bicycles.php';
        } catch (error) {
            setText('savePlanMessage', error.message);
        } finally {
            button.disabled = false;
            button.textContent = config.projectId ? 'Update Build Plan' : 'Save Build Plan';
        }
    }

    async function deletePlan() {
        if (!config.projectId || !window.confirm('Delete this bicycle plan? Its selected parts will also be removed.')) return;

        const button = $('deletePlanButton');
        button.disabled = true;

        try {
            const response = await fetch('delete_plan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    projectId: Number(config.projectId),
                    csrfToken: config.csrfToken
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to delete plan.');
            }

            try {
                localStorage.removeItem(draftKey);
            } catch (_) {}

            window.location.href = config.dashboardUrl || '../bicycles.php';
        } catch (error) {
            setText('savePlanMessage', error.message);
            button.disabled = false;
        }
    }

    document.querySelectorAll('.bike-type-card').forEach(card => {
        card.addEventListener('click', () => setBikeType(card.dataset.bikeType));
    });

    $('toBikeModel').addEventListener('click', () => {
        if (!bikeType()) {
            setText('typeMessage', 'Choose a bicycle type before continuing.');
            return;
        }

        if (!$('bicycleName').value.trim()) {
            setText('typeMessage', 'Enter a name for your bicycle before continuing.');
            $('bicycleName').focus();
            return;
        }

        if (!$('buildName').value.trim()) $('buildName').value = `${bikeType()} Build`;

        setText('typeMessage', '');
        updateSummary();
        saveDraftLocally();
        showStep('model');
    });

    document.querySelectorAll('.bike-part-marker, .component-button').forEach(element => {
        element.addEventListener('click', () => selectComponent(element.dataset.component));

        if (element.classList.contains('bike-part-marker')) {
            element.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectComponent(element.dataset.component);
                }
            });
        }
    });

    document.querySelectorAll('[data-filter-mode]').forEach(button => {
        button.addEventListener('click', () => setFilterMode(button.dataset.filterMode));
    });

    $('budgetPreset').addEventListener('change', () => {
        const custom = $('budgetPreset').value === 'custom';
        $('customBudget').hidden = !custom;
        $('customBudgetLabel').hidden = !custom;
    });

    $('builderSearchForm').addEventListener('submit', event => {
        event.preventDefault();
        searchMarketplace(false);
    });

    $('productSort').addEventListener('change', renderSearchProducts);
    $('loadMoreResults').addEventListener('click', () => searchMarketplace(true));

    $('showAllSizesAnyway').addEventListener('change', event => {
        showAllResults = event.target.checked;
        if (showAllResults) compatibilityFilters = {};
        searchMarketplace(false);
    });

    $('backToModelFromSearch').addEventListener('click', () => showStep('model'));
    $('cancelProductSelection').addEventListener('click', () => showStep('search'));
    $('confirmAddProduct').addEventListener('click', () => addPendingProduct(false));
    $('addAnyway').addEventListener('click', () => addPendingProduct(true));

    $('findCompatibleParts').addEventListener('click', () => {
        compatibilityFilters = suggestedFilters(activeComponent);
        showAllResults = false;
        $('showAllSizesAnyway').checked = false;
        $('compatibilityFilterBanner').hidden = Object.keys(compatibilityFilters).length === 0;
        setText('compatibilityFilterMessage', 'Looking for listings with matching specifications. Items without specifications may still need manual checking.');
        showStep('search');
        searchMarketplace(false);
    });

    $('toReviewFromModel').addEventListener('click', () => {
        renderReview();
        saveDraftLocally();
        showStep('review');
    });

    $('backToBikeType').addEventListener('click', () => showStep('type'));
    $('backToModelFromReview').addEventListener('click', () => showStep('model'));
    $('savePlanButton').addEventListener('click', savePlan);
    $('deletePlanButton').addEventListener('click', deletePlan);

    const fieldDefaults = {
        bicycleName: initial.bicycleName || '',
        buildName: initial.projectName || '',
        bikeBrand: initial.bikeBrand || '',
        bikeModel: initial.bikeModel || '',
        targetBudget: Number(initial.budget || 0) || ''
    };

    ['bicycleName', 'buildName', 'bikeBrand', 'bikeModel', 'targetBudget'].forEach(id => {
        const restoredValue = restoredDraft
            ? id === 'targetBudget' ? restoredDraft.budget : restoredDraft[id]
            : undefined;

        $(id).value = restoredValue !== undefined ? restoredValue : fieldDefaults[id];

        $(id).addEventListener('input', () => {
            if (id === 'targetBudget') updateSummary();
            saveDraftLocally();
        });
    });

    const savedBicycle = $('savedBicycle');

    if (savedBicycle) {
        const savedBicycleId = String(selectedBicycleId || '');
        savedBicycle.value = savedBicycleId;

        if (savedBicycle.value !== savedBicycleId) {
            selectedBicycleId = 0;
            savedBicycle.value = '';
        }

        savedBicycle.addEventListener('change', () => {
            const option = savedBicycle.options[savedBicycle.selectedIndex];
            selectedBicycleId = savedBicycle.value ? Number(savedBicycle.value) : 0;

            if (selectedBicycleId && option) {
                const type = option.dataset.type || '';

                $('bicycleName').value = option.dataset.name || '';
                $('bikeBrand').value = option.dataset.brand || '';
                $('bikeModel').value = option.dataset.model || '';

                if (!$('buildName').value.trim()) {
                    $('buildName').value = `${option.dataset.name || 'My Bicycle'} Build`;
                }

                if (type) setBikeType(type);
            } else {
                $('bicycleName').value = '';
                $('bikeBrand').value = '';
                $('bikeModel').value = '';
            }

            saveDraftLocally();
        });
    }

    const selectedType = (restoredDraft && restoredDraft.bikeType) || initial.bikeType || '';

    if (selectedType) setBikeType(selectedType);

    if (config.projectId) {
        $('deletePlanButton').hidden = false;
        $('savePlanButton').textContent = 'Update Build Plan';
    }

    updateSummary();
    saveDraftLocally();
    checkMarketplaceStatus();
    showStep(config.projectId || selectedType ? 'model' : 'type');
});
