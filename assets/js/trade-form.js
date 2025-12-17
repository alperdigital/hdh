/**
 * HDH: Trade Form Handler (Enhanced UX)
 * Handles item selection, quantity steppers, and form validation
 */

(function() {
    'use strict';

    // Configuration
    const MAX_OFFER_ITEMS = 3;
    const MIN_QTY = 1;
    const MAX_QTY = 999;

    // Wait for DOM to be ready
    function initTradeForm() {
        console.log('HDH Trade Form: Initializing enhanced UX...');
        
        // Initialize wanted item selection
        initWantedItemSelection();
        
        // Initialize offer item selection
        initOfferItemSelection();
        
        // Initialize quantity steppers
        initQuantitySteppers();
        
        // Initialize form validation
        initFormValidation();
    }
    
    /**
     * Initialize wanted item selection (radio buttons)
     */
    function initWantedItemSelection() {
        const wantedItemsGrid = document.getElementById('wanted-items-grid');
        const wantedQuantityWrapper = document.getElementById('wanted-quantity-wrapper');
        
        if (!wantedItemsGrid || !wantedQuantityWrapper) return;
        
        wantedItemsGrid.addEventListener('change', function(e) {
            if (e.target.type !== 'radio' || e.target.name !== 'wanted_item') return;
            
            const selectedCard = e.target.closest('.item-card-wrapper');
            
            // Remove selected state from all cards
            wantedItemsGrid.querySelectorAll('.item-card-wrapper').forEach(function(card) {
                card.classList.remove('selected');
            });
            
            // Add selected state to clicked card
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Show quantity stepper with animation
            wantedQuantityWrapper.style.display = 'block';
            setTimeout(function() {
                wantedQuantityWrapper.classList.add('visible');
            }, 10);
            
            console.log('HDH Trade Form: Wanted item selected', e.target.value);
        });
    }
    
    /**
     * Initialize offer item selection (checkboxes)
     */
    function initOfferItemSelection() {
        const offerItemsGrid = document.getElementById('offer-items-grid');
        const offerQuantities = document.getElementById('offer-quantities');
        const selectionCount = document.getElementById('offer-selection-count');
        
        if (!offerItemsGrid || !offerQuantities) {
            console.error('HDH Trade Form: offer-items-grid or offer-quantities not found');
            return;
        }
        
        // Get current selected count
        function getSelectedCount() {
            const checkedBoxes = offerItemsGrid.querySelectorAll('input[type="checkbox"]:checked');
            return checkedBoxes.length;
        }
        
        offerItemsGrid.addEventListener('change', function(e) {
            if (e.target.type !== 'checkbox') return;
            
            const checkbox = e.target;
            const itemSlug = checkbox.value;
            const itemCardWrapper = checkbox.closest('.item-card-wrapper');
            const itemLabelElement = itemCardWrapper ? itemCardWrapper.querySelector('.item-card-label') : null;
            const itemLabel = itemLabelElement ? itemLabelElement.textContent.trim() : itemSlug;
            
            console.log('HDH Trade Form: Checkbox changed', {
                slug: itemSlug,
                label: itemLabel,
                checked: checkbox.checked
            });
            
            if (checkbox.checked) {
                // Check limit - get current count before adding
                const currentCount = getSelectedCount();
                console.log('HDH Trade Form: Current selected count before adding:', currentCount);
                if (currentCount > MAX_OFFER_ITEMS) {
                    checkbox.checked = false;
                    showToast('En fazla ' + MAX_OFFER_ITEMS + ' ürün seçebilirsiniz', 'warning');
                    return;
                }
                
                // Add selected state
                if (itemCardWrapper) {
                    itemCardWrapper.classList.add('selected');
                }
                
                // Ensure offer-quantities container is visible
                if (offerQuantities) {
                    offerQuantities.style.display = 'flex';
                    offerQuantities.style.visibility = 'visible';
                }
                
                // Add quantity stepper
                console.log('HDH Trade Form: Adding quantity stepper for', itemSlug, 'with label', itemLabel);
                addQuantityStepper(itemSlug, itemLabel);
                
            } else {
                // Remove selected state
                if (itemCardWrapper) {
                    itemCardWrapper.classList.remove('selected');
                }
                
                // Remove quantity stepper
                console.log('HDH Trade Form: Removing quantity stepper for', itemSlug);
                removeQuantityStepper(itemSlug);
                
                // Hide offer-quantities container if no items selected
                const remainingCount = getSelectedCount();
                if (remainingCount === 0 && offerQuantities) {
                    offerQuantities.style.display = 'none';
                }
            }
            
            // Update selection count - get actual count
            const actualCount = getSelectedCount();
            updateSelectionCount(actualCount);
            
            console.log('HDH Trade Form: Offer items selected', actualCount);
        });
    }
    
    /**
     * Add quantity stepper for offer item
     */
    function addQuantityStepper(slug, label) {
        const offerQuantities = document.getElementById('offer-quantities');
        if (!offerQuantities) {
            console.error('HDH Trade Form: offer-quantities container not found');
            return;
        }
        
        // Check if already exists
        const existingStepper = document.getElementById('quantity-item-' + slug);
        if (existingStepper) {
            console.log('HDH Trade Form: Quantity stepper already exists for', slug);
            return;
        }
        
        console.log('HDH Trade Form: Creating quantity stepper for', slug, 'with label', label);
        
        const stepperItem = document.createElement('div');
        stepperItem.className = 'offer-quantity-item';
        stepperItem.id = 'quantity-item-' + slug;
        stepperItem.innerHTML = `
            <div class="quantity-stepper-wrapper">
                <label class="stepper-label">
                    <span class="stepper-label-text">${label}</span>
                    <span class="stepper-hint">Kaç adet vereceksiniz?</span>
                </label>
                <div class="quantity-stepper">
                    <button type="button" class="qty-btn qty-minus" data-target="offer_qty_${slug}" aria-label="Azalt">−</button>
                    <input type="number" 
                           id="offer_qty_${slug}" 
                           name="offer_qty[${slug}]" 
                           min="${MIN_QTY}" 
                           max="${MAX_QTY}"
                           value="1" 
                           required
                           class="qty-input"
                           readonly>
                    <button type="button" class="qty-btn qty-plus" data-target="offer_qty_${slug}" aria-label="Artır">+</button>
                </div>
                <input type="hidden" name="offer_item[${slug}]" value="${slug}">
            </div>
        `;
        
        // Ensure container is visible
        offerQuantities.style.display = 'flex';
        offerQuantities.style.visibility = 'visible';
        offerQuantities.style.opacity = '1';
        
        // Append to DOM first
        offerQuantities.appendChild(stepperItem);
        
        // Force reflow to ensure element is in DOM and styles are applied
        void stepperItem.offsetHeight;
        void stepperItem.querySelector('.quantity-stepper-wrapper').offsetHeight;
        
        // Immediately make it visible (no delay for better UX)
        requestAnimationFrame(function() {
            stepperItem.classList.add('visible');
            stepperItem.style.opacity = '1';
            stepperItem.style.transform = 'translateY(0)';
            console.log('HDH Trade Form: Visible class added to stepper for', slug);
        });
        
        // Scroll into view
        setTimeout(function() {
            stepperItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 50);
        
        console.log('HDH Trade Form: Quantity stepper added successfully for', slug, 'Element:', stepperItem);
    }
    
    /**
     * Remove quantity stepper
     */
    function removeQuantityStepper(slug) {
        const stepperItem = document.getElementById('quantity-item-' + slug);
        if (!stepperItem) return;
        
        stepperItem.classList.remove('visible');
        setTimeout(function() {
            stepperItem.remove();
        }, 300);
    }
    
    /**
     * Update selection count display
     */
    function updateSelectionCount(count) {
        const selectionCount = document.getElementById('offer-selection-count');
        if (!selectionCount) return;
        
        selectionCount.textContent = count + '/3 seçildi';
        
        // Add visual feedback
        if (count >= MAX_OFFER_ITEMS) {
            selectionCount.classList.add('limit-reached');
        } else {
            selectionCount.classList.remove('limit-reached');
        }
    }
    
    /**
     * Initialize quantity steppers (+ / - buttons)
     */
    function initQuantitySteppers() {
        document.addEventListener('click', function(e) {
            if (!e.target.classList.contains('qty-btn')) return;
            
            const button = e.target;
            const targetId = button.getAttribute('data-target');
            const input = document.getElementById(targetId);
            
            if (!input) return;
            
            let currentValue = parseInt(input.value) || MIN_QTY;
            const min = parseInt(input.getAttribute('min')) || MIN_QTY;
            const max = parseInt(input.getAttribute('max')) || MAX_QTY;
            
            if (button.classList.contains('qty-minus')) {
                if (currentValue > min) {
                    input.value = currentValue - 1;
                    animateValue(input, 'decrease');
                }
            } else if (button.classList.contains('qty-plus')) {
                if (currentValue < max) {
                    input.value = currentValue + 1;
                    animateValue(input, 'increase');
                }
            }
            
            // Trigger change event for validation
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
    
    /**
     * Animate value change
     */
    function animateValue(input, direction) {
        input.classList.add('value-changing', 'value-' + direction);
        setTimeout(function() {
            input.classList.remove('value-changing', 'value-' + direction);
        }, 300);
    }
    
    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const tradeForm = document.getElementById('create-trade-form');
        if (!tradeForm) return;
        
        tradeForm.addEventListener('submit', function(e) {
            // Validate wanted item
            const wantedItem = document.querySelector('input[name="wanted_item"]:checked');
            if (!wantedItem) {
                e.preventDefault();
                showToast('Lütfen almak istediğiniz ürünü seçin', 'error');
                scrollToElement(document.getElementById('wanted-items-grid'));
                return false;
            }
            
            // Validate wanted quantity
            const wantedQty = document.getElementById('wanted_qty');
            const wantedQtyValue = parseInt(wantedQty.value);
            if (!wantedQtyValue || wantedQtyValue < MIN_QTY || wantedQtyValue > MAX_QTY) {
                e.preventDefault();
                showToast('Lütfen geçerli bir miktar girin (1-999)', 'error');
                scrollToElement(wantedQty);
                return false;
            }
            
            // Validate offer items
            const offerCheckboxes = document.querySelectorAll('input[name^="offer_item["]:checked');
            if (offerCheckboxes.length === 0) {
                e.preventDefault();
                const errorMsg = (window.hdhMessages && window.hdhMessages.ajax && window.hdhMessages.ajax.select_at_least_one_gift) 
                    ? window.hdhMessages.ajax.select_at_least_one_gift 
                    : 'Lütfen en az 1 ürün seçin (vermek istediğiniz)';
                showToast(errorMsg, 'error');
                scrollToElement(document.getElementById('offer-items-grid'));
                return false;
            }
            
            if (offerCheckboxes.length > MAX_OFFER_ITEMS) {
                e.preventDefault();
                showToast('En fazla ' + MAX_OFFER_ITEMS + ' ürün seçebilirsiniz', 'error');
                return false;
            }
            
            // Validate offer quantities
            let allValid = true;
            const offerQtyInputs = document.querySelectorAll('input[name^="offer_qty["]');
            offerQtyInputs.forEach(function(input) {
                const value = parseInt(input.value);
                if (!value || value < MIN_QTY || value > MAX_QTY) {
                    allValid = false;
                }
            });
            
            if (!allValid) {
                e.preventDefault();
                showToast('Lütfen tüm ürünler için geçerli miktarlar girin (1-999)', 'error');
                return false;
            }
            
            // Show loading state
            const submitBtn = tradeForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="btn-icon">⏳</span>İlan Oluşturuluyor...';
            }
            
            console.log('HDH Trade Form: Validation passed, submitting...');
        });
    }
    
    /**
     * Show toast notification
     */
    function showToast(message, type) {
        // Check if toast container exists
        let toastContainer = document.getElementById('hdh-toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'hdh-toast-container';
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        
        const icon = type === 'error' ? '❌' : type === 'warning' ? '⚠️' : '✅';
        toast.innerHTML = '<span class="toast-icon">' + icon + '</span><span class="toast-message">' + message + '</span>';
        
        toastContainer.appendChild(toast);
        
        // Animate in
        setTimeout(function() {
            toast.classList.add('visible');
        }, 10);
        
        // Remove after 3 seconds
        setTimeout(function() {
            toast.classList.remove('visible');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 3000);
    }
    
    /**
     * Scroll to element
     */
    function scrollToElement(element) {
        if (!element) return;
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTradeForm);
    } else {
        initTradeForm();
    }
})();
