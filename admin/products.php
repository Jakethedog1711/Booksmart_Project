<?php
// admin/products.php
require_once '../includes/db_connect.php'; // Use the new connection file

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('location:../login.php');
    exit();
}

$message = '';

// Handle Add Product
if (isset($_POST['add_product'])) {
    $product_name = sanitize_input($_POST['product_name']);
    $price = sanitize_input($_POST['price']);
    $product_type = sanitize_input($_POST['product_type']);
    $nr_books = ($product_type === 'Physical Book') ? (int)sanitize_input($_POST['nr_books']) : 0; // Stock only for physical

    // Image handling for physical books
    $image_name = '';
    $image_folder = '../assets/img/uploaded_img/'; // New path
    if ($product_type === 'Physical Book' && isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $image_name = $_FILES['image']['name'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_size = $_FILES['image']['size'];

        if ($image_size > 2000000) { // 2MB limit
            $message = '<div class="alert alert-danger">Image size is too large. Max 2MB.</div>';
        } else {
            move_uploaded_file($image_tmp_name, $image_folder . $image_name);
        }
    }

    if (empty($message)) { // Proceed only if no image errors
        // Start transaction for atomicity
        $conn->begin_transaction();
        try {
            // 1. Insert into PRODUCTS table
            $stmt_product = $conn->prepare("INSERT INTO PRODUCTS (Product_Name, Price, Nr_books, ProductType) VALUES (?, ?, ?, ?)");
            if (!$stmt_product) throw new Exception("Prepare product insert failed: " . $conn->error);
            $stmt_product->bind_param("sdis", $product_name, $price, $nr_books, $product_type);
            if (!$stmt_product->execute()) throw new Exception("Execute product insert failed: " . $stmt_product->error);
            $new_product_id = $stmt_product->insert_id;
            $stmt_product->close();

            // 2. Insert into specific subclass table
            $stmt_subclass = null;
            if ($product_type === 'Physical Book') {
                $author = sanitize_input($_POST['author']);
                $isbn = sanitize_input($_POST['isbn']);
                $genre = sanitize_input($_POST['genre']);
                $publisher = sanitize_input($_POST['publisher']);
                $stmt_subclass = $conn->prepare("INSERT INTO PHYSICALBOOKS (ProductID, Author, ISBN, Genre, Publisher) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt_subclass) throw new Exception("Prepare physical book insert failed: " . $conn->error);
                $stmt_subclass->bind_param("issss", $new_product_id, $author, $isbn, $genre, $publisher);
            } elseif ($product_type === 'E-Book') {
                $author = sanitize_input($_POST['author']);
                $isbn = sanitize_input($_POST['isbn']);
                $genre = sanitize_input($_POST['genre']);
                $file_format = sanitize_input($_POST['file_format']);
                $download_link = sanitize_input($_POST['download_link']);
                $stmt_subclass = $conn->prepare("INSERT INTO EBOOKS (ProductID, Author, ISBN, Genre, FileFormat, DownloadLink) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$stmt_subclass) throw new Exception("Prepare ebook insert failed: " . $conn->error);
                $stmt_subclass->bind_param("isssss", $new_product_id, $author, $isbn, $genre, $file_format, $download_link);
            } elseif ($product_type === 'Audiobook') {
                $author = sanitize_input($_POST['author']);
                $narrator = sanitize_input($_POST['narrator']);
                $audio_format = sanitize_input($_POST['audio_format']);
                $duration = sanitize_input($_POST['duration']);
                $stmt_subclass = $conn->prepare("INSERT INTO AUDIOBOOKS (ProductID, Author, Narrator, AudioFormat, Duration) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt_subclass) throw new Exception("Prepare audiobook insert failed: " . $conn->error);
                $stmt_subclass->bind_param("isssd", $new_product_id, $author, $narrator, $audio_format, $duration);
            }

            if ($stmt_subclass && !$stmt_subclass->execute()) {
                throw new Exception("Execute subclass insert failed: " . $stmt_subclass->error);
            }
            if ($stmt_subclass) $stmt_subclass->close();

            $conn->commit();
            $_SESSION['message'] = 'Product added successfully!';
            $_SESSION['message_type'] = 'success';

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = 'Error adding product: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            // Clean up uploaded image if transaction fails
            if (!empty($image_name) && file_exists($image_folder . $image_name)) {
                unlink($image_folder . $image_name);
            }
        }
    }
    header('location:admin_products.php');
    exit();
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $delete_id = sanitize_input($_GET['delete']);

    // Start transaction for atomicity
    $conn->begin_transaction();
    try {
        // Get product type and image name before deleting
        $stmt_get_product = $conn->prepare("SELECT ProductType FROM PRODUCTS WHERE ProductID = ?");
        if (!$stmt_get_product) throw new Exception("Prepare get product failed: " . $conn->error);
        $stmt_get_product->bind_param("i", $delete_id);
        $stmt_get_product->execute();
        $product_data = $stmt_get_product->get_result()->fetch_assoc();
        $stmt_get_product->close();

        if ($product_data['ProductType'] === 'Physical Book') {
             // If it's a physical book, get the image name from the old structure if it was stored there
             // Assuming images were stored in 'uploaded_img/' and linked by name in the old 'products' table
             // In the new schema, we don't have an 'image' column in PRODUCTS.
             // If you want to store image paths for physical books, you'd need to add a column to PHYSICALBOOKS.
             // For now, we'll skip unlink as the new schema doesn't track images directly in PRODUCTS.
             // If you add an 'image_path' to PHYSICALBOOKS, you'd retrieve it here and unlink.
        }

        // Deleting from PRODUCTS will cascade delete from subclasses (PHYSICALBOOKS, EBOOKS, AUDIOBOOKS)
        // and also from ORDERITEMS, SHOPPINGCARTITEMS, BOOKREVIEWS due to ON DELETE CASCADE.
        $stmt_delete = $conn->prepare("DELETE FROM PRODUCTS WHERE ProductID = ?");
        if (!$stmt_delete) throw new Exception("Prepare product delete failed: " . $conn->error);
        $stmt_delete->bind_param("i", $delete_id);
        if (!$stmt_delete->execute()) throw new Exception("Execute product delete failed: " . $stmt_delete->error);
        $stmt_delete->close();

        $conn->commit();
        $_SESSION['message'] = 'Product deleted successfully!';
        $_SESSION['message_type'] = 'success';

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = 'Error deleting product: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    header('location:admin_products.php');
    exit();
}

// Handle Update Product
if (isset($_POST['update_product'])) {
    $update_p_id = sanitize_input($_POST['update_p_id']);
    $update_name = sanitize_input($_POST['update_name']);
    $update_price = sanitize_input($_POST['update_price']);
    $update_product_type = sanitize_input($_POST['update_product_type']); // New field for type

    $update_nr_books = ($update_product_type === 'Physical Book') ? (int)sanitize_input($_POST['update_nr_books']) : 0;

    // Image handling for physical books (if you add an image_path to PHYSICALBOOKS)
    // This part assumes you add an 'Image_Path' column to PHYSICALBOOKS
    $update_image_name = '';
    $update_image_folder = '../assets/img/uploaded_img/';
    $update_old_image = $_POST['update_old_image'] ?? ''; // Old image path from hidden field

    if ($update_product_type === 'Physical Book' && isset($_FILES['update_image']) && $_FILES['update_image']['error'] == UPLOAD_ERR_OK) {
        $update_image_name = $_FILES['update_image']['name'];
        $update_image_tmp_name = $_FILES['update_image']['tmp_name'];
        $update_image_size = $_FILES['update_image']['size'];

        if ($update_image_size > 2000000) {
            $message = '<div class="alert alert-danger">New image file size is too large. Max 2MB.</div>';
        } else {
            // Delete old image if it exists and is different
            if (!empty($update_old_image) && file_exists($update_image_folder . $update_old_image) && $update_old_image !== $update_image_name) {
                unlink($update_image_folder . $update_old_image);
            }
            move_uploaded_file($update_image_tmp_name, $update_image_folder . $update_image_name);
        }
    }

    if (empty($message)) {
        $conn->begin_transaction();
        try {
            // 1. Update PRODUCTS table
            $stmt_product_update = $conn->prepare("UPDATE PRODUCTS SET Product_Name = ?, Price = ?, Nr_books = ?, ProductType = ? WHERE ProductID = ?");
            if (!$stmt_product_update) throw new Exception("Prepare product update failed: " . $conn->error);
            $stmt_product_update->bind_param("sdisi", $update_name, $update_price, $update_nr_books, $update_product_type, $update_p_id);
            if (!$stmt_product_update->execute()) throw new Exception("Execute product update failed: " . $stmt_product_update->error);
            $stmt_product_update->close();

            // 2. Update specific subclass table (or handle type change)
            // This is complex: if product type changes, you need to delete from old subclass and insert into new.
            // For simplicity, this example assumes type doesn't change during update or handles only same-type updates.
            // A robust solution would involve checking old type vs new type and performing appropriate DELETE/INSERT.
            if ($update_product_type === 'Physical Book') {
                $author = sanitize_input($_POST['update_author']);
                $isbn = sanitize_input($_POST['update_isbn']);
                $genre = sanitize_input($_POST['update_genre']);
                $publisher = sanitize_input($_POST['update_publisher']);
                // If you added Image_Path to PHYSICALBOOKS:
                // $image_path_to_save = !empty($update_image_name) ? $update_image_name : $update_old_image;

                $stmt_subclass_update = $conn->prepare("UPDATE PHYSICALBOOKS SET Author = ?, ISBN = ?, Genre = ?, Publisher = ? WHERE ProductID = ?");
                if (!$stmt_subclass_update) throw new Exception("Prepare physical book update failed: " . $conn->error);
                $stmt_subclass_update->bind_param("ssssi", $author, $isbn, $genre, $publisher, $update_p_id);
            } elseif ($update_product_type === 'E-Book') {
                $author = sanitize_input($_POST['update_author']);
                $isbn = sanitize_input($_POST['update_isbn']);
                $genre = sanitize_input($_POST['update_genre']);
                $file_format = sanitize_input($_POST['update_file_format']);
                $download_link = sanitize_input($_POST['update_download_link']);
                $stmt_subclass_update = $conn->prepare("UPDATE EBOOKS SET Author = ?, ISBN = ?, Genre = ?, FileFormat = ?, DownloadLink = ? WHERE ProductID = ?");
                if (!$stmt_subclass_update) throw new Exception("Prepare ebook update failed: " . $conn->error);
                $stmt_subclass_update->bind_param("sssssi", $author, $isbn, $genre, $file_format, $download_link, $update_p_id);
            } elseif ($update_product_type === 'Audiobook') {
                $author = sanitize_input($_POST['update_author']);
                $narrator = sanitize_input($_POST['update_narrator']);
                $audio_format = sanitize_input($_POST['update_audio_format']);
                $duration = sanitize_input($_POST['update_duration']);
                $stmt_subclass_update = $conn->prepare("UPDATE AUDIOBOOKS SET Author = ?, Narrator = ?, AudioFormat = ?, Duration = ? WHERE ProductID = ?");
                if (!$stmt_subclass_update) throw new Exception("Prepare audiobook update failed: " . $conn->error);
                $stmt_subclass_update->bind_param("sssdi", $author, $narrator, $audio_format, $duration, $update_p_id);
            }

            if (isset($stmt_subclass_update) && !$stmt_subclass_update->execute()) {
                throw new Exception("Execute subclass update failed: " . $stmt_subclass_update->error);
            }
            if (isset($stmt_subclass_update)) $stmt_subclass_update->close();

            $conn->commit();
            $_SESSION['message'] = 'Product updated successfully!';
            $_SESSION['message_type'] = 'success';

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = 'Error updating product: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
            // Clean up newly uploaded image if transaction fails
            if (!empty($update_image_name) && file_exists($update_image_folder . $update_image_name)) {
                unlink($update_image_folder . $update_image_name);
            }
        }
    }
    header('location:admin_products.php');
    exit();
}

// Fetch all products for display
$products = [];
$sql_products = "SELECT p.ProductID, p.Product_Name, p.Price, p.Nr_books, p.ProductType,
                        pb.Author AS PB_Author, pb.ISBN AS PB_ISBN, pb.Genre AS PB_Genre, pb.Publisher,
                        eb.Author AS EB_Author, eb.ISBN AS EB_ISBN, eb.Genre AS EB_Genre, eb.FileFormat, eb.DownloadLink,
                        ab.Author AS AB_Author, ab.Narrator, ab.AudioFormat, ab.Duration
                 FROM PRODUCTS p
                 LEFT JOIN PHYSICALBOOKS pb ON p.ProductID = pb.ProductID
                 LEFT JOIN EBOOKS eb ON p.ProductID = eb.ProductID
                 LEFT JOIN AUDIOBOOKS ab ON p.ProductID = ab.ProductID
                 ORDER BY p.ProductID DESC";
$result_products = $conn->query($sql_products);
if ($result_products) {
    while ($row = $result_products->fetch_assoc()) {
        $products[] = $row;
    }
}

require_once '../includes/header.php'; // Use the main header
?>

<section class="add-products">
   <h1 class="title text-center mb-4">Shop Products</h1>

   <div class="card shadow-sm mb-4">
       <div class="card-header bg-primary text-white">
           Add New Product
       </div>
       <div class="card-body">
           <form action="admin_products.php" method="post" enctype="multipart/form-data">
               <div class="mb-3">
                   <label for="product_name" class="form-label">Product Name</label>
                   <input type="text" name="product_name" class="form-control" placeholder="Enter product name" required>
               </div>
               <div class="mb-3">
                   <label for="price" class="form-label">Price ($)</label>
                   <input type="number" min="0" step="0.01" name="price" class="form-control" placeholder="Enter product price" required>
               </div>
               <div class="mb-3">
                   <label for="product_type" class="form-label">Product Type</label>
                   <select name="product_type" id="product_type" class="form-select" required onchange="toggleProductFields()">
                       <option value="">Select Type</option>
                       <option value="Physical Book">Physical Book</option>
                       <option value="E-Book">E-Book</option>
                       <option value="Audiobook">Audiobook</option>
                   </select>
               </div>

               <!-- Conditional fields for Physical Book -->
               <div id="physical_book_fields" style="display: none;">
                   <div class="mb-3">
                       <label for="nr_books" class="form-label">Stock Quantity</label>
                       <input type="number" min="0" name="nr_books" class="form-control" placeholder="Enter stock quantity">
                   </div>
                   <div class="mb-3">
                       <label for="image" class="form-label">Product Image</label>
                       <input type="file" name="image" accept="image/jpg, image/jpeg, image/png" class="form-control">
                   </div>
                   <div class="mb-3">
                       <label for="author" class="form-label">Author</label>
                       <input type="text" name="author" class="form-control" placeholder="Enter author name">
                   </div>
                   <div class="mb-3">
                       <label for="isbn" class="form-label">ISBN</label>
                       <input type="text" name="isbn" class="form-control" placeholder="Enter ISBN">
                   </div>
                   <div class="mb-3">
                       <label for="genre" class="form-label">Genre</label>
                       <input type="text" name="genre" class="form-control" placeholder="Enter genre">
                   </div>
                   <div class="mb-3">
                       <label for="publisher" class="form-label">Publisher</label>
                       <input type="text" name="publisher" class="form-control" placeholder="Enter publisher">
                   </div>
               </div>

               <!-- Conditional fields for E-Book -->
               <div id="ebook_fields" style="display: none;">
                   <div class="mb-3">
                       <label for="author_ebook" class="form-label">Author</label>
                       <input type="text" name="author" id="author_ebook" class="form-control" placeholder="Enter author name">
                   </div>
                   <div class="mb-3">
                       <label for="isbn_ebook" class="form-label">ISBN (Optional)</label>
                       <input type="text" name="isbn" id="isbn_ebook" class="form-control" placeholder="Enter ISBN (optional)">
                   </div>
                   <div class="mb-3">
                       <label for="genre_ebook" class="form-label">Genre</label>
                       <input type="text" name="genre" id="genre_ebook" class="form-control" placeholder="Enter genre">
                   </div>
                   <div class="mb-3">
                       <label for="file_format" class="form-label">File Format</label>
                       <input type="text" name="file_format" class="form-control" placeholder="e.g., PDF, EPUB" required>
                   </div>
                   <div class="mb-3">
                       <label for="download_link" class="form-label">Download Link (DL)</label>
                       <input type="url" name="download_link" class="form-control" placeholder="Enter download URL" required>
                   </div>
               </div>

               <!-- Conditional fields for Audiobook -->
               <div id="audiobook_fields" style="display: none;">
                   <div class="mb-3">
                       <label for="author_audiobook" class="form-label">Author</label>
                       <input type="text" name="author" id="author_audiobook" class="form-control" placeholder="Enter author name">
                   </div>
                   <div class="mb-3">
                       <label for="narrator" class="form-label">Narrator</label>
                       <input type="text" name="narrator" class="form-control" placeholder="Enter narrator name">
                   </div>
                   <div class="mb-3">
                       <label for="audio_format" class="form-label">Audio Format</label>
                       <input type="text" name="audio_format" class="form-control" placeholder="e.g., MP3, WAV" required>
                   </div>
                   <div class="mb-3">
                       <label for="duration" class="form-label">Duration (Hours.Minutes)</label>
                       <input type="number" min="0" step="0.01" name="duration" class="form-control" placeholder="e.g., 3.50 for 3h 30m" required>
                   </div>
               </div>

               <button type="submit" value="add product" name="add_product" class="btn btn-primary">Add Product</button>
           </form>
       </div>
   </div>
</section>

<section class="show-products">
   <h1 class="title text-center mb-4">View Products</h1>
   <div class="box-container row row-cols-1 row-cols-md-3 g-4">
      <?php
         if (!empty($products)) {
            foreach ($products as $fetch_products) {
                $image_url = "https://placehold.co/400x300/E0E0E0/333333?text=" . urlencode($fetch_products['ProductType']);
                // If you store image paths in PHYSICALBOOKS, you'd use that here:
                // if ($fetch_products['ProductType'] === 'Physical Book' && !empty($fetch_products['Image_Path'])) {
                //     $image_url = '../assets/img/uploaded_img/' . htmlspecialchars($fetch_products['Image_Path']);
                // }
      ?>
      <div class="col">
         <div class="box card shadow-sm h-100">
            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($fetch_products['Product_Name']); ?>" class="card-img-top">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($fetch_products['Product_Name']); ?></h5>
                <p class="card-text">Type: <?php echo htmlspecialchars($fetch_products['ProductType']); ?></p>
                <p class="card-text">Price: $<?php echo htmlspecialchars(number_format($fetch_products['Price'], 2)); ?>/-</p>
                <?php if ($fetch_products['ProductType'] === 'Physical Book'): ?>
                    <p class="card-text">Stock: <?php echo htmlspecialchars($fetch_products['Nr_books']); ?></p>
                <?php endif; ?>
                <a href="admin_products.php?update_id=<?php echo htmlspecialchars($fetch_products['ProductID']); ?>" class="btn btn-warning btn-sm me-2">Update</a>
                <a href="admin_products.php?delete=<?php echo htmlspecialchars($fetch_products['ProductID']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?');">Delete</a>
            </div>
         </div>
      </div>
      <?php
            }
         } else {
            echo '<div class="col-12"><p class="empty text-center">No products added yet!</p></div>';
         }
      ?>
   </div>
</section>

<section class="edit-product-form py-4" style="display: none;">
   <?php
      if (isset($_GET['update_id'])) {
         $update_id = sanitize_input($_GET['update_id']);
         $stmt_fetch_update = $conn->prepare("SELECT p.ProductID, p.Product_Name, p.Price, p.Nr_books, p.ProductType,
                                                    pb.Author AS PB_Author, pb.ISBN AS PB_ISBN, pb.Genre AS PB_Genre, pb.Publisher,
                                                    eb.Author AS EB_Author, eb.ISBN AS EB_ISBN, eb.Genre AS EB_Genre, eb.FileFormat, eb.DownloadLink,
                                                    ab.Author AS AB_Author, ab.Narrator, ab.AudioFormat, ab.Duration
                                             FROM PRODUCTS p
                                             LEFT JOIN PHYSICALBOOKS pb ON p.ProductID = pb.ProductID
                                             LEFT JOIN EBOOKS eb ON p.ProductID = eb.ProductID
                                             LEFT JOIN AUDIOBOOKS ab ON p.ProductID = ab.ProductID
                                             WHERE p.ProductID = ?");
      }
         if ($stmt_fetch_update) {
            $stmt_fetch_update->bind_param("i", $update_id);
            $stmt_fetch_update->execute();
            $fetch_update = $stmt_fetch_update->get_result()->fetch_assoc();
            $stmt_fetch_update->close();

            if ($fetch_update) {
   ?>
   <div class="card shadow-sm">
       <div class="card-header bg-warning text-dark">
           Update Product
       </div>
       <div class="card-body">
           <form action="admin_products.php" method="post" enctype="multipart/form-data">
               <input type="hidden" name="update_p_id" value="<?php echo htmlspecialchars($fetch_update['ProductID']); ?>">
               <input type="hidden" name="update_old_image" value="<?php //echo htmlspecialchars($fetch_update['Image_Path'] ?? ''); // If you add Image_Path ?>">

               <div class="mb-3">
                   <label for="update_name" class="form-label">Product Name</label>
                   <input type="text" name="update_name" value="<?php echo htmlspecialchars($fetch_update['Product_Name']); ?>" class="form-control" required placeholder="enter product name">
               </div>
               <div class="mb-3">
                   <label for="update_price" class="form-label">Price ($)</label>
                   <input type="number" min="0" step="0.01" name="update_price" value="<?php echo htmlspecialchars($fetch_update['Price']); ?>" class="form-control" required placeholder="enter product price">
               </div>
               <div class="mb-3">
                   <label for="update_product_type" class="form-label">Product Type</label>
                   <select name="update_product_type" id="update_product_type" class="form-select" required onchange="toggleProductFields('update')">
                       <option value="Physical Book" <?php echo ($fetch_update['ProductType'] === 'Physical Book') ? 'selected' : ''; ?>>Physical Book</option>
                       <option value="E-Book" <?php echo ($fetch_update['ProductType'] === 'E-Book') ? 'selected' : ''; ?>>E-Book</option>
                       <option value="Audiobook" <?php echo ($fetch_update['ProductType'] === 'Audiobook') ? 'selected' : ''; ?>>Audiobook</option>
                   </select>
               </div>

               <!-- Conditional fields for Physical Book (Update) -->
               <div id="update_physical_book_fields" style="display: <?php echo ($fetch_update['ProductType'] === 'Physical Book') ? 'block' : 'none'; ?>;">
                   <div class="mb-3">
                       <label for="update_nr_books" class="form-label">Stock Quantity</label>
                       <input type="number" min="0" name="update_nr_books" value="<?php echo htmlspecialchars($fetch_update['Nr_books']); ?>" class="form-control" placeholder="Enter stock quantity">
                   </div>
                   <div class="mb-3">
                       <label for="update_image" class="form-label">Product Image (Leave blank to keep current)</label>
                       <input type="file" class="form-control" name="update_image" accept="image/jpg, image/jpeg, image/png">
                       <?php // if ($fetch_update['ProductType'] === 'Physical Book' && !empty($fetch_update['Image_Path'])): ?>
                           <!-- <img src="../assets/img/uploaded_img/<?php // echo htmlspecialchars($fetch_update['Image_Path']); ?>" alt="Current Image" class="img-thumbnail mt-2" style="max-width: 150px;"> -->
                       <?php // endif; ?>
                   </div>
                   <div class="mb-3">
                       <label for="update_author_pb" class="form-label">Author</label>
                       <input type="text" name="update_author" id="update_author_pb" value="<?php echo htmlspecialchars($fetch_update['PB_Author'] ?? ''); ?>" class="form-control" placeholder="Enter author name">
                   </div>
                   <div class="mb-3">
                       <label for="update_isbn_pb" class="form-label">ISBN</label>
                       <input type="text" name="update_isbn" id="update_isbn_pb" value="<?php echo htmlspecialchars($fetch_update['PB_ISBN'] ?? ''); ?>" class="form-control" placeholder="Enter ISBN">
                   </div>
                   <div class="mb-3">
                       <label for="update_genre_pb" class="form-label">Genre</label>
                       <input type="text" name="update_genre" id="update_genre_pb" value="<?php echo htmlspecialchars($fetch_update['PB_Genre'] ?? ''); ?>" class="form-control" placeholder="Enter genre">
                   </div>
                   <div class="mb-3">
                       <label for="update_publisher" class="form-label">Publisher</label>
                       <input type="text" name="update_publisher" id="update_publisher" value="<?php echo htmlspecialchars($fetch_update['Publisher'] ?? ''); ?>" class="form-control" placeholder="Enter publisher">
                   </div>
               </div>

               <!-- Conditional fields for E-Book (Update) -->
               <div id="update_ebook_fields" style="display: <?php echo ($fetch_update['ProductType'] === 'E-Book') ? 'block' : 'none'; ?>;">
                   <div class="mb-3">
                       <label for="update_author_eb" class="form-label">Author</label>
                       <input type="text" name="update_author" id="update_author_eb" value="<?php echo htmlspecialchars($fetch_update['EB_Author'] ?? ''); ?>" class="form-control" placeholder="Enter author name">
                   </div>
                   <div class="mb-3">
                       <label for="update_isbn_eb" class="form-label">ISBN (Optional)</label>
                       <input type="text" name="update_isbn" id="update_isbn_eb" value="<?php echo htmlspecialchars($fetch_update['EB_ISBN'] ?? ''); ?>" class="form-control" placeholder="Enter ISBN (optional)">
                   </div>
                   <div class="mb-3">
                       <label for="update_genre_eb" class="form-label">Genre</label>
                       <input type="text" name="update_genre" id="update_genre_eb" value="<?php echo htmlspecialchars($fetch_update['EB_Genre'] ?? ''); ?>" class="form-control" placeholder="Enter genre">
                   </div>
                   <div class="mb-3">
                       <label for="update_file_format" class="form-label">File Format</label>
                       <input type="text" name="update_file_format" id="update_file_format" value="<?php echo htmlspecialchars($fetch_update['FileFormat'] ?? ''); ?>" class="form-control" placeholder="e.g., PDF, EPUB">
                   </div>
                   <div class="mb-3">
                       <label for="update_download_link" class="form-label">Download Link (DL)</label>
                       <input type="url" name="update_download_link" id="update_download_link" value="<?php echo htmlspecialchars($fetch_update['DownloadLink'] ?? ''); ?>" class="form-control" placeholder="Enter download URL">
                   </div>
               </div>

               <!-- Conditional fields for Audiobook (Update) -->
               <div id="update_audiobook_fields" style="display: <?php echo ($fetch_update['ProductType'] === 'Audiobook') ? 'block' : 'none'; ?>;">
                   <div class="mb-3">
                       <label for="update_author_ab" class="form-label">Author</label>
                       <input type="text" name="update_author" id="update_author_ab" value="<?php echo htmlspecialchars($fetch_update['AB_Author'] ?? ''); ?>" class="form-control" placeholder="Enter author name">
                   </div>
                   <div class="mb-3">
                       <label for="update_narrator" class="form-label">Narrator</label>
                       <input type="text" name="update_narrator" id="update_narrator" value="<?php echo htmlspecialchars($fetch_update['Narrator'] ?? ''); ?>" class="form-control" placeholder="Enter narrator name">
                   </div>
                   <div class="mb-3">
                       <label for="update_audio_format" class="form-label">Audio Format</label>
                       <input type="text" name="update_audio_format" id="update_audio_format" value="<?php echo htmlspecialchars($fetch_update['AudioFormat'] ?? ''); ?>" class="form-control" placeholder="e.g., MP3, WAV">
                   </div>
                   <div class="mb-3">
                       <label for="update_duration" class="form-label">Duration (Hours.Minutes)</label>
                       <input type="number" min="0" step="0.01" name="update_duration" id="update_duration" value="<?php echo htmlspecialchars($fetch_update['Duration'] ?? ''); ?>" class="form-control" placeholder="e.g., 3.50 for 3h 30m">
                   </div>
               </div>

               <button type="submit" value="update" name="update_product" class="btn btn-warning me-2">Update Product</button>
               <a href="admin_products.php" class="btn btn-secondary">Cancel</a>
           </form>
       </div>
   </div>
   <script>
       // Show the update form section if update_id is set
       document.addEventListener('DOMContentLoaded', function() {
           document.querySelector(".edit-product-form").style.display = "block";
           toggleProductFields('update'); // Initialize fields based on current product type
       });
   </script>
   <?php
            } else {
                echo '<div class="alert alert-danger text-center">Product not found for update.</div>';
            }
         }
   ?>
</section>

<script>
    function toggleProductFields(prefix = '') {
        const typeSelect = document.getElementById(prefix + 'product_type');
        const physicalFields = document.getElementById(prefix + 'physical_book_fields');
        const ebookFields = document.getElementById(prefix + 'ebook_fields');
        const audiobookFields = document.getElementById(prefix + 'audiobook_fields');

        // Hide all fields first
        physicalFields.style.display = 'none';
        ebookFields.style.display = 'none';
        audiobookFields.style.display = 'none';

        // Show relevant fields based on selection
        if (typeSelect.value === 'Physical Book') {
            physicalFields.style.display = 'block';
        } else if (typeSelect.value === 'E-Book') {
            ebookFields.style.display = 'block';
        } else if (typeSelect.value === 'Audiobook') {
            audiobookFields.style.display = 'block';
        }
    }

    // Call on page load for add form
    document.addEventListener('DOMContentLoaded', function() {
        toggleProductFields();
    });
</script>

<?php require_once '../includes/footer.php'; ?> 
