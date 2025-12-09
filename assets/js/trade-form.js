/**
 * HDH: Trade Form Handler
 * Handles item selection, quantity inputs, and form validation
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const offerItemsGrid = document.getElementById('offer-items-grid');
        const offerQuantities = document.getElementById('offer-quantities');
        const maxOfferItems = 3;
        
        if (!offerItemsGrid || !offerQuantities) {
            console.warn('HDH Trade Form: offer-items-grid or offer-quantities not found');
            return;
        }
        
        // Track selected offer items
        let selectedOfferItems = [];
        
        // Handle offer item checkbox changes - use event delegation for better reliability
        offerItemsGrid.addEventListener('change', function(e) {
            if (e.target.type !== 'checkbox') {
                return;
            }
            
            const checkbox = e.target;
            const itemSlug = checkbox.value;
            const itemCardWrapper = checkbox.closest('.item-card-wrapper');
            const itemLabelElement = itemCardWrapper ? itemCardWrapper.querySelector('.item-card-label') : null;
            const itemLabel = itemLabelElement ? itemLabelElement.textContent.trim() : itemSlug;
            
            console.log('HDH Trade Form: Checkbox changed', { itemSlug, checked: checkbox.checked, itemLabel });
            
            if (checkbox.checked) {
                // Check if we've reached the limit
                if (selectedOfferItems.length >= maxOfferItems) {
                    checkbox.checked = false;
                    alert('En fazla ' + maxOfferItems + ' ürün seçebilirsiniz.');
                    return;
                }
                
                // Check if already selected
                const alreadySelected = selectedOfferItems.some(function(item) {
                    return item.slug === itemSlug;
                });
                
                if (!alreadySelected) {
                    selectedOfferItems.push({
                        slug: itemSlug,
                        label: itemLabel
                    });
                    
                    console.log('HDH Trade Form: Adding quantity input for', itemSlug);
                    // Add quantity input
                    addQuantityInput(itemSlug, itemLabel);
                }
            } else {
                // Remove from selection
                selectedOfferItems = selectedOfferItems.filter(function(item) {
                    return item.slug !== itemSlug;
                });
                
                console.log('HDH Trade Form: Removing quantity input for', itemSlug);
                // Remove quantity input
                removeQuantityInput(itemSlug);
            }
        });
        
        // Add quantity input for selected offer item
        function addQuantityInput(slug, label) {
            // Check if already exists
            if (document.getElementById('offer_qty_' + slug)) {
                console.warn('HDH Trade Form: Quantity input already exists for', slug);
                return;
            }
            
            const quantityItem = document.createElement('div');
            quantityItem.className = 'offer-quantity-item';
            quantityItem.id = 'quantity-item-' + slug;
            quantityItem.style.cssText = 'margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #74C365;';
            quantityItem.innerHTML = `
                <label for="offer_qty_${slug}" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">${label} Miktarı:</label>
                <input type="number" 
                       id="offer_qty_${slug}" 
                       name="offer_qty[${slug}]" 
                       min="1" 
                       value="1" 
                       required
                       class="quantity-input"
                       style="width: 100%; max-width: 200px; padding: 10px; border: 2px solid #74C365; border-radius: 6px; font-size: 16px;">
                <input type="hidden" name="offer_item[${slug}]" value="${slug}">
            `;
            
            offerQuantities.appendChild(quantityItem);
            console.log('HDH Trade Form: Quantity input added for', slug, quantityItem);
            
            // Scroll to the new input
            quantityItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        // Remove quantity input
        function removeQuantityInput(slug) {
            const quantityItem = document.getElementById('quantity-item-' + slug);
            if (quantityItem) {
                quantityItem.remove();
            }
        }
        
        // Form validation
        const tradeForm = document.getElementById('create-trade-form');
        if (tradeForm) {
            tradeForm.addEventListener('submit', function(e) {
                const wantedItem = document.querySelector('input[name="wanted_item"]:checked');
                const wantedQty = document.getElementById('wanted_qty');
                
                if (!wantedItem) {
                    e.preventDefault();
                    alert('Lütfen almak istediğiniz hediye seçin.');
                    return false;
                }
                
                if (!wantedQty.value || parseInt(wantedQty.value) < 1) {
                    e.preventDefault();
                    alert('Lütfen geçerli bir miktar girin.');
                    return false;
                }
                
                if (selectedOfferItems.length === 0) {
                    e.preventDefault();
                    alert('Lütfen en az bir ürün seçin (vermek istediğiniz hediye).');
                    return false;
                }
                
                // Validate offer quantities
                const offerQtyInputs = offerQuantities.querySelectorAll('input[type="number"]');
                let allValid = true;
                offerQtyInputs.forEach(function(input) {
                    if (!input.value || parseInt(input.value) < 1) {
                        allValid = false;
                    }
                });
                
                if (!allValid) {
                    e.preventDefault();
                    alert('Lütfen tüm ürünler için geçerli miktarlar girin.');
                    return false;
                }
            });
        }
    });
})();
