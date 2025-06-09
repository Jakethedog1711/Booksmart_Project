<?php
// ajax_actions.php
require_once 'includes/db_connect.php';

header('Content-Type: application/json'); // Respond with JSON

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if (isset($_POST['action'])) {
    $action = sanitize_input($_POST['action']);
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        $response = ['status' => 'error', 'message' => 'User not logged in.'];
        echo json_encode($response);
        exit();
    }

    // Get user's cart ID (or create if it doesn't exist)
    $cart_id = null;
    $stmt_get_cart = $conn->prepare("SELECT CartID FROM SHOPPINGCARTS WHERE UserID = ?");
    if ($stmt_get_cart) {
        $stmt_get_cart->bind_param("i", $user_id);
        $stmt_get_cart->execute();
        $result_get_cart = $stmt_get_cart->get_result();
        if ($result_get_cart->num_rows > 0) {
            $cart_id = $result_get_cart->fetch_assoc()['CartID'];
        } else {
            // Create a cart for the user
            $stmt_create_cart = $conn->prepare("INSERT INTO SHOPPINGCARTS (UserID) VALUES (?)");
            if ($stmt_create_cart) {
                $stmt_create_cart->bind_param("i", $user_id);
                $stmt_create_cart->execute();
                $cart_id = $stmt_create_cart->insert_id;
                $stmt_create_cart->close();
            }
        }
        $stmt_get_cart->close();
    }

    if (!$cart_id) {
        $response = ['status' => 'error', 'message' => 'Could not retrieve or create shopping cart.'];
        echo json_encode($response);
        exit();
    }

    switch ($action) {
        case 'get_cart_count':
            $stmt = $conn->prepare("SELECT SUM(Quantity) AS total_items FROM SHOPPINGCARTITEMS WHERE CartID = ?");
            if ($stmt) {
                $stmt->bind_param("i", $cart_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                echo $row['total_items'] ?? 0; // Output raw count for JS to update
                $stmt->close();
                exit(); // Exit after outputting count
            }
            break;

        case 'add_to_cart':
            $product_id = sanitize_input($_POST['product_id']);
            $quantity = (int)$_POST['quantity'];

            // Check if product exists and is in stock (for physical books)
            $stmt_product = $conn->prepare("SELECT ProductType, Nr_books FROM PRODUCTS WHERE ProductID = ?");
            if ($stmt_product) {
                $stmt_product->bind_param("i", $product_id);
                $stmt_product->execute();
                $res_product = $stmt_product->get_result();
                if ($res_product->num_rows === 0) {
                    $response = ['status' => 'error', 'message' => 'Product not found.'];
                    echo json_encode($response);
                    exit();
                }
                $product_data = $res_product->fetch_assoc();
                $stmt_product->close();

                if ($product_data['ProductType'] === 'Physical Book' && $product_data['Nr_books'] < $quantity) {
                    $response = ['status' => 'error', 'message' => 'Not enough stock available.'];
                    echo json_encode($response);
                    exit();
                }
            }


            // Check if item already in cart
            $stmt = $conn->prepare("SELECT CartItemID, Quantity FROM SHOPPINGCARTITEMS WHERE CartID = ? AND ProductID = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $cart_id, $product_id);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    // Update quantity if item exists
                    $stmt->bind_result($cart_item_id, $current_quantity);
                    $stmt->fetch();
                    $new_quantity = $current_quantity + $quantity;

                    if ($product_data['ProductType'] === 'Physical Book' && $product_data['Nr_books'] < $new_quantity) {
                        $response = ['status' => 'error', 'message' => 'Adding this quantity would exceed available stock.'];
                        echo json_encode($response);
                        exit();
                    }

                    $stmt_update = $conn->prepare("UPDATE SHOPPINGCARTITEMS SET Quantity = ? WHERE CartItemID = ?");
                    if ($stmt_update) {
                        $stmt_update->bind_param("ii", $new_quantity, $cart_item_id);
                        if ($stmt_update->execute()) {
                            $response = ['status' => 'success', 'message' => 'Product quantity updated in cart!'];
                        } else {
                            $response = ['status' => 'error', 'message' => 'Failed to update cart quantity: ' . $stmt_update->error];
                        }
                        $stmt_update->close();
                    }
                } else {
                    // Add new item to cart
                    $stmt_insert = $conn->prepare("INSERT INTO SHOPPINGCARTITEMS (CartID, ProductID, Quantity) VALUES (?, ?, ?)");
                    if ($stmt_insert) {
                        $stmt_insert->bind_param("iii", $cart_id, $product_id, $quantity);
                        if ($stmt_insert->execute()) {
                            $response = ['status' => 'success', 'message' => 'Product added to cart!'];
                        } else {
                            $response = ['status' => 'error', 'message' => 'Failed to add product to cart: ' . $stmt_insert->error];
                        }
                        $stmt_insert->close();
                    }
                }
                $stmt->close();
            }
            break;

        case 'remove_from_cart':
            $cart_item_id = sanitize_input($_POST['cart_item_id']);

            $stmt = $conn->prepare("DELETE FROM SHOPPINGCARTITEMS WHERE CartItemID = ? AND CartID = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $cart_item_id, $cart_id);
                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'Item removed from cart.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Failed to remove item: ' . $stmt->error];
                }
                $stmt->close();
            }
            break;

        case 'update_cart_quantity':
            $cart_item_id = sanitize_input($_POST['cart_item_id']);
            $quantity = (int)$_POST['quantity'];

            if ($quantity < 1) {
                $response = ['status' => 'error', 'message' => 'Quantity must be at least 1.'];
                echo json_encode($response);
                exit();
            }

            // Check product stock before updating (for physical books)
            $stmt_check_stock = $conn->prepare("
                SELECT p.ProductType, p.Nr_books
                FROM SHOPPINGCARTITEMS sci
                JOIN PRODUCTS p ON sci.ProductID = p.ProductID
                WHERE sci.CartItemID = ? AND sci.CartID = ?
            ");
            if ($stmt_check_stock) {
                $stmt_check_stock->bind_param("ii", $cart_item_id, $cart_id);
                $stmt_check_stock->execute();
                $res_check_stock = $stmt_check_stock->get_result();
                if ($res_check_stock->num_rows > 0) {
                    $item_data = $res_check_stock->fetch_assoc();
                    if ($item_data['ProductType'] === 'Physical Book' && $item_data['Nr_books'] < $quantity) {
                        $response = ['status' => 'error', 'message' => 'Not enough stock for this quantity.'];
                        echo json_encode($response);
                        exit();
                    }
                }
                $stmt_check_stock->close();
            }


            $stmt = $conn->prepare("UPDATE SHOPPINGCARTITEMS SET Quantity = ? WHERE CartItemID = ? AND CartID = ?");
            if ($stmt) {
                $stmt->bind_param("iii", $quantity, $cart_item_id, $cart_id);
                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'Cart quantity updated.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Failed to update quantity: ' . $stmt->error];
                }
                $stmt->close();
            }
            break;

        default:
            $response = ['status' => 'error', 'message' => 'Unknown action.'];
            break;
    }
}

echo json_encode($response);
?>
