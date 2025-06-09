<?php
// home.php
require_once 'includes/db_connect.php'; // Use the new connection file

// Check if user is logged in (optional for homepage, but original had it)
// If you want the homepage to be accessible to guests, remove this block.
$user_id = $_SESSION['user_id'] ?? null;
if (!isset($user_id)) {
    // header('location:login.php'); // Uncomment if homepage should be protected
    // exit();
}

// The 'add_to_cart' logic will now be handled via AJAX in ajax_actions.php
// So, the PHP block for add_to_cart is removed from here.

require_once 'includes/header.php'; // Use the new header
?>

<section class="home py-5 bg-light rounded-3 shadow-sm mb-4">
   <div class="content text-center">
      <h3 class="display-4">Hand Picked Books to your door.</h3>
      <p class="lead">Lorem ipsum dolor sit amet consectetur adipisicing elit. Excepturi, quod? Reiciendis ut porro iste totam.</p>
      <a href="about.php" class="btn btn-outline-primary btn-lg mt-3">Discover More</a>
   </div>
</section>

<section class="products py-5">
   <h1 class="title text-center mb-4">Latest Products</h1>

   <div class="box-container row row-cols-1 row-cols-md-3 g-4">
      <?php
         // Fetch latest products (e.g., limit 6)
         $select_products = $conn->query("SELECT ProductID, Product_Name, Price, ProductType FROM PRODUCTS ORDER BY ProductID DESC LIMIT 6");
         if ($select_products && $select_products->num_rows > 0) {
            while ($fetch_products = $select_products->fetch_assoc()) {
                $image_text = urlencode($fetch_products['ProductType']);
                $image_url = "https://placehold.co/400x300/E0E0E0/333333?text=" . $image_text;
                // If you store image paths in PHYSICALBOOKS, you'd use that here:
                // if ($fetch_products['ProductType'] === 'Physical Book' && !empty($fetch_products['Image_Path'])) {
                //     $image_url = 'assets/img/uploaded_img/' . htmlspecialchars($fetch_products['Image_Path']);
                // }
      ?>
     <div class="col">
        <div class="box card h-100 shadow-sm">
            <img class="card-img-top" src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($fetch_products['Product_Name']); ?>">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($fetch_products['Product_Name']); ?></h5>
                <p class="card-text">Price: $<?php echo htmlspecialchars(number_format($fetch_products['Price'], 2)); ?>/-</p>
                <!-- Quantity input for adding to cart (can be hidden or shown) -->
                <!-- <input type="number" min="1" name="product_quantity" value="1" class="form-control mb-2 qty-input" style="width: 80px;"> -->
                <button class="btn btn-primary add-to-cart-btn" data-product-id="<?php echo htmlspecialchars($fetch_products['ProductID']); ?>">Add to Cart</button>
                <a href="product_detail.php?id=<?php echo htmlspecialchars($fetch_products['ProductID']); ?>" class="btn btn-info btn-sm mt-2">View Details</a>
            </div>
        </div>
     </div>
      <?php
            }
         } else {
            echo '<div class="col-12"><p class="empty text-center alert alert-info">No products added yet!</p></div>';
         }
      ?>
   </div>

   <div class="load-more text-center mt-4">
      <a href="products.php" class="btn btn-secondary">Load More</a>
   </div>
</section>

<section class="about py-5 bg-white rounded-3 shadow-sm mb-4">
   <div class="flex row align-items-center">
      <div class="image col-md-6 mb-4 mb-md-0">
         <img src="https://placehold.co/600x400/E0E0E0/333333?text=About+Image" alt="About Us" class="img-fluid rounded shadow-sm">
      </div>
      <div class="content col-md-6">
         <h3>About Us</h3>
         <p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Impedit quos enim minima ipsa dicta officia corporis ratione saepe sed adipisci?</p>
         <a href="about.php" class="btn btn-primary mt-3">Read More</a>
      </div>
   </div>
</section>

<section class="home-contact py-5 bg-light rounded-3 shadow-sm">
   <div class="content text-center">
      <h3>Have Any Questions?</h3>
      <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Atque cumque exercitationem repellendus, amet ullam voluptatibus?</p>
      <a href="contact.php" class="btn btn-success mt-3">Contact Us</a>
   </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<!-- custom js file link - already included by footer.php -->
<!-- <script src="js/script.js"></script> -->

</body>
</html>
