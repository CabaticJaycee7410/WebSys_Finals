
document.addEventListener('DOMContentLoaded',function(){
    const form=document.getElementById('marketplaceSearchForm');
    if(!form)return;

    const message=document.getElementById('marketplaceMessage');
    const results=document.getElementById('marketplaceResults');
    const savedMessage=document.getElementById('marketplaceSavedMessage');
    const wishlistContainer=document.getElementById('marketplaceWishlist');
    const favoritesContainer=document.getElementById('marketplaceFavorites');
    const csrfToken=document.getElementById('marketplaceCsrfToken').value;
    const money=value=>'₱'+Number(value||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});

    let savedItems=[];
    let projects=[];

    const connectionNote=document.getElementById('marketplaceConnectionNote');

    if(connectionNote){
        fetch('api/marketplace_status.php',{headers:{Accept:'application/json'}})
            .then(response=>response.json())
            .then(data=>{
                connectionNote.textContent=data.message||'Marketplace status is unavailable.';
                connectionNote.classList.toggle('is-ready',Boolean(data.configured));
                connectionNote.classList.toggle('is-offline',!data.configured);
            })
            .catch(()=>{
                connectionNote.textContent='Could not check the marketplace connection status.';
                connectionNote.classList.add('is-offline');
            });
    }

    function safeUrl(value){
        return /^https?:\/\//i.test(String(value||''))?String(value):'';
    }

    function productMatches(saved,product){
        const productUrl=String(product.url||'').trim().toLowerCase();
        const savedUrl=String(saved.url||'').trim().toLowerCase();

        if(productUrl&&savedUrl)return productUrl===savedUrl;

        return String(saved.title||'').trim().toLowerCase()===String(product.title||'').trim().toLowerCase()
            &&String(saved.brand||'').trim().toLowerCase()===String(product.brand||'').trim().toLowerCase()
            &&String(saved.seller_name||'').trim().toLowerCase()===String(product.seller_name||'').trim().toLowerCase();
    }

    function findSavedItem(type,product,projectId){
        return savedItems.find(item=>
            item.item_type===type
            &&String(item.project_id||'')===String(projectId||'')
            &&productMatches(item,product)
        );
    }

    async function postSavedItem(values){
        const params=new URLSearchParams();
        Object.keys(values).forEach(key=>params.set(key,values[key]===null?'':String(values[key])));
        params.set('csrf_token',csrfToken);

        const response=await fetch('api/marketplace_saved_items.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8',Accept:'application/json'},
            body:params.toString()
        });

        const data=await response.json();
        if(!response.ok||!data.success)throw new Error(data.message||'Unable to update saved products.');
        return data;
    }

    async function loadSavedItems(){
        const response=await fetch('api/marketplace_saved_items.php',{headers:{Accept:'application/json'}});
        const data=await response.json();

        if(!response.ok||!data.success)throw new Error(data.message||'Unable to load saved products.');

        savedItems=data.items||[];
        projects=data.projects||[];

        renderSavedItems('wishlist',wishlistContainer);
        renderSavedItems('favorite',favoritesContainer);
        updateAllProductButtons();
    }

    function createProjectSelect(){
        const wrapper=document.createElement('div');
        wrapper.className='marketplace-product-plan';

        const label=document.createElement('label');
        label.textContent='Associate with a bicycle build plan (optional)';

        const select=document.createElement('select');
        select.className='marketplace-save-project';
        select.setAttribute('aria-label','Choose bicycle build plan');

        const unassigned=document.createElement('option');
        unassigned.value='';
        unassigned.textContent='No build plan selected';
        select.appendChild(unassigned);

        projects.forEach(project=>{
            const option=document.createElement('option');
            option.value=project.project_id;
            option.textContent=project.project_name;
            select.appendChild(option);
        });

        wrapper.append(label,select);
        return {wrapper,select};
    }

    function updateProductButtons(card){
        if(!card||!card._marketplaceProduct)return;

        const product=card._marketplaceProduct;
        const projectId=card._marketplaceProject.value;

        ['wishlist','favorite'].forEach(type=>{
            const button=card._marketplaceButtons[type];
            const saved=findSavedItem(type,product,projectId);

            button.classList.toggle('is-saved',Boolean(saved));
            button.textContent=saved
                ?(type==='wishlist'?'✓ In Wishlist':'✓ In Favorites')
                :(type==='wishlist'?'♡ Add to Wishlist':'★ Add to Favorites');
        });
    }

    function updateAllProductButtons(){
        results.querySelectorAll('.marketplace-product-card').forEach(updateProductButtons);
    }

    async function toggleSaved(type,card){
        const product=card._marketplaceProduct;
        const projectId=card._marketplaceProject.value;
        const existing=findSavedItem(type,product,projectId);
        const button=card._marketplaceButtons[type];

        button.disabled=true;

        try{
            if(existing){
                await postSavedItem({action:'remove',saved_item_id:existing.saved_item_id});
                savedMessage.textContent=type==='wishlist'?'Removed from Wishlist.':'Removed from Favorites.';
            }else{
                const priceText=String(product.price||'').replace(/[^0-9.]/g,'');
                const price=priceText!==''&&isFinite(Number(priceText))?priceText:'';

                const response=await postSavedItem({
                    action:'save',
                    item_type:type,
                    project_id:projectId,
                    title:product.title||'',
                    brand:product.brand||'',
                    seller_name:product.seller_name||'',
                    price:price,
                    image:product.image||'',
                    url:product.url||'',
                    source:product.source||'lazada'
                });

                savedMessage.textContent=response.message||'Product saved.';
            }

            await loadSavedItems();
        }catch(error){
            savedMessage.textContent=error.message;
        }finally{
            button.disabled=false;
            updateProductButtons(card);
        }
    }

    function renderProduct(product){
        const card=document.createElement('article');
        card.className='marketplace-product-card';
        card._marketplaceProduct=product;

        const imageUrl=safeUrl(product.image);
        if(imageUrl){
            const image=document.createElement('img');
            image.src=imageUrl;
            image.alt=product.title||'Product';
            image.loading='lazy';
            card.appendChild(image);
        }

        const content=document.createElement('div');
        const title=document.createElement('h3');
        title.textContent=product.title||'Untitled product';

        const price=document.createElement('strong');
        price.textContent=money(product.price);

        const seller=document.createElement('p');
        seller.textContent='Brand: '+(product.brand||'Not specified')+' · Seller: '+(product.seller_name||'Not specified');

        content.append(title,price,seller);

        const productUrl=safeUrl(product.url);
        if(productUrl){
            const link=document.createElement('a');
            link.href=productUrl;
            link.target='_blank';
            link.rel='noopener noreferrer';
            link.textContent='View listing';
            content.appendChild(link);
        }

        const projectControl=createProjectSelect();
        const actions=document.createElement('div');
        actions.className='marketplace-product-actions';

        const wishlistButton=document.createElement('button');
        wishlistButton.type='button';
        wishlistButton.className='marketplace-action-button';

        const favoriteButton=document.createElement('button');
        favoriteButton.type='button';
        favoriteButton.className='marketplace-action-button';

        actions.append(wishlistButton,favoriteButton);
        content.append(projectControl.wrapper,actions);
        card.appendChild(content);

        card._marketplaceProject=projectControl.select;
        card._marketplaceButtons={wishlist:wishlistButton,favorite:favoriteButton};

        wishlistButton.addEventListener('click',()=>toggleSaved('wishlist',card));
        favoriteButton.addEventListener('click',()=>toggleSaved('favorite',card));
        projectControl.select.addEventListener('change',()=>updateProductButtons(card));

        updateProductButtons(card);
        results.appendChild(card);
    }

    function renderSavedItems(type,container){
        if(!container)return;

        container.replaceChildren();

        const items=savedItems.filter(item=>item.item_type===type);

        if(!items.length){
            const empty=document.createElement('p');
            empty.className='marketplace-saved-empty';
            empty.textContent=type==='wishlist'?'Your Wishlist is empty. Save products you plan to buy later.':'Your Favorites list is empty. Save products you like here.';
            container.appendChild(empty);
            return;
        }

        items.forEach(item=>{
            const card=document.createElement('article');
            card.className='marketplace-saved-item';

            const imageUrl=safeUrl(item.image);
            if(imageUrl){
                const image=document.createElement('img');
                image.src=imageUrl;
                image.alt=item.title||'Saved product';
                image.loading='lazy';
                card.appendChild(image);
            }

            const info=document.createElement('div');
            info.className='marketplace-saved-item-info';

            const title=document.createElement('strong');
            title.textContent=item.title||'Saved product';

            const details=document.createElement('p');
            details.textContent=(item.brand||'')+(item.brand&&item.seller_name?' · ':'')+(item.seller_name||'');

            const price=document.createElement('p');
            price.textContent=item.price!==null&&item.price!==''?money(item.price):'Price unavailable';

            const project=document.createElement('p');
            project.textContent=item.project_name?'Build plan: '+item.project_name:'Not linked to a build plan';

            info.append(title,details,price,project);

            const actions=document.createElement('div');
            actions.className='marketplace-saved-item-actions';

            const productUrl=safeUrl(item.url);
            if(productUrl){
                const link=document.createElement('a');
                link.href=productUrl;
                link.target='_blank';
                link.rel='noopener noreferrer';
                link.textContent='View listing';
                actions.appendChild(link);
            }

            const remove=document.createElement('button');
            remove.type='button';
            remove.className='marketplace-saved-remove';
            remove.textContent='Remove';

            remove.addEventListener('click',async()=>{
                remove.disabled=true;
                try{
                    await postSavedItem({action:'remove',saved_item_id:item.saved_item_id});
                    savedMessage.textContent=type==='wishlist'?'Product removed from Wishlist.':'Product removed from Favorites.';
                    await loadSavedItems();
                }catch(error){
                    savedMessage.textContent=error.message;
                }finally{
                    remove.disabled=false;
                }
            });

            actions.appendChild(remove);
            info.appendChild(actions);
            card.appendChild(info);
            container.appendChild(card);
        });
    }

    form.addEventListener('submit',async event=>{
        event.preventDefault();

        const params=new URLSearchParams();
        params.set('q',document.getElementById('marketplaceKeyword').value.trim());

        const brand=document.getElementById('marketplaceBrand').value.trim();
        const budget=document.getElementById('marketplaceBudget').value.trim();

        if(brand)params.set('brand',brand);
        if(budget)params.set('max_budget',budget);

        message.textContent='Searching Lazada...';
        results.replaceChildren();

        try{
            const response=await fetch('api/marketplace_search.php?'+params.toString(),{headers:{Accept:'application/json'}});
            const data=await response.json();

            if(!response.ok||!data.success)throw new Error(data.message||'Search failed.');

            if(!data.products.length){
                message.textContent='No matching products found.';
                return;
            }

            message.textContent=data.count+' products found.';
            data.products.forEach(renderProduct);
        }catch(error){
            message.textContent=error.message;
        }
    });

    loadSavedItems().catch(error=>{
        wishlistContainer.innerHTML='';
        favoritesContainer.innerHTML='';

        const wishlistError=document.createElement('p');
        wishlistError.className='marketplace-saved-empty';
        wishlistError.textContent='Unable to load Wishlist: '+error.message;

        const favoritesError=document.createElement('p');
        favoritesError.className='marketplace-saved-empty';
        favoritesError.textContent='Unable to load Favorites: '+error.message;

        wishlistContainer.appendChild(wishlistError);
        favoritesContainer.appendChild(favoritesError);
    });
});
