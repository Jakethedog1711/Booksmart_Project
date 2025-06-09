<?php
// admin/index.php (renamed from admin_page.php for consistency with folder structure)
require_once '../includes/db_connect.php'; // Use the new connection file

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('location:../login.php');
    exit();
}

require_once '../includes/header.php'; // Use the main header
?>

<section class="dashboard">
   <h1 class="title text-center mb-4">Admin Dashboard</h1>

   <div class="box-container row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">

      <div class="col">
         <div class="box card text-center shadow-sm h-100 p-3">
            <?php
               $total_pendings = 0;
               $select_pending = $conn->query("SELECT SUM(oi.Quantity * oi.Price_at_Purchase) AS total_price FROM ORDERS o JOIN ORDERITEMS oi ON o.OrderID = oi.OrderID WHERE o.Order_Status = 'Pending'");
               if ($select_pending && $row = $select_pending->fetch_assoc()) {
                   $total_pendings = $row['total_price'] ?? 0;
               }
            ?>
            <h3>$<?php echo htmlspecialchars(number_format($total_pendings, 2)); ?>/-</h3>
            <p>Total Pendings</p>
         </div>
      </div>

      <div class="col">
         <div class="box card text-center shadow-sm h-100 p-3">
            <?php
               $total_completed = 0;
               $select_completed = $conn->query("SELECT SUM(oi.Quantity * oi.Price_at_Purchase) AS total_price FROM ORDERS o JOIN ORDERITEMS oi ON o.OrderID = oi.OrderID WHERE o.Order_Status = 'Delivered'");
               if ($select_completed && $row = $select_completed->fetch_assoc()) {
                   $total_completed = $row['total_price'] ?? 0;
               }
            ?>
            <h3>$<?php echo htmlspecialchars(number_format($total_completed, 2)); ?>/-</h3>
            <p>Completed Payments</p>
         </div>
      </div>

      <div class="col">
         <div class="box card text-center shadow-sm h-100 p-3">
            <?php
               $select_orders = $conn->query("SELECT COUNT(*) AS num_orders FROM ORDERS");
               $number_of_orders = $select_orders ? $select_orders->fetch_assoc()['num_orders'] : 0;
            ?>
            <h3><?php echo htmlspecialchars($number_of_orders); ?></h3>
            <p>Orders Placed</p>
         </div>
      </div>

      <div class="col">
         <div class="box card text-center shadow-sm h-100 p-3">
            <?php
               $select_products = $conn->query("SELECT COUNT(*) AS num_products FROM PRODUCTS");
               $number_of_products = $select_products ? $select_products->fetch_assoc()['num_products'] : 0;
            ?>
            <h3><?php echo htmlspecialchars($number_of_products); ?></h3>
            <p>Products Added</p>
         </div>
      </div>

      <div class="col">
         <div class="box card text-center shadow-sm h-100 p-3">
            <?php
               $select_users = $conn->query("SELECT COUNT(*) AS num_users FROM USERS WHERE UserType = 'Customer'");
               $number_of_users = $select_users ? $select_users->fetch_assoc()['num_users'] : 0;
            ?>
            <h3><?php echo htmlspecialchars($number_of_users); ?></h3>
            <p>Normal Users</p>
         </div>
      </div>

      <div class="col">
         <div class="box card text-center shadow-sm h-100 p-3">
            <?php
               $select_admins = $conn->query("SELECT COUNT(*) AS num_admins FROM USERS WHERE UserType = 'Admin'");
               $number_of_admins = $select_admins ? $select_admins->fetch_assoc()['num_admins'] : 0;
            ?>
            <h3><?php echo htmlspecialchars($number_of_admins); ?></h3>
            <p>Admin Users</p>
         </div>
      </div>

      <div class="col">
         <div class="box card text-center shadow-sm h-100 p-3">
            <?php
               $select_account = $conn->query("SELECT COUNT(*) AS num_accounts FROM USERS");
               $number_of_account = $select_account ? $select_account->fetch_assoc()['num_accounts'] : 0;
            ?>
            <h3><?php echo htmlspecialchars($number_of_account); ?></h3>
            <p>Total Accounts</p>
         </div>
      </div>

      <div class="col">
         <div class="box card text-center shadow-sm h-100 p-3">
            <?php
               // Assuming MESSAGES table exists
               $select_messages = $conn->query("SELECT COUNT(*) AS num_messages FROM MESSAGES");
               $number_of_messages = $select_messages ? $select_messages->fetch_assoc()['num_messages'] : 0;
            ?>
            <h3><?php echo htmlspecialchars($number_of_messages); ?></h3>
            <p>New Messages</p>
         </div>
      </div>

   </div>
</section>

<?php require_once '../includes/footer.php'; ?>

<!-- custom admin js file link - already included by footer.php -->
<!-- <script src="js/admin_script.js"></script> -->

</body>
</html>
