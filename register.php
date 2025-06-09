<?php
// register.php
require_once 'includes/db_connect.php'; // Use the new connection file

if (isset($_POST['submit'])) {
    $first_name = sanitize_input($_POST['first_name']); // Assuming you'll add first_name/last_name fields
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // Raw password
    $cpassword = $_POST['cpassword']; // Raw confirm password
    $user_type = sanitize_input($_POST['user_type']); // 'user' or 'admin' from select box

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($cpassword)) {
        $_SESSION['message'] = 'All fields are required.';
        $_SESSION['message_type'] = 'danger';
    } elseif ($password !== $cpassword) {
        $_SESSION['message'] = 'Confirm password does not match!';
        $_SESSION['message_type'] = 'danger';
    } elseif (strlen($password) < 6) { // Minimum password length
        $_SESSION['message'] = 'Password must be at least 6 characters long.';
        $_SESSION['message_type'] = 'danger';
    } else {
        $hashed_password = hash_password($password); // Hash the password

        // Check if email already exists
        $stmt_check_email = $conn->prepare("SELECT UserID FROM USERS WHERE Email_Address = ?");
        if ($stmt_check_email) {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();

            if ($stmt_check_email->num_rows > 0) {
                $_SESSION['message'] = 'User with this email already exists!';
                $_SESSION['message_type'] = 'warning';
            } else {
                $stmt_check_email->close(); // Close previous statement

                // Start a transaction for atomicity
                $conn->begin_transaction();
                try {
                    // Insert into USERS table
                    $stmt_user = $conn->prepare("INSERT INTO USERS (Password, Email_Address, First_Name, Last_Name, UserType) VALUES (?, ?, ?, ?, ?)");
                    if (!$stmt_user) throw new Exception("Prepare user insert failed: " . $conn->error);
                    $stmt_user->bind_param("sssss", $hashed_password, $email, $first_name, $last_name, $user_type);
                    if (!$stmt_user->execute()) throw new Exception("Execute user insert failed: " . $stmt_user->error);
                    $new_user_id = $stmt_user->insert_id;
                    $stmt_user->close();

                    // Insert into specific subclass table
                    if ($user_type === 'Customer') {
                        $stmt_customer = $conn->prepare("INSERT INTO CUSTOMERS (UserID) VALUES (?)");
                        if (!$stmt_customer) throw new Exception("Prepare customer insert failed: " . $conn->error);
                        $stmt_customer->bind_param("i", $new_user_id);
                        if (!$stmt_customer->execute()) throw new Exception("Execute customer insert failed: " . $stmt_customer->error);
                        $stmt_customer->close();

                        // Also create an empty shopping cart for the new customer
                        $stmt_cart = $conn->prepare("INSERT INTO SHOPPINGCARTS (UserID) VALUES (?)");
                        if (!$stmt_cart) throw new Exception("Prepare cart insert failed: " . $conn->error);
                        $stmt_cart->bind_param("i", $new_user_id);
                        if (!$stmt_cart->execute()) throw new Exception("Execute cart insert failed: " . $stmt_cart->error);
                        $stmt_cart->close();

                    } elseif ($user_type === 'Admin') {
                        $stmt_admin = $conn->prepare("INSERT INTO ADMINISTRATORS (UserID, AdminRole) VALUES (?, ?)");
                        if (!$stmt_admin) throw new Exception("Prepare admin insert failed: " . $conn->error);
                        $admin_role = 'Default Admin'; // You might want a form field for this
                        $stmt_admin->bind_param("is", $new_user_id, $admin_role);
                        if (!$stmt_admin->execute()) throw new Exception("Execute admin insert failed: " . $stmt_admin->error);
                        $stmt_admin->close();
                    }

                    $conn->commit(); // Commit the transaction
                    $_SESSION['message'] = 'Registered successfully! You can now login.';
                    $_SESSION['message_type'] = 'success';
                    header('location:login.php');
                    exit();

                } catch (Exception $e) {
                    $conn->rollback(); // Rollback on error
                    $_SESSION['message'] = 'Registration failed: ' . $e->getMessage();
                    $_SESSION['message_type'] = 'danger';
                }
            }
        } else {
            $_SESSION['message'] = 'Database error preparing email check: ' . $conn->error;
            $_SESSION['message_type'] = 'danger';
        }
    }
    header('location:register.php'); // Redirect to show message
    exit();
}

require_once 'includes/header.php'; // Use the new header
?>

<div class="form-container d-flex justify-content-center align-items-center min-vh-100">
   <form action="register.php" method="post" class="p-4 shadow-lg rounded bg-white" style="max-width: 400px; width: 100%;">
      <h3 class="text-center mb-4">Register Now</h3>
      <div class="mb-3">
         <input type="text" name="first_name" placeholder="Enter your first name" required class="form-control">
      </div>
      <div class="mb-3">
         <input type="text" name="last_name" placeholder="Enter your last name" required class="form-control">
      </div>
      <div class="mb-3">
         <input type="email" name="email" placeholder="Enter your email" required class="form-control">
      </div>
      <div class="mb-3">
         <input type="password" name="password" placeholder="Enter your password" required class="form-control">
      </div>
      <div class="mb-3">
         <input type="password" name="cpassword" placeholder="Confirm your password" required class="form-control">
      </div>
      <div class="mb-3">
         <select name="user_type" class="form-select">
            <option value="Customer">Customer</option>
            <!-- Only allow admin registration via direct DB insert or specific admin page for security -->
            <!-- <option value="Admin">Admin</option> -->
         </select>
      </div>
      <input type="submit" name="submit" value="Register Now" class="btn btn-success w-100">
      <p class="mt-3 text-center">Already have an account? <a href="login.php">Login now</a></p>
   </form>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- custom js file link - already included by footer.php -->
<!-- <script src="js/script.js"></script> -->

</body>
</html>
