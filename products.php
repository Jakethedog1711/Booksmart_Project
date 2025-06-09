<?php
// products.php
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

$sql = "SELECT ProductID, Product_Name, Price, ProductType FROM PRODUCTS";
$result = $conn->query($sql);
?>

<h2 class="mb-4">Our Book Collection</h2>

<div class="row row-cols-1 row-cols-md-4 g-4">
    <?php
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Determine image based on product type (placeholder)
            $image_text = '';
            switch ($row['ProductType']) {
                case 'Physical Book': $image_text = 'Physical Book'; break;
                case 'E-Book': $image_text = 'E-Book'; break;
                case 'Audiobook': $image_text = 'Audiobook'; break;
                default: $image_text = 'Book Cover'; break;
            }
            $image_url = "[https://placehold.co/400x300/E0E0E0/333333?text=](https://placehold.co/400x300/E0E0E0/333333?text=)" . urlencode($image_text);

            echo '
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <img src="' . $image_url . '" class="card-img-top" alt="' . htmlspecialchars($row['Product_Name']) . '">
                    <div class="card-body">
                        <h5 class="card-title">' . htmlspecialchars($row['Product_Name']) . '</h5>
                        <p class="card-text">Type: ' . htmlspecialchars($row['ProductType']) . '</p>
                        <p class="card-text">Price: $' . htmlspecialchars(number_format($row['Price'], 2)) . '</p>
                        <a href="product_detail.php?id=' . htmlspecialchars($row['ProductID']) . '" class="btn btn-info btn-sm">View Details</a>
                        <button class="btn btn-primary btn-sm add-to-cart-btn" data-product-id="' . htmlspecialchars($row['ProductID']) . '">Add to Cart</button>
                    </div>
                </div>
            </div>';
        }
    } else {
        echo '<div class="col-12"><p>No products available.</p></div>';
    }
    ?>
</div>

<?php require_once 'includes/footer.php'; ?>
