<?php
// login.php
require_once 'includes/db_connect.php'; // Use the new connection file

if (isset($_POST['submit'])) {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // Raw password for verification

    if (empty($email) || empty($password)) {
        $_SESSION['message'] = 'Please enter both email and password.';
        $_SESSION['message_type'] = 'danger';
    } else {
        $stmt = $conn->prepare("SELECT UserID, Password, First_Name, UserType FROM USERS WHERE Email_Address = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $hashed_password_from_db, $first_name, $user_type);
                $stmt->fetch();

                if (verify_password($password, $hashed_password_from_db)) {
                    // Password is correct, set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['email'] = $email; // Store email in session for convenience

                    $_SESSION['message'] = 'Login successful!';
                    $_SESSION['message_type'] = 'success';

                    // Redirect based on user type
                    if ($user_type === 'Admin') {
                        header("Location: admin/index.php");
                    } else {
                        header("Location: my_account.php"); // Assuming you'll create my_account.php
                    }
                    exit();
                } else {
                    $_SESSION['message'] = 'Incorrect email or password!';
                    $_SESSION['message_type'] = 'danger';
                }
            } else {
                $_SESSION['message'] = 'Incorrect email or password!';
                $_SESSION['message_type'] = 'danger';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Database error preparing statement: ' . $conn->error;
            $_SESSION['message_type'] = 'danger';
        }
    }
    header('location:login.php'); // Redirect to show message
    exit();
}

require_once 'includes/header.php'; // Use the new header
?>

<div class="form-container d-flex justify-content-center align-items-center min-vh-100">
   <form action="login.php" method="post" class="p-4 shadow-lg rounded bg-white" style="max-width: 400px; width: 100%;">
      <h3 class="text-center mb-4">Login Now</h3>
      <div class="mb-3">
         <input type="email" name="email" placeholder="Enter your email" required class="form-control">
      </div>
      <div class="mb-3">
         <input type="password" name="password" placeholder="Enter your password" required class="form-control">
      </div>
      <input type="submit" name="submit" value="Login Now" class="btn btn-primary w-100">
      <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register now</a></p>
   </form>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- custom js file link - already included by footer.php -->
<!-- <script src="js/script.js"></script> -->

</body>
</html>
