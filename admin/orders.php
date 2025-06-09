<?php
// admin/orders.php
require_once '../includes/db_connect.php'; // Use the new connection file

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('location:../login.php');
    exit();
}

// Handle order status update
if (isset($_POST['update_order'])) {
    $order_update_id = sanitize_input($_POST['order_id']);
    $update_status = sanitize_input($_POST['update_payment']); // Renamed to update_status for clarity

    $stmt = $conn->prepare("UPDATE ORDERS SET Order_Status = ? WHERE OrderID = ?");
    if ($stmt) {
        $stmt->bind_param("si", $update_status, $order_update_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Order status has been updated!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error updating order status: ' . $stmt->error;
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = 'Database error preparing update statement: ' . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }
    header('location:admin_orders.php');
    exit();
}

// Handle order deletion
if (isset($_GET['delete'])) {
    $delete_id = sanitize_input($_GET['delete']);

    // Start a transaction to ensure both order and order items are deleted
    $conn->begin_transaction();
    try {
        // Delete from ORDERITEMS first (due to foreign key constraint)
        $stmt_items = $conn->prepare("DELETE FROM ORDERITEMS WHERE OrderID = ?");
        if (!$stmt_items) throw new Exception("Prepare order items delete failed: " . $conn->error);
        $stmt_items->bind_param("i", $delete_id);
        if (!$stmt_items->execute()) throw new Exception("Execute order items delete failed: " . $stmt_items->error);
        $stmt_items->close();

        // Then delete from ORDERS
        $stmt_order = $conn->prepare("DELETE FROM ORDERS WHERE OrderID = ?");
        if (!$stmt_order) throw new Exception("Prepare order delete failed: " . $conn->error);
        $stmt_order->bind_param("i", $delete_id);
        if (!$stmt_order->execute()) throw new Exception("Execute order delete failed: " . $stmt_order->error);
        $stmt_order->close();

        $conn->commit();
        $_SESSION['message'] = 'Order deleted successfully!';
        $_SESSION['message_type'] = 'success';

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = 'Error deleting order: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    header('location:admin_orders.php');
    exit();
}

require_once '../includes/header.php'; // Use the main header
?>

<section class="orders">
   <h1 class="title text-center mb-4">Placed Orders</h1>

   <div class="box-container row row-cols-1 row-cols-md-2 g-4">
      <?php
      // Fetch all orders
      $select_orders = $conn->query("SELECT o.*, u.First_Name, u.Last_Name, u.Email_Address, u.Phone_Number, st.Type_Name AS ShippingTypeName
                                     FROM ORDERS o
                                     JOIN CUSTOMERS c ON o.UserID = c.UserID
                                     JOIN USERS u ON c.UserID = u.UserID
                                     JOIN SHIPPINGTYPES st ON o.ShippingTypeID = st.ShippingTypeID
                                     ORDER BY o.Date_of_Purchase DESC");

      if ($select_orders && $select_orders->num_rows > 0) {
         while ($fetch_orders = $select_orders->fetch_assoc()) {
            // Fetch products for this order
            $order_products = [];
            $stmt_products = $conn->prepare("SELECT p.Product_Name, oi.Quantity, oi.Price_at_Purchase
                                             FROM ORDERITEMS oi
                                             JOIN PRODUCTS p ON oi.ProductID = p.ProductID
                                             WHERE oi.OrderID = ?");
            if ($stmt_products) {
                $stmt_products->bind_param("i", $fetch_orders['OrderID']);
                $stmt_products->execute();
                $result_products = $stmt_products->get_result();
                while ($prod = $result_products->fetch_assoc()) {
                    $order_products[] = htmlspecialchars($prod['Product_Name']) . ' (' . htmlspecialchars($prod['Quantity']) . ' x $' . number_format($prod['Price_at_Purchase'], 2) . ')';
                }
                $stmt_products->close();
            }
            $total_products_str = implode(', ', $order_products);
            $calculated_total_price = array_sum(array_map(function($item) {
                // Extract quantity and price from the formatted string for calculation
                preg_match('/\((?<qty>\d+) x \$(?<price>\d+\.\d{2})\)/', $item, $matches);
                return (float)$matches['qty'] * (float)$matches['price'];
            }, $order_products)); // This is a rough way to re-calculate, better to sum from DB query

            // Recalculate total price including shipping and tax for display
            $shipping_cost = $fetch_orders['ShippingTypeName'] ? $fetch_orders['Price'] : 0; // Assuming Price comes from SHIPPINGTYPES join
            $state_tax_rate = 0;
            $stmt_tax = $conn->prepare("SELECT Sales_Tax_Rate FROM STATETAXES WHERE State_Name = ?");
            if ($stmt_tax) {
                $stmt_tax->bind_param("s", $fetch_orders['Shipping_State']);
                $stmt_tax->execute();
                $tax_res = $stmt_tax->get_result();
                if ($tax_res->num_rows > 0) {
                    $state_tax_rate = $tax_res->fetch_assoc()['Sales_Tax_Rate'];
                }
                $stmt_tax->close();
            }

            // Calculate total price based on items, shipping, and tax
            $items_subtotal = array_reduce($order_products, function($sum, $item) {
                preg_match('/\((?<qty>\d+) x \$(?<price>\d+\.\d{2})\)/', $item, $matches);
                return $sum + ((float)$matches['qty'] * (float)$matches['price']);
            }, 0);

            $taxable_amount = $items_subtotal + $fetch_orders['Price']; // Assuming Price is shipping cost from join
            $sales_tax = $taxable_amount * $state_tax_rate;
            $final_order_total = $taxable_amount + $sales_tax;


      ?>
      <div class="col">
         <div class="box card shadow-sm h-100 p-3">
            <p> Order ID : <span><?php echo htmlspecialchars($fetch_orders['OrderID']); ?></span> </p>
            <p> User ID : <span><?php echo htmlspecialchars($fetch_orders['UserID']); ?></span> </p>
            <p> Placed On : <span><?php echo htmlspecialchars($fetch_orders['Date_of_Purchase']); ?></span> </p>
            <p> Customer Name : <span><?php echo htmlspecialchars($fetch_orders['First_Name'] . ' ' . $fetch_orders['Last_Name']); ?></span> </p>
            <p> Contact Number : <span><?php echo htmlspecialchars($fetch_orders['Phone_Number']); ?></span> </p>
            <p> Email : <span><?php echo htmlspecialchars($fetch_orders['Email_Address']); ?></span> </p>
            <p> Shipping Address : <span><?php echo htmlspecialchars($fetch_orders['Shipping_Address'] . ', ' . $fetch_orders['Shipping_City'] . ', ' . $fetch_orders['Shipping_State'] . ' - ' . $fetch_orders['Shipping_Zip']); ?></span> </p>
            <p> Shipping Type : <span><?php echo htmlspecialchars($fetch_orders['ShippingTypeName']); ?> ($<?php echo htmlspecialchars(number_format($fetch_orders['Price'], 2)); ?>)</span> </p>
            <p> Total Products : <span><?php echo $total_products_str; ?></span> </p>
            <p> Total Price : <span>$<?php echo htmlspecialchars(number_format($final_order_total, 2)); ?>/-</span> </p>
            <p> Payment Status : <span style="color:<?php echo ($fetch_orders['Order_Status'] == 'Pending') ? 'orange' : 'green'; ?>"><?php echo htmlspecialchars($fetch_orders['Order_Status']); ?></span> </p>
            <form action="" method="post" class="mt-3">
               <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($fetch_orders['OrderID']); ?>">
               <div class="mb-2">
                   <select name="update_payment" class="form-select">
                       <option value="" selected disabled><?php echo htmlspecialchars($fetch_orders['Order_Status']); ?></option>
                       <option value="Pending">Pending</option>
                       <option value="Processing">Processing</option>
                       <option value="Shipped">Shipped</option>
                       <option value="Delivered">Delivered</option>
                       <option value="Cancelled">Cancelled</option>
                   </select>
               </div>
               <input type="submit" value="Update Status" name="update_order" class="btn btn-primary btn-sm me-2">
               <a href="admin_orders.php?delete=<?php echo htmlspecialchars($fetch_orders['OrderID']); ?>" onclick="return confirm('Delete this order and all its items?');" class="btn btn-danger btn-sm">Delete Order</a>
            </form>
         </div>
      </div>
      <?php
         }
      } else {
         echo '<div class="col-12"><p class="empty text-center">No orders placed yet!</p></div>';
      }
      ?>
   </div>
</section>

<?php require_once '../includes/footer.php'; ?>

<!-- custom admin js file link - already included by footer.php -->
<!-- <script src="js/admin_script.js"></script> -->

</body>
</html>
