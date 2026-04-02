
// --- AJAX ФУНКЦИИ ---
function addToCart(productId, productName, price, img) {
    fetch('ajax_add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count);
            showNotification(data.product_name + ' е добавен в количката!');
            
            // Ако количката е отворена, обновяваме съдържанието
            if (document.getElementById('cartModal').style.display === 'flex') {
                renderCartItems();
            }
        } else {
            showNotification('Грешка при добавяне в количката!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Грешка при връзка със сървъра!');
        
        // Резервен вариант с localStorage
        addToCartLocal(productId, productName, price, img);
    });
}

function removeFromCart(productId) {
    fetch('ajax_remove_from_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count);
            renderCartItems();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        removeFromCartLocal(productId);
        renderCartItems();
    });
}

function updateCartBadge(count) {
    const badge = document.querySelector('.cart-badge');
    if (badge) {
        badge.textContent = count;
        if (count > 0) {
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
    
    // Обновяваме и в навигацията 
    document.querySelectorAll('.cart-badge').forEach(b => {
        b.textContent = count;
        b.style.display = count > 0 ? 'flex' : 'none';
    });
}

// --- CART функции ---
function renderCartItems() {
    fetch('ajax_get_cart.php')
        .then(response => response.json())
        .then(data => {
            const cartItemsContainer = document.getElementById('cartItems');
            const cartTotalElement = document.getElementById('cartTotal');
            
            if (!cartItemsContainer) return;
            
            if (data.items.length === 0) {
                cartItemsContainer.innerHTML = '<p style="text-align: center; color: #666;">Количката е празна</p>';
                if (cartTotalElement) cartTotalElement.textContent = '0.00 €';
                return;
            }
            
            let total = 0;
            cartItemsContainer.innerHTML = '';
            
            data.items.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
            const itemElement = document.createElement('div');
itemElement.className = 'cart-item';
itemElement.style.cssText = `
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-bottom: 1px solid #edf2f7;
    transition: background-color 0.2s;
    font-size: 0.95rem;
`;
itemElement.innerHTML = `
    <img src="${item.image_url}" alt="${item.name}" 
         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; flex-shrink: 0;">
    
    <div style="flex: 1; min-width: 0;">
        <h4 style="margin: 0 0 4px 0; color: #2d3748; font-size: 0.95rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            ${item.name}
        </h4>
        
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="color: #718096; font-size: 0.9rem; white-space: nowrap;">
                    ${parseFloat(item.price).toFixed(2)} €
                </span>
                
                <span style="color: #ff6b6b;">×</span>
                
                <div style="display: flex; align-items: center; gap: 4px; background: #f7fafc; padding: 2px; border-radius: 30px; border: 1px solid #e2e8f0;">
                    
                    <button type="button" onclick="updateCartQuantity(${item.id}, 'decrease')" 
                            style="width: 24px; height: 24px; border: none; border-radius: 50%; background: #fff; color: #ff6b6b; cursor: pointer; font-weight: bold; font-size: 1rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s; line-height: 1; border: 1px solid #e2e8f0;"
                            onmouseover="this.style.background='#fff5f5'; this.style.color='#e53e3e'; this.style.borderColor='#feb2b2'" 
                            onmouseout="this.style.background='#fff'; this.style.color='#ff6b6b'; this.style.borderColor='#e2e8f0'">
                        −
                    </button>
                    
                    <span style="min-width: 18px; text-align: center; font-size: 0.9rem; font-weight: 600; color: #2d3748;">
                        ${item.quantity}
                    </span>
                    
                    <button type="button" onclick="updateCartQuantity(${item.id}, 'increase')" 
                            style="width: 24px; height: 24px; border: none; border-radius: 50%; background: #fff; color: #ff6b6b; cursor: pointer; font-weight: bold; font-size: 1rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s; line-height: 1; border: 1px solid #e2e8f0;"
                            onmouseover="this.style.background='#f0fff4'; this.style.color='#38a169'; this.style.borderColor='#9ae6b4'" 
                            onmouseout="this.style.background='#fff'; this.style.color='#ff6b6b'; this.style.borderColor='#e2e8f0'">
                        +
                    </button>
                    
                </div>
            </div>
            
            <span style="font-weight: 600; color: #2c5282; font-size: 1rem; white-space: nowrap; margin-left: 4px;">
                ${itemTotal.toFixed(2)} €
            </span>
        </div>
    </div>
    
    <button type="button" onclick="removeFromCart(${item.id})" 
            style="background: none; border: none; color: #ff6b6b; cursor: pointer; padding: 6px; font-size: 1rem; transition: all 0.2s; border-radius: 50%; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; flex-shrink: 0;"
            onmouseover="this.style.background='#fff5f5'; this.style.color='#e53e3e'" 
            onmouseout="this.style.background='none'; this.style.color='#ff6b6b'">
        <i class="fas fa-trash-alt" style="font-size: 0.9rem;"></i>
    </button>
`;
                cartItemsContainer.appendChild(itemElement);
            });
            
            if (cartTotalElement) {
                cartTotalElement.textContent = `${total.toFixed(2)} €`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Резервен вариант с localStorage
            renderCartItemsLocal();
        });
}
function updateCartQuantity(productId, action) {
    fetch('ajax_update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&action=' + action
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count); // Обновяваме бройката в иконката
            renderCartItems(); // Презареждаме списъка в количката, за да видим новите цени и количества
        } else {
            showNotification(data.message || 'Грешка при обновяване на количеството');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Грешка при връзка със сървъра!');
    });
}

// --- MODAL функции ---
function openCart() {
    renderCartItems();
    openModal('cartModal');
}

