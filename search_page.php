<?php
// search_page.php
require_once 'includes/db_connect.php'; // Use the new connection file

// Redirect if not logged in (original code had this)
$user_id = $_SESSION['user_id'] ?? null;
if (!isset($user_id)) {
   header('location:login.php');
   exit();
}

// The 'add_to_cart' logic will now be handled via AJAX in ajax_actions.php
// So, the PHP block for add_to_cart is removed from here.

require_once 'includes/header.php'; // Use the new header
?>

<div class="heading text-center my-4">
   <h3>search page</h3>
   <p> <a href="index.php" class="text-decoration-none">home</a> / search </p>
</div>

<section class="search-form py-4">
   <div class="row justify-content-center">
      <div class="col-md-6">
         <form action="" method="post" class="d-flex shadow-sm rounded">
            <input type="text" name="search" placeholder="Search products..." class="form-control me-2" value="<?php echo htmlspecialchars($_POST['search'] ?? ''); ?>">
            <input type="submit" name="submit" value="Search" class="btn btn-primary">
         </form>
      </div>
   </div>
</section>

<section class="products py-5" style="padding-top: 0;">
   <div class="box-container row row-cols-1 row-cols-md-3 g-4">
   <?php
      if (isset($_POST['submit']) && !empty($_POST['search'])) {
         $search_item = '%' . sanitize_input($_POST['search']) . '%'; // Add wildcards for LIKE search
         $stmt_search = $conn->prepare("SELECT ProductID, Product_Name, Price, ProductType FROM PRODUCTS WHERE Product_Name LIKE ?");
         if ($stmt_search) {
            $stmt_search->bind_param("s", $search_item);
            $stmt_search->execute();
            $select_products = $stmt_search->get_result();

            if ($select_products->num_rows > 0) {
               while ($fetch_product = $select_products->fetch_assoc()) {
                  $image_text = urlencode($fetch_product['ProductType']);
                  $image_url = "https://placehold.co/400x300/E0E0E0/333333?text=" . $image_text;
                  // If you store image paths in PHYSICALBOOKS, you'd use that here:
                  // if ($fetch_product['ProductType'] === 'Physical Book' && !empty($fetch_product['Image_Path'])) {
                  //     $image_url = 'assets/img/uploaded_img/' . htmlspecialchars($fetch_product['Image_Path']);
                  // }
   ?>
   <div class="col">
      <div class="box card h-100 shadow-sm">
         <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($fetch_product['Product_Name']); ?>" class="card-img-top">
         <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($fetch_product['Product_Name']); ?></h5>
            <p class="card-text">Price: $<?php echo htmlspecialchars(number_format($fetch_product['Price'], 2)); ?>/-</p>
            <!-- Quantity input can be added here if needed for search results -->
            <button class="btn btn-primary add-to-cart-btn" data-product-id="<?php echo htmlspecialchars($fetch_product['ProductID']); ?>">Add to Cart</button>
            <a href="product_detail.php?id=<?php echo htmlspecialchars($fetch_product['ProductID']); ?>" class="btn btn-info btn-sm mt-2">View Details</a>
         </div>
      </div>
   </div>
   <?php
               }
            } else {
               echo '<div class="col-12"><p class="empty text-center alert alert-info">No result found!</p></div>';
            }
            $stmt_search->close();
         } else {
            echo '<div class="col-12"><p class="empty text-center alert alert-danger">Database error during search: ' . $conn->error . '</p></div>';
         }
      } else {
         echo '<div class="col-12"><p class="empty text-center alert alert-info">Search for something!</p></div>';
      }
   ?>
   </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<!-- custom js file link - already included by footer.php -->
<!-- <script src="js/script.js"></script> -->

</body>
</html>
