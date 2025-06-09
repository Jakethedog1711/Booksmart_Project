<?php
// cart.php
require_once 'includes/db_connect.php'; // Use the new connection file

// Redirect if not logged in as a Customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Customer') {
    header('location:login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_id = null;
$cart_items = [];
$grand_total = 0;

// Get user's cart ID (or create if it doesn't exist)
$stmt = $conn->prepare("SELECT CartID FROM SHOPPINGCARTS WHERE UserID = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $cart_id = $result->fetch_assoc()['CartID'];
    } else {
        // Create a cart for the user if one doesn't exist
        $stmt_create_cart = $conn->prepare("INSERT INTO SHOPPINGCARTS (UserID) VALUES (?)");
        if ($stmt_create_cart) {
            $stmt_create_cart->bind_param("i", $user_id);
            $stmt_create_cart->execute();
            $cart_id = $stmt_create_cart->insert_id;
            $stmt_create_cart->close();
        } else {
            $_SESSION['message'] = 'Error creating shopping cart: ' . $conn->error;
            $_SESSION['message_type'] = 'danger';
        }
    }
    $stmt->close();
}

if ($cart_id) {
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
            $grand_total += ($row['Price'] * $row['Quantity']);
        }
        $stmt_items->close();
    }
}

// Handle 'delete_all' action (from URL parameter)
if (isset($_GET['delete_all'])) {
    if ($cart_id) {
        $stmt_delete_all = $conn->prepare("DELETE FROM SHOPPINGCARTITEMS WHERE CartID = ?");
        if ($stmt_delete_all) {
            $stmt_delete_all->bind_param("i", $cart_id);
            if ($stmt_delete_all->execute()) {
                $_SESSION['message'] = 'All items removed from cart!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error clearing cart: ' . $stmt_delete_all->error;
                $_SESSION['message_type'] = 'danger';
            }
            $stmt_delete_all->close();
        }
    }
    header('location:cart.php'); // Redirect to refresh page
    exit();
}


require_once 'includes/header.php'; // Use the new header
?>

<div class="heading text-center my-4">
   <h3>shopping cart</h3>
   <p> <a href="index.php" class="text-decoration-none">home</a> / cart </p>
</div>

<section class="shopping-cart py-5">
   <h1 class="title text-center mb-4">Products Added</h1>

   <div class="box-container">
      <?php if (empty($cart_items)): ?>
         <p class="empty text-center alert alert-info">Your cart is empty</p>
      <?php else: ?>
         <div class="table-responsive">
            <table class="table table-bordered table-hover shadow-sm">
               <thead class="table-light">
                  <tr>
                     <th>Product</th>
                     <th>Type</th>
                     <th>Price</th>
                     <th>Quantity</th>
                     <th>Subtotal</th>
                     <th>Actions</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($cart_items as $item):
                     $sub_total = ($item['Quantity'] * $item['Price']);
                     $image_url = "https://placehold.co/100x100/E0E0E0/333333?text=" . urlencode($item['ProductType']);
                     // If you store image paths in PHYSICALBOOKS, you'd use that here:
                     // if ($item['ProductType'] === 'Physical Book' && !empty($item['Image_Path'])) {
                     //     $image_url = 'assets/img/uploaded_img/' . htmlspecialchars($item['Image_Path']);
                     // }
                  ?>
                  <tr>
                     <td>
                        <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($item['Product_Name']); ?>" class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                        <?php echo htmlspecialchars($item['Product_Name']); ?>
                     </td>
                     <td><?php echo htmlspecialchars($item['ProductType']); ?></td>
                     <td>$<?php echo htmlspecialchars(number_format($item['Price'], 2)); ?></td>
                     <td>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <button class="btn btn-outline-secondary update-cart-qty" data-cart-item-id="<?php echo $item['CartItemID']; ?>" data-action="decrease">-</button>
                            <input type="number" class="form-control text-center cart-qty-input" value="<?php echo $item['Quantity']; ?>" min="1" data-cart-item-id="<?php echo $item['CartItemID']; ?>">
                            <button class="btn btn-outline-secondary update-cart-qty" data-cart-item-id="<?php echo $item['CartItemID']; ?>" data-action="increase">+</button>
                        </div>
                     </td>
                     <td>$<?php echo htmlspecialchars(number_format($sub_total, 2)); ?></td>
                     <td>
                        <button class="btn btn-danger btn-sm remove-from-cart-btn" data-cart-item-id="<?php echo $item['CartItemID']; ?>"><i class="fas fa-trash-alt"></i> Remove</button>
                     </td>
                  </tr>
                  <?php endforeach; ?>
               </tbody>
               <tfoot>
                  <tr>
                     <th colspan="4" class="text-end">Grand Total:</th>
                     <th colspan="2">$<?php echo htmlspecialchars(number_format($grand_total, 2)); ?></th>
                  </tr>
               </tfoot>
            </table>
         </div>

         <div class="d-flex justify-content-between mt-4">
            <a href="cart.php?delete_all=1" class="btn btn-danger <?php echo ($grand_total > 0)?'':'disabled'; ?>" onclick="return confirm('Are you sure you want to delete all items from your cart?');">Delete All</a>
            <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
            <a href="checkout.php" class="btn btn-success <?php echo ($grand_total > 0)?'':'disabled'; ?>">Proceed to Checkout</a>
         </div>
      <?php endif; ?>
   </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<!-- custom js file link - already included by footer.php -->
<!-- <script src="js/script.js"></script> -->

</body>
</html>
