<?php
// contact.php
require_once 'includes/db_connect.php'; // Use the new connection file

// Check if user is logged in (optional, can allow guest messages)
$user_id = $_SESSION['user_id'] ?? null;

if (isset($_POST['send'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $number = sanitize_input($_POST['number']);
    $msg_text = sanitize_input($_POST['message']);

    if (empty($name) || empty($email) || empty($msg_text)) {
        $_SESSION['message'] = 'Please fill in all required fields (Name, Email, Message).';
        $_SESSION['message_type'] = 'danger';
    } else {
        // Check if message already exists (optional, but good for preventing spam)
        $stmt_check = $conn->prepare("SELECT MessageID FROM MESSAGES WHERE Name = ? AND Email = ? AND Message_Text = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("sss", $name, $email, $msg_text);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $_SESSION['message'] = 'Message already sent!';
                $_SESSION['message_type'] = 'warning';
            } else {
                $stmt_check->close(); // Close previous statement

                // Insert message into MESSAGES table
                $stmt_insert = $conn->prepare("INSERT INTO MESSAGES (UserID, Name, Email, Number, Message_Text) VALUES (?, ?, ?, ?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("issss", $user_id, $name, $email, $number, $msg_text);
                    if ($stmt_insert->execute()) {
                        $_SESSION['message'] = 'Message sent successfully!';
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = 'Error sending message: ' . $stmt_insert->error;
                        $_SESSION['message_type'] = 'danger';
                    }
                    $stmt_insert->close();
                } else {
                    $_SESSION['message'] = 'Database error preparing insert statement: ' . $conn->error;
                    $_SESSION['message_type'] = 'danger';
                }
            }
        } else {
            $_SESSION['message'] = 'Database error preparing check statement: ' . $conn->error;
            $_SESSION['message_type'] = 'danger';
        }
    }
    header('location:contact.php'); // Redirect to refresh page and show message
    exit();
}

require_once 'includes/header.php'; // Use the new header
?>

<div class="heading text-center my-4">
   <h3>contact us</h3>
   <p> <a href="index.php" class="text-decoration-none">home</a> / contact </p>
</div>

<section class="contact py-5">
   <div class="row justify-content-center">
      <div class="col-md-8">
         <form action="" method="post" class="p-4 shadow-sm rounded bg-white">
            <h3 class="text-center mb-4">Say Something!</h3>
            <div class="mb-3">
               <input type="text" name="name" required placeholder="Enter your name" class="form-control">
            </div>
            <div class="mb-3">
               <input type="email" name="email" required placeholder="Enter your email" class="form-control">
            </div>
            <div class="mb-3">
               <input type="text" name="number" required placeholder="Enter your number (optional)" class="form-control">
            </div>
            <div class="mb-3">
               <textarea name="message" class="form-control" placeholder="Enter your message" rows="5" required></textarea>
            </div>
            <input type="submit" value="Send Message" name="send" class="btn btn-primary w-100">
         </form>
      </div>
   </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<!-- custom js file link - already included by footer.php -->
<!-- <script src="js/script.js"></script> -->

</body>
</html>
