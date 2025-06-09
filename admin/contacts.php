<?php
// admin/contacts.php
require_once '../includes/db_connect.php'; // Use the new connection file

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('location:../login.php');
    exit();
}

// Handle message deletion
if (isset($_GET['delete'])) {
    $delete_id = sanitize_input($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM MESSAGES WHERE MessageID = ?");
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Message deleted successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Error deleting message: ' . $stmt->error;
            $_SESSION['message_type'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = 'Database error preparing delete statement: ' . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }
    header('location:admin_contacts.php'); // Redirect to refresh page and show message
    exit();
}

require_once '../includes/header.php'; // Use the main header
?>

<section class="messages">
   <h1 class="title text-center mb-4">Customer Messages</h1>

   <div class="box-container row row-cols-1 row-cols-md-2 g-4">
   <?php
      // Fetch messages from the MESSAGES table
      $select_message = $conn->query("SELECT * FROM MESSAGES ORDER BY Sent_On DESC"); // Assuming 'Sent_On' column
      if ($select_message && $select_message->num_rows > 0) {
         while ($fetch_message = $select_message->fetch_assoc()) {
   ?>
   <div class="col">
      <div class="box card shadow-sm h-100 p-3">
         <p> User ID : <span><?php echo htmlspecialchars($fetch_message['UserID'] ?? 'N/A'); ?></span> </p>
         <p> Name : <span><?php echo htmlspecialchars($fetch_message['Name']); ?></span> </p>
         <p> Number : <span><?php echo htmlspecialchars($fetch_message['Number']); ?></span> </p>
         <p> Email : <span><?php echo htmlspecialchars($fetch_message['Email']); ?></span> </p>
         <p> Message : <span><?php echo htmlspecialchars($fetch_message['Message_Text']); ?></span> </p>
         <p> Sent On : <span><?php echo htmlspecialchars($fetch_message['Sent_On'] ?? 'N/A'); ?></span> </p>
         <a href="admin_contacts.php?delete=<?php echo htmlspecialchars($fetch_message['MessageID']); ?>" class="btn btn-danger btn-sm mt-2" onclick="return confirm('Are you sure you want to delete this message?');">Delete Message</a>
      </div>
   </div>
   <?php
         }
      } else {
         echo '<div class="col-12"><p class="empty text-center">You have no messages!</p></div>';
      }
   ?>
   </div>
</section>

<?php require_once '../includes/footer.php'; ?>

<!-- custom admin js file link - already included by footer.php -->
<!-- <script src="js/admin_script.js"></script> -->

</body>
</html>
