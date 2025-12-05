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
            return;
        }
        
        // Track selected offer items
        let selectedOfferItems = [];
        
        // Handle offer item checkbox changes
        const offerCheckboxes = offerItemsGrid.querySelectorAll('input[type="checkbox"]');
        offerCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const itemSlug = this.value;
                const itemLabel = this.closest('.item-card-wrapper').querySelector('.item-card-label').textContent;
                
                if (this.checked) {
                    // Check if we've reached the limit
                    if (selectedOfferItems.length >= maxOfferItems) {
                        this.checked = false;
                        alert('En fazla ' + maxOfferItems + ' ürün seçebilirsiniz.');
                        return;
                    }
                    
                    selectedOfferItems.push({
                        slug: itemSlug,
                        label: itemLabel
                    });
                    
                    // Add quantity input
                    addQuantityInput(itemSlug, itemLabel);
                } else {
                    // Remove from selection
                    selectedOfferItems = selectedOfferItems.filter(function(item) {
                        return item.slug !== itemSlug;
                    });
                    
                    // Remove quantity input
                    removeQuantityInput(itemSlug);
                }
            });
        });
        
        // Add quantity input for selected offer item
        function addQuantityInput(slug, label) {
            // Check if already exists
            if (document.getElementById('offer_qty_' + slug)) {
                return;
            }
            
            const quantityItem = document.createElement('div');
            quantityItem.className = 'offer-quantity-item';
            quantityItem.id = 'quantity-item-' + slug;
            quantityItem.innerHTML = `
                <label for="offer_qty_${slug}">${label}:</label>
                <input type="number" 
                       id="offer_qty_${slug}" 
                       name="offer_qty[${slug}]" 
                       min="1" 
                       value="1" 
                       required
                       class="quantity-input">
                <input type="hidden" name="offer_item[${slug}]" value="${slug}">
            `;
            
            offerQuantities.appendChild(quantityItem);
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

