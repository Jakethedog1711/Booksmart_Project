// assets/js/admin_script.js
// This script handles admin panel specific UI interactions.

document.addEventListener('DOMContentLoaded', function() {

    // --- Header / Navbar Toggles (from your original admin_script.js) ---
    // Note: If using Bootstrap's default navbar-toggler, some of this might be redundant.
    // Ensure your HTML has #menu-btn and .account-box if you want this JS to control them.

    let navbar = document.querySelector('.header .navbar'); // This refers to the main admin navbar
    let accountBox = document.querySelector('.header .account-box'); // The user dropdown box

    // Toggle main admin navbar (if custom, otherwise Bootstrap handles it)
    const menuBtn = document.querySelector('#menu-btn');
    if (menuBtn) {
        menuBtn.onclick = () => {
            if (navbar) {
                navbar.classList.toggle('active');
            }
            if (accountBox) {
                accountBox.classList.remove('active'); // Close account box if navbar opens
            }
        };
    }

    // Toggle admin account box on user-btn click
    const userBtn = document.querySelector('#user-btn');
    if (userBtn) {
        userBtn.onclick = () => {
            if (accountBox) {
                accountBox.classList.toggle('active');
            }
            if (navbar) {
                navbar.classList.remove('active'); // Close navbar if account box opens
            }
        };
    }

    // Close both on scroll
    window.onscroll = () => {
        if (navbar) {
            navbar.classList.remove('active');
        }
        if (accountBox) {
            accountBox.classList.remove('active');
        }
    };

    // --- Specific Admin Product Form Interactions ---

    // Close update product form button (on admin_products.php)
    const closeUpdateBtn = document.querySelector('#close-update');
    if (closeUpdateBtn) {
        closeUpdateBtn.onclick = () => {
            const editProductForm = document.querySelector('.edit-product-form');
            if (editProductForm) {
                editProductForm.style.display = 'none';
            }
            window.location.href = 'admin_products.php'; // Redirect to clear URL parameters
        };
    }

    // Function to toggle product-specific fields in add/edit forms (on admin_products.php)
    // This function is defined in admin_products.php directly, but if you want it here,
    // you'd need to ensure the elements are accessible or pass IDs.
    // For now, it's best to keep it in admin_products.php as it's tightly coupled to its form.
    // If you move it here, ensure it's called on DOMContentLoaded and on change events.
    // Example call: toggleProductFields('product_type', 'physical_book_fields', 'ebook_fields', 'audiobook_fields');
});
