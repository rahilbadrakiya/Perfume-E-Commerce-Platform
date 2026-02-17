// Product Data (In-memory, but should come from DB/DOM in real implementation)
// For page-to-page persistence we will use localStorage

// Init Cart from LocalStorage
// Init Cart from LocalStorage
let cart = [];
try {
    const saved = localStorage.getItem('km_cart');
    let loaded = saved ? JSON.parse(saved) : [];
    if (!Array.isArray(loaded)) loaded = [];

    // Self-Cleaning: Filter out invalid/corrupted items (undefined, no name, no price)
    cart = loaded.filter(item => item && item.name && item.price != null && (item.productId || item.id));

    // If corruption was found and fixed, save the clean version immediately
    if (cart.length !== loaded.length) {
        console.log("Cleaned corrupted cart items.");
        localStorage.setItem('km_cart', JSON.stringify(cart));
    }
} catch (e) {
    console.error("Cart corrupted, resetting.", e);
    cart = [];
    localStorage.removeItem('km_cart');
}

const WHATSAPP_NUMBER = "917030005453";

// UI Elements
const cartOverlay = document.getElementById('cartOverlay');
const cartItemsContainer = document.getElementById('cartItems');
const cartTotalElement = document.getElementById('cartTotal');
const cartCountElements = document.querySelectorAll('.cart-count');

// Initialize UI on load
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();

    // Also Populate Checkout if present
    const checkoutItemsDiv = document.getElementById('checkoutItems');
    const checkoutTotalSpan = document.getElementById('checkoutTotal');

    if (checkoutItemsDiv && checkoutTotalSpan) {
        renderCheckout(checkoutItemsDiv, checkoutTotalSpan);
    }
});

function toggleCart() {
    if (cartOverlay) cartOverlay.classList.toggle('open');
}

// Toast Notification
function showToast(message) {
    // Create toast container if not exists
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.position = 'fixed';
        toastContainer.style.bottom = '20px';
        toastContainer.style.left = '50%';
        toastContainer.style.transform = 'translateX(-50%)';
        toastContainer.style.zIndex = '10000';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.background = '#000';
    toast.style.color = '#fff';
    toast.style.padding = '12px 24px';
    toast.style.borderRadius = '4px';
    toast.style.marginBottom = '10px';
    toast.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s ease';

    toastContainer.appendChild(toast);

    // Fade in
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
    });

    // Remove after 3s
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// Updated addToCart signature to take data directly
function addToCart(id, name, price, img, size = '50ml') {
    const cartItemId = `${id}-${size}`;

    const existingItem = cart.find(item => item.cartId === cartItemId);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            cartId: cartItemId,
            productId: id,
            name: name,
            img: img,
            size: size,
            price: price,
            quantity: 1
        });
    }

    saveCart();
    updateCartUI();

    // Show feedback
    showToast(`${name} (${size}) added to cart!`);

    // Auto-open cart drawer for better visibility
    if (!cartOverlay.classList.contains('open')) {
        toggleCart();
    }
}

function adjustQuantity(cartItemId, change) {
    const itemIndex = cart.findIndex(item => item.cartId === cartItemId);
    if (itemIndex > -1) {
        cart[itemIndex].quantity += change;
        if (cart[itemIndex].quantity <= 0) {
            cart.splice(itemIndex, 1);
        }
        saveCart();
        updateCartUI();

        // Update Checkout if on page
        const checkoutItemsDiv = document.getElementById('checkoutItems');
        const checkoutTotalSpan = document.getElementById('checkoutTotal');
        if (checkoutItemsDiv) renderCheckout(checkoutItemsDiv, checkoutTotalSpan);
    }
}

function saveCart() {
    localStorage.setItem('km_cart', JSON.stringify(cart));
}

function updateCartUI() {
    const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCountElements.forEach(el => el.textContent = totalCount);

    if (!cartItemsContainer) return;

    cartItemsContainer.innerHTML = '';

    if (cart.length === 0) {
        cartItemsContainer.innerHTML = '<p style="text-align:center; margin-top:20px; color:#888;">Your cart is empty.</p>';
        if (cartTotalElement) cartTotalElement.textContent = '₹0';
        return;
    }

    let totalAmount = 0;

    cart.forEach(item => {
        // Safe casting
        const price = Number(item.price) || 0;
        const qty = Number(item.quantity) || 1;

        totalAmount += price * qty;

        const itemEl = document.createElement('div');
        itemEl.className = 'cart-item';
        itemEl.innerHTML = `
            <img src="${item.img || 'assets/images/placeholder.jpg'}" alt="${item.name}">
            <div class="item-details" style="flex:1;">
                <h4>${item.name}</h4>
                <span class="size-label">Size: ${item.size}</span>
                <span class="item-price">₹${price.toLocaleString('en-IN')}</span>
                <div class="item-controls">
                    <button class="qty-btn" onclick="adjustQuantity('${item.cartId}', -1)">-</button>
                    <span>${qty}</span>
                    <button class="qty-btn" onclick="adjustQuantity('${item.cartId}', 1)">+</button>
                </div>
            </div>
        `;
        cartItemsContainer.appendChild(itemEl);
    });

    if (cartTotalElement) cartTotalElement.textContent = '₹' + totalAmount.toLocaleString('en-IN');
}

function renderCheckout(container, totalEl) {
    container.innerHTML = '';
    let total = 0;

    if (cart.length === 0) {
        container.innerHTML = "<p>Your cart is empty.</p>";
        totalEl.textContent = "₹0";
        return;
    }

    cart.forEach(item => {
        total += item.price * item.quantity;
        const div = document.createElement('div');
        div.style.display = 'flex';
        div.style.justifyContent = 'space-between';
        div.style.marginBottom = '10px';
        div.innerHTML = `
            <span>${item.name} x${item.quantity}</span>
            <span>₹${(item.price * item.quantity).toLocaleString()}</span>
        `;
        container.appendChild(div);
    });

    totalEl.textContent = "₹" + total.toLocaleString('en-IN');
}

function checkoutWhatsApp() {
    if (cart.length === 0) {
        alert("Your cart is empty!");
        return;
    }

    // Grab Details
    const name = document.getElementById('name')?.value || "Guest";
    const address = document.getElementById('address')?.value || "N/A";

    let message = `New Order from *${name}*:%0A%0A`;
    let total = 0;

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        message += `- ${item.name} (${item.size}) x${item.quantity}: ₹${itemTotal.toLocaleString()}%0A`;
    });

    message += `%0A*Total: ₹${total.toLocaleString()}*`;
    message += `%0A%0AAddress: ${address}`;
    message += "%0A%0APlease confirm.";

    const url = `https://wa.me/${WHATSAPP_NUMBER}?text=${message}`;
    window.open(url, '_blank');
}

function toggleMobileMenu() {
    const nav = document.getElementById('mobileNav');
    nav.classList.toggle('active');
}
