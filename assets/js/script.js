// assets/js/script.js
// This script combines general UI interactions with AJAX calls for cart functionality.

$(document).ready(function() {

    // --- Header / Navbar Toggles (from your original script.js) ---
    // Note: Bootstrap's navbar toggler handles the main menu.
    // This part is for the user-box dropdown if it's a custom element.
    let userBox = document.querySelector('.header .header-2 .user-box');
    let navbar = document.querySelector('.header .header-2 .navbar'); // Main navbar element

    // Toggle user box on user-btn click
    document.querySelector('#user-btn').onclick = () => {
        if (userBox) { // Check if userBox exists (it might not if user is logged out)
            userBox.classList.toggle('active');
        }
        if (navbar) {
            navbar.classList.remove('active'); // Close main navbar if open
        }
    };

    // Toggle main navbar on menu-btn click (Bootstrap handles this usually, but keeping for custom)
    // Note: If you are using Bootstrap's default navbar-toggler, this might be redundant.
    // Ensure your HTML has #menu-btn if you want this JS to control it.
    const menuBtn = document.querySelector('#menu-btn');
    if (menuBtn) {
        menuBtn.onclick = () => {
            if (navbar) {
                navbar.classList.toggle('active');
            }
            if (userBox) {
                userBox.classList.remove('active'); // Close user box if open
            }
        };
    }


    // Close user box and navbar on scroll
    window.onscroll = () => {
        if (userBox) {
            userBox.classList.remove('active');
        }
        if (navbar) {
            navbar.classList.remove('active');
        }
        // Add/remove 'active' class to header-2 for sticky effect (from your original script.js)
        if (document.querySelector('.header .header-2')) {
            if (window.scrollY > 60) {
                document.querySelector('.header .header-2').classList.add('active');
            } else {
                document.querySelector('.header .header-2').classList.remove('active');
            }
        }
    };

    // --- AJAX Cart Operations (from my previous guidance) ---

    // Function to update cart item count in navbar
    function updateCartItemCount() {
        // Only fetch if a user is logged in (cart icon is visible)
        if ($('#cart-item-count').length) {
            $.ajax({
                url: 'ajax_actions.php', // This file handles all AJAX requests
                type: 'POST',
                data: { action: 'get_cart_count' },
                success: function(response) {
                    $('#cart-item-count').text(response);
                },
                error: function() {
                    console.error('Error fetching cart count.');
                }
            });
        }
    }

    // Initial cart count update on page load
    updateCartItemCount();

    // Add to Cart button click handler (used on home.php, products.php, search_page.php, product_detail.php)
    $('.add-to-cart-btn').on('click', function() {
        const productId = $(this).data('product-id');
        const quantity = 1; // Default to 1, can be extended for quantity input on product pages

        $.ajax({
            url: 'ajax_actions.php',
            type: 'POST',
            data: {
                action: 'add_to_cart',
                product_id: productId,
                quantity: quantity
            },
            success: function(response) {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    // Use Bootstrap alert or custom modal for user feedback
                    alert(res.message); // For assignment, using alert. Replace with custom modal later.
                    updateCartItemCount(); // Update cart count in navbar
                } else {
                    alert(res.message); // For assignment, using alert. Replace with custom modal later.
                }
            },
            error: function() {
                alert('Error adding product to cart. Please try again.'); // Replace with custom modal
            }
        });
    });

    // Remove from Cart button click handler (on cart.php)
    $('.remove-from-cart-btn').on('click', function() {
        const cartItemId = $(this).data('cart-item-id');
        // Use a custom modal for confirmation instead of browser's confirm()
        if (confirm('Are you sure you want to remove this item from your cart?')) { // Replace with custom modal
            $.ajax({
                url: 'ajax_actions.php',
                type: 'POST',
                data: {
                    action: 'remove_from_cart',
                    cart_item_id: cartItemId
                },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        alert(res.message); // Replace with custom modal
                        location.reload(); // Reload page to update cart display
                    } else {
                        alert(res.message); // Replace with custom modal
                    }
                },
                error: function() {
                    alert('Error removing product from cart. Please try again.'); // Replace with custom modal
                }
            });
        }
    });

    // Update Cart Quantity buttons (+/-) (on cart.php)
    $('.update-cart-qty').on('click', function() {
        const cartItemId = $(this).data('cart-item-id');
        const action = $(this).data('action');
        const $input = $(this).siblings('.cart-qty-input');
        let currentQty = parseInt($input.val());

        if (action === 'increase') {
            currentQty++;
        } else if (action === 'decrease' && currentQty > 1) {
            currentQty--;
        } else {
            return; // Don't decrease below 1
        }

        // $input.val(currentQty); // Update input field immediately (optional, reload will do it)

        $.ajax({
            url: 'ajax_actions.php',
            type: 'POST',
            data: {
                action: 'update_cart_quantity',
                cart_item_id: cartItemId,
                quantity: currentQty
            },
            success: function(response) {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    location.reload(); // Reload page to update cart display and totals
                } else {
                    alert(res.message); // Replace with custom modal
                    // Revert quantity if update failed (requires storing original value)
                    // For simplicity, a reload on success/failure will reflect actual state.
                }
            },
            error: function() {
                alert('Error updating quantity. Please try again.'); // Replace with custom modal
            }
        });
    });

    // Update Cart Quantity on input change (e.g., direct typing on cart.php)
    $('.cart-qty-input').on('change', function() {
        const cartItemId = $(this).data('cart-item-id');
        let newQty = parseInt($(this).val());

        if (isNaN(newQty) || newQty < 1) {
            alert('Quantity must be a positive number.'); // Replace with custom modal
            $(this).val(1); // Reset to 1
            newQty = 1;
        }

        $.ajax({
            url: 'ajax_actions.php',
            type: 'POST',
            data: {
                action: 'update_cart_quantity',
                cart_item_id: cartItemId,
                quantity: newQty
            },
            success: function(response) {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    location.reload(); // Reload page to update cart display and totals
                } else {
                    alert(res.message); // Replace with custom modal
                    // Revert quantity if update failed
                }
            },
            error: function() {
                alert('Error updating quantity. Please try again.'); // Replace with custom modal
            }
        });
    });
});
