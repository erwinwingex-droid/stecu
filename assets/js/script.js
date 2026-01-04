// Mobile Navigation Toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
    }
    
    // Load promo data
    loadPromotions();
});

// Load Promotions
async function loadPromotions() {
    try {
        const response = await fetch('api/get_promotions.php');
        const promotions = await response.json();
        
        const container = document.getElementById('promo-container');
        if (container && promotions.length > 0) {
            container.innerHTML = promotions.map(promo => `
                <div class="promo-card">
                    <div class="promo-header">
                        <h3>${promo.name}</h3>
                    </div>
                    <div class="promo-body">
                        <p>${promo.description}</p>
                        <div class="promo-price">
                            ${promo.discount_type === 'percentage' ? 
                                `Diskon ${promo.discount_value}%` : 
                                `Potongan Rp ${parseInt(promo.discount_value).toLocaleString()}`
                            }
                        </div>
                        ${promo.min_quantity > 1 ? 
                            `<p><small>Minimal pembelian: ${promo.min_quantity} item</small></p>` : 
                            ''
                        }
                        <a href="pricing.php" class="btn btn-primary">Lihat Detail</a>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading promotions:', error);
    }
}

// Order Form Calculation
function calculateOrder() {
    const serviceSelect = document.getElementById('service');
    const quantityInput = document.getElementById('quantity');
    const totalPriceElement = document.getElementById('total-price');
    
    if (serviceSelect && quantityInput && totalPriceElement) {
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const basePrice = parseFloat(selectedOption.getAttribute('data-price'));
        const quantity = parseFloat(quantityInput.value) || 0;
        
        let totalPrice = basePrice * quantity;
        
        // Apply promo logic based on service and quantity
        const serviceId = serviceSelect.value;
        const promos = window.promotions || {};
        
        if (promos[serviceId] && quantity >= promos[serviceId].min_quantity) {
            if (promos[serviceId].discount_type === 'percentage') {
                totalPrice = totalPrice * (1 - promos[serviceId].discount_value / 100);
            } else {
                totalPrice = totalPrice - promos[serviceId].discount_value;
            }
        }
        
        totalPriceElement.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
    }
}

// Form Validation
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = 'red';
            isValid = false;
        } else {
            input.style.borderColor = '';
        }
    });
    
    return isValid;
}