function openAdopt(animalName) {
    const modal = document.getElementById('adoptionModal');
    if (modal) {
        document.getElementById('animalNameInput').value = animalName;
        openModal('adoptionModal');
    }
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.body.style.paddingRight = '15px'; 
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        document.body.style.paddingRight = '0';
    }
}

// --- меню & навигация ---
function setupMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            hamburger.innerHTML = navLinks.classList.contains('active') 
                ? '<i class="fas fa-times"></i>' 
                : '<i class="fas fa-bars"></i>';
        });
    }
}

// --- извесия ---
function showNotification(message) {
    // Проверяваме дали вече има notification
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: var(--primary-color);
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 3000;
        animation: slideIn 0.3s ease;
        max-width: 300px;
        word-wrap: break-word;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

if (!document.querySelector('#notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// --- DONATION функции ---
function setDonationAmount(amount) {
    const input = document.getElementById('donationAmount');
    if (input) {
        input.value = amount;
    }
}

function quickDonate(amount) {
    setDonationAmount(amount);
    openModal('donationModal');
}

// --- инициализация ---
document.addEventListener("DOMContentLoaded", () => {
    setupMobileMenu();
    setupActiveLink();
    setupScrollAnimation();
    setupModals();
    setupPaymentOptions();
    
    // Инициализиране на брояча на количката при зареждане
    updateCartBadgeOnLoad();
});

function updateCartBadgeOnLoad() {
    fetch('ajax_get_cart.php')
        .then(response => response.json())
        .then(data => {
            updateCartBadge(data.count);
        })
        .catch(error => {
            console.error('Error loading cart count:', error);
            // Резервен вариант
            const cartCount = getCartCountLocal();
            updateCartBadge(cartCount);
        });
}

function setupActiveLink() {
    const path = window.location.pathname.split("/").pop() || 'index.php';
    document.querySelectorAll('.nav-links a').forEach(link => {
        if (link.getAttribute('href') === path) {
            link.classList.add('active');
        }
    });
}

function setupScrollAnimation() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
}

function setupModals() {
    // Adoption modal
    const adoptionModal = document.getElementById('adoptionModal');
    if (adoptionModal) {
        adoptionModal.addEventListener('click', (e) => {
            if (e.target === adoptionModal || e.target.classList.contains('close-modal')) {
                closeModal('adoptionModal');
            }
        });
        
        // Затваряне с ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && adoptionModal.style.display === 'flex') {
                closeModal('adoptionModal');
            }
        });
    }
    
    // Cart modal
    const cartModal = document.getElementById('cartModal');
    if (cartModal) {
        cartModal.addEventListener('click', (e) => {
            if (e.target === cartModal || e.target.classList.contains('close-modal')) {
                closeModal('cartModal');
            }
        });
        
        // Затваряне с ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && cartModal.style.display === 'flex') {
                closeModal('cartModal');
            }
        });
    }
    
    // Donation modal
    const donationModal = document.getElementById('donationModal');
    if (donationModal) {
        donationModal.addEventListener('click', (e) => {
            if (e.target === donationModal || e.target.classList.contains('close-modal')) {
                closeModal('donationModal');
            }
        });
        
        // Затваряне с ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && donationModal.style.display === 'flex') {
                closeModal('donationModal');
            }
        });
    }
}

function setupPaymentOptions() {
    const paymentOptions = document.querySelectorAll('.payment-option');
    paymentOptions.forEach(option => {
        option.addEventListener('click', () => {
            paymentOptions.forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
        });
    });
    
    if (paymentOptions.length > 0) {
        paymentOptions[0].click();
    }
}

// --- РЕЗЕРВНИ ФУНКЦИИ С LOCALSTORAGE (за тестване) ---
function addToCartLocal(productId, productName, price, img) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: productId,
            name: productName,
            price: price,
            img: img,
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    updateCartBadge(cartCount);
    showNotification(`${productName} е добавен в количката!`);
}

function removeFromCartLocal(productId) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart = cart.filter(item => item.id !== productId);
    localStorage.setItem('cart', JSON.stringify(cart));
    
    const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    updateCartBadge(cartCount);
}

function getCartCountLocal() {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    return cart.reduce((sum, item) => sum + item.quantity, 0);
}

function renderCartItemsLocal() {
    const cartItemsContainer = document.getElementById('cartItems');
    const cartTotalElement = document.getElementById('cartTotal');
    
    if (!cartItemsContainer) return;
    
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = '<p style="text-align: center; color: #666;">Количката е празна</p>';
        if (cartTotalElement) cartTotalElement.textContent = '0.00 €';
        return;
    }
    
    let total = 0;
    cartItemsContainer.innerHTML = '';
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        const itemElement = document.createElement('div');
        itemElement.className = 'cart-item';
        itemElement.style.cssText = 'display: flex; align-items: center; gap: 15px; padding: 15px; border-bottom: 1px solid #eee;';
        itemElement.innerHTML = `
            <img src="${item.img}" alt="${item.name}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
            <div style="flex: 1;">
                <h4 style="margin: 0; color: var(--secondary-color);">${item.name}</h4>
                <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                    <span>${item.price.toFixed(2)} € × ${item.quantity}</span>
                    <span style="font-weight: bold; color: var(--primary-color);">${itemTotal.toFixed(2)} €</span>
                </div>
            </div>
            <button onclick="removeFromCart(${item.id})" style="background: none; border: none; color: #ff6b6b; cursor: pointer; padding: 5px; font-size: 1.2rem;">
                <i class="fas fa-trash"></i>
            </button>
        `;
        cartItemsContainer.appendChild(itemElement);
    });
    
    if (cartTotalElement) {
        cartTotalElement.textContent = `${total.toFixed(2)} €`;
    }
}

// --- FILTER функции ---
function filterAnimals(type) {
    window.location.href = 'animals.php?filter=' + type;
}
