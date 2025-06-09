<?php
// product_detail.php
require_once 'includes/db_connect.php';

$product = null;
$reviews = [];
$message = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = sanitize_input($_GET['id']);

    // Fetch product details from PRODUCTS table
    $stmt = $conn->prepare("SELECT ProductID, Product_Name, Price, Nr_books, ProductType FROM PRODUCTS WHERE ProductID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $product = $result->fetch_assoc();

            // Fetch specific details based on ProductType
            $specific_details = [];
            switch ($product['ProductType']) {
                case 'Physical Book':
                    $stmt_specific = $conn->prepare("SELECT Author, ISBN, Genre, Publisher FROM PHYSICALBOOKS WHERE ProductID = ?");
                    break;
                case 'E-Book':
                    $stmt_specific = $conn->prepare("SELECT Author, ISBN, Genre, FileFormat, DownloadLink FROM EBOOKS WHERE ProductID = ?");
                    break;
                case 'Audiobook':
                    $stmt_specific = $conn->prepare("SELECT Author, Narrator, AudioFormat, Duration FROM AUDIOBOOKS WHERE ProductID = ?");
                    break;
                default:
                    $stmt_specific = null;
            }

            if ($stmt_specific) {
                $stmt_specific->bind_param("i", $product_id);
                $stmt_specific->execute();
                $specific_result = $stmt_specific->get_result();
                if ($specific_result->num_rows === 1) {
                    $specific_details = $specific_result->fetch_assoc();
                    $product = array_merge($product, $specific_details); // Merge specific details into product array
                }
                $stmt_specific->close();
            }

            // Fetch reviews for this product
            $stmt_reviews = $conn->prepare("SELECT br.Review_Text, br.Rating, br.Review_Date, u.First_Name, u.Last_Name
                                            FROM BOOKREVIEWS br
                                            JOIN USERS u ON br.UserID = u.UserID
                                            WHERE br.ProductID = ? ORDER BY br.Review_Date DESC");
            if ($stmt_reviews) {
                $stmt_reviews->bind_param("i", $product_id);
                $stmt_reviews->execute();
                $reviews_result = $stmt_reviews->get_result();
                while ($row = $reviews_result->fetch_assoc()) {
                    $reviews[] = $row;
                }
                $stmt_reviews->close();
            }
        } else {
            $message = '<div class="alert alert-warning">Product not found.</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger">Database error: ' . $conn->error . '</div>';
    }
} else {
    $message = '<div class="alert alert-danger">Invalid product ID.</div>';
}

require_once 'includes/header.php';
?>

<?php if ($product): ?>
<div class="row">
    <div class="col-md-4">
        <?php
            $image_text = '';
            switch ($product['ProductType']) {
                case 'Physical Book': $image_text = 'Physical Book'; break;
                case 'E-Book': $image_text = 'E-Book'; break;
                case 'Audiobook': $image_text = 'Audiobook'; break;
                default: $image_text = 'Book Cover'; break;
            }
            $image_url = "[https://placehold.co/400x500/E0E0E0/333333?text=](https://placehold.co/400x500/E0E0E0/333333?text=)" . urlencode($image_text);
        ?>
        <img src="<?php echo $image_url; ?>" class="img-fluid rounded shadow-sm" alt="<?php echo htmlspecialchars($product['Product_Name']); ?>">
    </div>
    <div class="col-md-8">
        <h1><?php echo htmlspecialchars($product['Product_Name']); ?></h1>
        <p class="lead">Price: $<?php echo htmlspecialchars(number_format($product['Price'], 2)); ?></p>
        <p><strong>Type:</strong> <?php echo htmlspecialchars($product['ProductType']); ?></p>

        <?php if ($product['ProductType'] === 'Physical Book'): ?>
            <p><strong>Author:</strong> <?php echo htmlspecialchars($product['Author']); ?></p>
            <p><strong>ISBN:</strong> <?php echo htmlspecialchars($product['ISBN']); ?></p>
            <p><strong>Genre:</strong> <?php echo htmlspecialchars($product['Genre']); ?></p>
            <p><strong>Publisher:</strong> <?php echo htmlspecialchars($product['Publisher']); ?></p>
            <p><strong>Stock:</strong> <?php echo htmlspecialchars($product['Nr_books']); ?></p>
        <?php elseif ($product['ProductType'] === 'E-Book'): ?>
            <p><strong>Author:</strong> <?php echo htmlspecialchars($product['Author']); ?></p>
            <p><strong>ISBN:</strong> <?php echo htmlspecialchars($product['ISBN']); ?></p>
            <p><strong>Genre:</strong> <?php echo htmlspecialchars($product['Genre']); ?></p>
            <p><strong>File Format:</strong> <?php echo htmlspecialchars($product['FileFormat']); ?></p>
            <?php if (!empty($product['DownloadLink'])): ?>
                <p><a href="<?php echo htmlspecialchars($product['DownloadLink']); ?>" target="_blank" class="btn btn-outline-primary">Download Sample</a></p>
            <?php endif; ?>
        <?php elseif ($product['ProductType'] === 'Audiobook'): ?>
            <p><strong>Author:</strong> <?php echo htmlspecialchars($product['Author']); ?></p>
            <p><strong>Narrator:</strong> <?php echo htmlspecialchars($product['Narrator']); ?></p>
            <p><strong>Audio Format:</strong> <?php echo htmlspecialchars($product['AudioFormat']); ?></p>
            <p><strong>Duration:</strong> <?php echo htmlspecialchars($product['Duration']); ?> hours</p>
        <?php endif; ?>

        <button class="btn btn-primary btn-lg mt-3 add-to-cart-btn" data-product-id="<?php echo htmlspecialchars($product['ProductID']); ?>">
            <i class="fas fa-cart-plus"></i> Add to Cart
        </button>

        <hr class="my-4">

        <h4>Customer Reviews (<?php echo count($reviews); ?>)</h4>
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">
                            <?php echo htmlspecialchars($review['First_Name'] . ' ' . $review['Last_Name']); ?>
                            <span class="badge bg-warning text-dark"><?php echo str_repeat('★', $review['Rating']); ?></span>
                        </h6>
                        <p class="card-text"><?php echo htmlspecialchars($review['Review_Text']); ?></p>
                        <small class="text-muted">Reviewed on: <?php echo date('M d, Y', strtotime($review['Review_Date'])); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No reviews yet. Be the first to review this product!</p>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'Customer'): ?>
            <hr class="my-4">
            <h4>Submit Your Review</h4>
            <form action="submit_review.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['ProductID']); ?>">
                <div class="mb-3">
                    <label for="rating" class="form-label">Rating</label>
                    <select class="form-select" id="rating" name="rating" required>
                        <option value="">Select rating</option>
                        <option value="5">5 Stars - Excellent</option>
                        <option value="4">4 Stars - Very Good</option>
                        <option value="3">3 Stars - Good</option>
                        <option value="2">2 Stars - Fair</option>
                        <option value="1">1 Star - Poor</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="review_text" class="form-label">Your Review</label>
                    <textarea class="form-control" id="review_text" name="review_text" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-success">Submit Review</button>
            </form>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <p class="mt-4">Please <a href="login.php">login</a> to submit a review.</p>
        <?php endif; ?>

    </div>
</div>
<?php else: ?>
    <div class="alert alert-info text-center"><?php echo $message; ?></div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
