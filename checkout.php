<?php
// checkout.php
require_once 'includes/db_connect.php'; // Use the new connection file

// Redirect if not logged in as a Customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Customer') {
    header('location:login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_id = null;
$cart_items = [];
$cart_subtotal = 0; // Renamed from cart_total for clarity (before shipping/tax)
$message = '';
$customer_details = [];
$shipping_types = [];
$states_taxes = [];

// Fetch customer details for pre-filling form
$stmt_customer = $conn->prepare("SELECT u.First_Name, u.Last_Name, u.Email_Address, u.Phone_Number, c.Address, c.City, c.Zip, c.State
                                FROM USERS u JOIN CUSTOMERS c ON u.UserID = c.UserID WHERE u.UserID = ?");
if ($stmt_customer) {
    $stmt_customer->bind_param("i", $user_id);
    $stmt_customer->execute();
    $result_customer = $stmt_customer->get_result();
    if ($result_customer->num_rows > 0) {
        $customer_details = $result_customer->fetch_assoc();
    }
    $stmt_customer->close();
}

// Get user's cart ID
$stmt = $conn->prepare("SELECT CartID FROM SHOPPINGCARTS WHERE UserID = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $cart_id = $result->fetch_assoc()['CartID'];
    }
    $stmt->close();
}

if (!$cart_id) {
    $message = '<div class="alert alert-warning">Your cart is empty. Cannot proceed to checkout.</div>';
} else {
    // Fetch cart items
    $stmt_items = $conn->prepare("SELECT sci.CartItemID, p.ProductID, p.Product_Name, p.Price, sci.Quantity, p.ProductType
                                FROM SHOPPINGCARTITEMS sci
                                JOIN PRODUCTS p ON sci.ProductID = p.ProductID
                                WHERE sci.CartID = ?");
    if ($stmt_items) {
        $stmt_items->bind_param("i", $cart_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while ($row = $result_items->fetch_assoc()) {
            $cart_items[] = $row;
            $cart_subtotal += ($row['Price'] * $row['Quantity']);
        }
        $stmt_items->close();
    }

    if (empty($cart_items)) {
        $message = '<div class="alert alert-warning">Your cart is empty. <a href="products.php">Start shopping!</a></div>';
    }
}

// Fetch shipping types
$stmt_shipping = $conn->query("SELECT * FROM SHIPPINGTYPES ORDER BY Price ASC");
if ($stmt_shipping) {
    while ($row = $stmt_shipping->fetch_assoc()) {
        $shipping_types[] = $row;
    }
}

// Fetch states and taxes
$stmt_states = $conn->query("SELECT State_Name, Sales_Tax_Rate FROM STATETAXES ORDER BY State_Name ASC");
if ($stmt_states) {
    while ($row = $stmt_states->fetch_assoc()) {
        $states_taxes[$row['State_Name']] = $row['Sales_Tax_Rate'];
    }
}

// Fetch customer's payment methods
$customer_payment_methods = [];
$stmt_pm = $conn->prepare("SELECT PaymentMethodID, MethodType FROM PAYMENTMETHODS WHERE UserID = ?");
if ($stmt_pm) {
    $stmt_pm->bind_param("i", $user_id);
    $stmt_pm->execute();
    $result_pm = $stmt_pm->get_result();
    while ($row = $result_pm->fetch_assoc()) {
        $customer_payment_methods[] = $row;
    }
    $stmt_pm->close();
}

// Handle Order Placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (empty($cart_items)) {
        $_SESSION['message'] = 'Your cart is empty. Cannot place order.';
        $_SESSION['message_type'] = 'danger';
        header('location:checkout.php');
        exit();
    }

    $receiver_name = sanitize_input($_POST['receiver_name']);
    $shipping_address = sanitize_input($_POST['shipping_address']);
    $shipping_city = sanitize_input($_POST['shipping_city']);
    $shipping_zip = sanitize_input($_POST['shipping_zip']);
    $shipping_state = sanitize_input($_POST['shipping_state']);
    $shipping_type_id = sanitize_input($_POST['shipping_type']);
    $payment_method_id = sanitize_input($_POST['payment_method']);

    // Basic validation
    if (empty($receiver_name) || empty($shipping_address) || empty($shipping_city) || empty($shipping_zip) || empty($shipping_state) || empty($shipping_type_id) || empty($payment_method_id)) {
        $_SESSION['message'] = 'Please fill in all required shipping and payment details.';
        $_SESSION['message_type'] = 'danger';
        header('location:checkout.php');
        exit();
    }

    // Start a transaction for atomicity
    $conn->begin_transaction();
    try {
        // 1. Insert into ORDERS table
        $stmt_order = $conn->prepare("INSERT INTO ORDERS (UserID, Receiver_Name, Shipping_Address, Shipping_City, Shipping_Zip, Shipping_State, ShippingTypeID, Date_of_Purchase, Order_Status) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Pending')");
        if (!$stmt_order) throw new Exception("Prepare order failed: " . $conn->error);
        $stmt_order->bind_param("issssssi", $user_id, $receiver_name, $shipping_address, $shipping_city, $shipping_zip, $shipping_state, $shipping_type_id);
        if (!$stmt_order->execute()) throw new Exception("Execute order failed: " . $stmt_order->error);
        $order_id = $stmt_order->insert_id;
        $stmt_order->close();

        // 2. Move items from SHOPPINGCARTITEMS to ORDERITEMS and update product stock
        foreach ($cart_items as $item) {
            $product_id = $item['ProductID'];
            $quantity = $item['Quantity'];
            $price_at_purchase = $item['Price']; // Use current price from cart

            // Check product type and stock for physical books before finalizing
            $stmt_product_type = $conn->prepare("SELECT ProductType, Nr_books FROM PRODUCTS WHERE ProductID = ?");
            if (!$stmt_product_type) throw new Exception("Prepare product type check failed: " . $conn->error);
            $stmt_product_type->bind_param("i", $product_id);
            $stmt_product_type->execute();
            $product_res = $stmt_product_type->get_result();
            $product_data = $product_res->fetch_assoc();
            $stmt_product_type->close();

            if ($product_data['ProductType'] === 'Physical Book') {
                if ($product_data['Nr_books'] < $quantity) {
                    throw new Exception("Not enough stock for " . htmlspecialchars($item['Product_Name']) . ". Available: " . $product_data['Nr_books']);
                }
                // Decrease stock for physical books
                $stmt_stock_update = $conn->prepare("UPDATE PRODUCTS SET Nr_books = Nr_books - ? WHERE ProductID = ? AND ProductType = 'Physical Book'");
                if (!$stmt_stock_update) throw new Exception("Prepare stock update failed: " . $conn->error);
                $stmt_stock_update->bind_param("ii", $quantity, $product_id);
                if (!$stmt_stock_update->execute()) throw new Exception("Execute stock update failed: " . $stmt_stock_update->error);
                $stmt_stock_update->close();
            }

            $stmt_order_item = $conn->prepare("INSERT INTO ORDERITEMS (OrderID, ProductID, Quantity, Price_at_Purchase) VALUES (?, ?, ?, ?)");
            if (!$stmt_order_item) throw new Exception("Prepare order item failed: " . $conn->error);
            $stmt_order_item->bind_param("iiid", $order_id, $product_id, $quantity, $price_at_purchase);
            if (!$stmt_order_item->execute()) throw new Exception("Execute order item failed: " . $stmt_order_item->error);
            $stmt_order_item->close();
        }

        // 3. Clear the shopping cart
        $stmt_clear_cart = $conn->prepare("DELETE FROM SHOPPINGCARTITEMS WHERE CartID = ?");
        if (!$stmt_clear_cart) throw new Exception("Prepare clear cart failed: " . $conn->error);
        $stmt_clear_cart->bind_param("i", $cart_id);
        if (!$stmt_clear_cart->execute()) throw new Exception("Execute clear cart failed: " . $stmt_clear_cart->error);
        $stmt_clear_cart->close();

        // Commit the transaction
        $conn->commit();
        $_SESSION['message'] = 'Order placed successfully! Your Order ID is: <strong>' . $order_id . '</strong>.';
        $_SESSION['message_type'] = 'success';
        header('location:my_account.php'); // Redirect to my_account or order confirmation page
        exit();
    } catch (Exception $e) {
        $conn->rollback(); // Rollback on error
        $_SESSION['message'] = 'Error placing order: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header('location:checkout.php'); // Redirect back to checkout with error
        exit();
    }
}

require_once 'includes/header.php'; // Use the new header
?>

<div class="heading text-center my-4">
   <h3>checkout</h3>
   <p> <a href="index.php" class="text-decoration-none">home</a> / checkout </p>
</div>

<section class="checkout py-5">
   <?php if (empty($cart_items)): ?>
       <div class="alert alert-info text-center">Your cart is empty. <a href="products.php">Start shopping!</a></div>
   <?php else: ?>
       <div class="row">
           <div class="col-md-7">
               <div class="card shadow-sm mb-4">
                   <div class="card-header bg-primary text-white">
                       Order Summary
                   </div>
                   <div class="card-body">
                       <ul class="list-group mb-3">
                           <?php foreach ($cart_items as $item): ?>
                               <li class="list-group-item d-flex justify-content-between lh-sm">
                                   <div>
                                       <h6 class="my-0"><?php echo htmlspecialchars($item['Product_Name']); ?> (<?php echo htmlspecialchars($item['ProductType']); ?>)</h6>
                                       <small class="text-muted">Quantity: <?php echo htmlspecialchars($item['Quantity']); ?></small>
                                   </div>
                                   <span class="text-muted">$<?php echo htmlspecialchars(number_format($item['Price'] * $item['Quantity'], 2)); ?></span>
                               </li>
                           <?php endforeach; ?>
                           <li class="list-group-item d-flex justify-content-between">
                               <span>Items Subtotal</span>
                               <strong>$<?php echo htmlspecialchars(number_format($cart_subtotal, 2)); ?></strong>
                           </li>
                       </ul>
                   </div>
               </div>

               <div class="card shadow-sm mb-4">
                   <div class="card-header bg-info text-white">
                       Shipping & Payment Information
                   </div>
                   <div class="card-body">
                       <form id="checkoutForm" action="checkout.php" method="POST">
                           <div class="mb-3">
                               <label for="receiver_name" class="form-label">Receiver's Name</label>
                               <input type="text" class="form-control" id="receiver_name" name="receiver_name" value="<?php echo htmlspecialchars($customer_details['First_Name'] . ' ' . $customer_details['Last_Name'] ?? ''); ?>" required>
                           </div>
                           <div class="mb-3">
                               <label for="shipping_address" class="form-label">Address</label>
                               <input type="text" class="form-control" id="shipping_address" name="shipping_address" value="<?php echo htmlspecialchars($customer_details['Address'] ?? ''); ?>" required>
                           </div>
                           <div class="row">
                               <div class="col-md-4 mb-3">
                                   <label for="shipping_city" class="form-label">City</label>
                                   <input type="text" class="form-control" id="shipping_city" name="shipping_city" value="<?php echo htmlspecialchars($customer_details['City'] ?? ''); ?>" required>
                               </div>
                               <div class="col-md-4 mb-3">
                                   <label for="shipping_zip" class="form-label">Zip</label>
                                   <input type="text" class="form-control" id="shipping_zip" name="shipping_zip" value="<?php echo htmlspecialchars($customer_details['Zip'] ?? ''); ?>" required>
                               </div>
                               <div class="col-md-4 mb-3">
                                   <label for="shipping_state" class="form-label">State</label>
                                   <select class="form-select" id="shipping_state" name="shipping_state" required>
                                       <option value="">Choose...</option>
                                       <?php foreach ($states_taxes as $state_name => $rate): ?>
                                           <option value="<?php echo htmlspecialchars($state_name); ?>" <?php echo (isset($customer_details['State']) && $customer_details['State'] === $state_name) ? 'selected' : ''; ?>>
                                               <?php echo htmlspecialchars($state_name); ?> (Tax: <?php echo number_format($rate * 100, 2); ?>%)
                                           </option>
                                       <?php endforeach; ?>
                                   </select>
                               </div>
                           </div>
                           <div class="mb-3">
                               <label for="shipping_type" class="form-label">Shipping Type</label>
                               <select class="form-select" id="shipping_type" name="shipping_type" required>
                                   <option value="">Choose...</option>
                                   <?php foreach ($shipping_types as $type): ?>
                                       <option value="<?php echo htmlspecialchars($type['ShippingTypeID']); ?>" data-price="<?php echo htmlspecialchars($type['Price']); ?>">
                                           <?php echo htmlspecialchars($type['Type_Name']); ?> ($<?php echo htmlspecialchars(number_format($type['Price'], 2)); ?>)
                                       </option>
                                   <?php endforeach; ?>
                               </select>
                           </div>

                           <div class="mb-3">
                               <label for="payment_method" class="form-label">Payment Method</label>
                               <select class="form-select" id="payment_method" name="payment_method" required>
                                   <option value="">Choose...</option>
                                   <?php if (!empty($customer_payment_methods)): ?>
                                       <?php foreach ($customer_payment_methods as $pm): ?>
                                           <option value="<?php echo htmlspecialchars($pm['PaymentMethodID']); ?>">
                                               <?php echo htmlspecialchars($pm['MethodType']); ?>
                                           </option>
                                       <?php endforeach; ?>
                                   <?php else: ?>
                                       <option value="" disabled>No payment methods found. Please add one in My Account.</option>
                                   <?php endif; ?>
                               </select>
                               <small class="text-muted">You can manage payment methods in your <a href="my_account.php">My Account</a> section.</small>
                           </div>

                           <div class="card shadow-sm mt-4">
                               <div class="card-header bg-light">
                                   <h5>Final Order Total</h5>
                               </div>
                               <div class="card-body">
                                   <ul class="list-group list-group-flush">
                                       <li class="list-group-item d-flex justify-content-between">
                                           <span>Items Subtotal</span>
                                           <span id="items-subtotal">$<?php echo htmlspecialchars(number_format($cart_subtotal, 2)); ?></span>
                                       </li>
                                       <li class="list-group-item d-flex justify-content-between">
                                           <span>Shipping Cost</span>
                                           <span id="shipping-cost">$0.00</span>
                                       </li>
                                       <li class="list-group-item d-flex justify-content-between">
                                           <span>Sales Tax (<span id="tax-rate-display">0.00%</span>)</span>
                                           <span id="sales-tax">$0.00</span>
                                       </li>
                                       <li class="list-group-item d-flex justify-content-between fw-bold">
                                           <span>Order Total</span>
                                           <span id="order-final-total">$<?php echo htmlspecialchars(number_format($cart_subtotal, 2)); ?></span>
                                       </li>
                                   </ul>
                               </div>
                           </div>

                           <button type="submit" name="place_order" class="btn btn-success btn-lg w-100 mt-4">Place Order</button>
                       </form>
                   </div>
               </div>
           </div>
       </div>

       <script>
           // JavaScript for dynamic total calculation on checkout page
           $(document).ready(function() {
               const cartSubtotal = <?php echo json_encode($cart_subtotal); ?>;
               const statesTaxes = <?php echo json_encode($states_taxes); ?>;

               function calculateOrderTotal() {
                   let currentShippingCost = 0;
                   let currentTaxRate = 0;

                   const selectedShippingType = $('#shipping_type option:selected');
                   if (selectedShippingType.val()) {
                       currentShippingCost = parseFloat(selectedShippingType.data('price'));
                   }

                   const selectedState = $('#shipping_state option:selected').val();
                   if (selectedState && statesTaxes[selectedState]) {
                       currentTaxRate = parseFloat(statesTaxes[selectedState]);
                   }

                   const taxableAmount = cartSubtotal + currentShippingCost;
                   const salesTax = taxableAmount * currentTaxRate;
                   const finalTotal = taxableAmount + salesTax;

                   $('#items-subtotal').text('$' + cartSubtotal.toFixed(2));
                   $('#shipping-cost').text('$' + currentShippingCost.toFixed(2));
                   $('#sales-tax').text('$' + salesTax.toFixed(2));
                   $('#tax-rate-display').text((currentTaxRate * 100).toFixed(2) + '%');
                   $('#order-final-total').text('$' + finalTotal.toFixed(2));
               }

               // Attach change listeners
               $('#shipping_type, #shipping_state').on('change', calculateOrderTotal);

               // Initial calculation on page load
               calculateOrderTotal();
           });
       </script>

   <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
