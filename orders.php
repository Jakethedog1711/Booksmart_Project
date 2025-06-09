<?php
// orders.php
require_once 'includes/db_connect.php'; // Use the new connection file

// Redirect if not logged in as a Customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Customer') {
   header('location:login.php');
   exit();
}

$user_id = $_SESSION['user_id'];
$orders = [];

// Fetch orders for the logged-in user
$stmt_orders = $conn->prepare("SELECT o.*, st.Type_Name AS ShippingTypeName, st.Price AS ShippingPrice
                              FROM ORDERS o
                              JOIN SHIPPINGTYPES st ON o.ShippingTypeID = st.ShippingTypeID
                              WHERE o.UserID = ?
                              ORDER BY o.Date_of_Purchase DESC");
if ($stmt_orders) {
    $stmt_orders->bind_param("i", $user_id);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    while ($fetch_orders = $result_orders->fetch_assoc()) {
        // Fetch order items for each order
        $order_products = [];
        $items_subtotal = 0;
        $stmt_items = $conn->prepare("SELECT p.Product_Name, p.ProductType, oi.Quantity, oi.Price_at_Purchase
                                      FROM ORDERITEMS oi
                                      JOIN PRODUCTS p ON oi.ProductID = p.ProductID
                                      WHERE oi.OrderID = ?");
        if ($stmt_items) {
            $stmt_items->bind_param("i", $fetch_orders['OrderID']);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();
            while ($item = $result_items->fetch_assoc()) {
                $order_products[] = $item;
                $items_subtotal += ($item['Quantity'] * $item['Price_at_Purchase']);
            }
            $stmt_items->close();
        }

        // Calculate tax for this order
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

        $taxable_amount = $items_subtotal + $fetch_orders['ShippingPrice'];
        $sales_tax = $taxable_amount * $state_tax_rate;
        $final_order_total = $taxable_amount + $sales_tax;

        $fetch_orders['items'] = $order_products;
        $fetch_orders['items_subtotal'] = $items_subtotal;
        $fetch_orders['sales_tax'] = $sales_tax;
        $fetch_orders['final_order_total'] = $final_order_total;
        $fetch_orders['tax_rate_display'] = number_format($state_tax_rate * 100, 2);

        $orders[] = $fetch_orders;
    }
    $stmt_orders->close();
}

require_once 'includes/header.php'; // Use the new header
?>

<div class="heading text-center my-4">
   <h3>your orders</h3>
   <p> <a href="index.php" class="text-decoration-none">home</a> / orders </p>
</div>

<section class="placed-orders py-5">
   <h1 class="title text-center mb-4">Placed Orders</h1>

   <div class="box-container row row-cols-1 row-cols-md-2 g-4">
      <?php
         if (!empty($orders)) {
            foreach ($orders as $order) {
      ?>
      <div class="col">
         <div class="box card shadow-sm h-100 p-3">
            <p> Order ID : <span><?php echo htmlspecialchars($order['OrderID']); ?></span> </p>
            <p> Placed On : <span><?php echo htmlspecialchars($order['Date_of_Purchase']); ?></span> </p>
            <p> Receiver Name : <span><?php echo htmlspecialchars($order['Receiver_Name']); ?></span> </p>
            <p> Shipping Address : <span><?php echo htmlspecialchars($order['Shipping_Address'] . ', ' . $order['Shipping_City'] . ', ' . $order['Shipping_State'] . ' - ' . $order['Shipping_Zip']); ?></span> </p>
            <p> Shipping Type : <span><?php echo htmlspecialchars($order['ShippingTypeName']); ?> ($<?php echo htmlspecialchars(number_format($order['ShippingPrice'], 2)); ?>)</span> </p>
            <p> Payment Status : <span style="color:<?php echo ($order['Order_Status'] == 'Pending' || $order['Order_Status'] == 'Processing') ? 'orange' : 'green'; ?>"><?php echo htmlspecialchars($order['Order_Status']); ?></span> </p>

            <h5 class="mt-3">Ordered Products:</h5>
            <ul class="list-group list-group-flush mb-3">
                <?php foreach ($order['items'] as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo htmlspecialchars($item['Product_Name']); ?> (<?php echo htmlspecialchars($item['ProductType']); ?>)
                        <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($item['Quantity']); ?> x $<?php echo htmlspecialchars(number_format($item['Price_at_Purchase'], 2)); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p> Items Subtotal : <span>$<?php echo htmlspecialchars(number_format($order['items_subtotal'], 2)); ?>/-</span> </p>
            <p> Sales Tax (<?php echo htmlspecialchars($order['tax_rate_display']); ?>%) : <span>$<?php echo htmlspecialchars(number_format($order['sales_tax'], 2)); ?>/-</span> </p>
            <p> Total Price : <span>$<?php echo htmlspecialchars(number_format($order['final_order_total'], 2)); ?>/-</span> </p>
         </div>
      </div>
      <?php
         }
      } else {
         echo '<div class="col-12"><p class="empty text-center alert alert-info">No orders placed yet!</p></div>';
      }
      ?>
   </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<!-- custom js file link - already included by footer.php -->
<!-- <script src="js/script.js"></script> -->

</body>
</html>
