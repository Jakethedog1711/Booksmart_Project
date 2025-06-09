<?php
// admin/users.php
require_once '../includes/db_connect.php'; // Use the new connection file

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('location:../login.php');
    exit();
}

$message = '';

// Handle Delete User
if (isset($_GET['delete'])) {
    $delete_id = sanitize_input($_GET['delete']);

    // Prevent admin from deleting their own account
    if ($delete_id == $_SESSION['user_id']) {
        $_SESSION['message'] = 'You cannot delete your own account!';
        $_SESSION['message_type'] = 'danger';
    } else {
        // Start a transaction to ensure atomicity
        $conn->begin_transaction();
        try {
            // Deleting from USERS will cascade delete from CUSTOMERS/ADMINISTRATORS due to ON DELETE CASCADE
            $stmt = $conn->prepare("DELETE FROM USERS WHERE UserID = ?");
            if (!$stmt) throw new Exception("Prepare delete user failed: " . $conn->error);
            $stmt->bind_param("i", $delete_id);
            if (!$stmt->execute()) throw new Exception("Execute delete user failed: " . $stmt->error);
            $stmt->close();

            $conn->commit();
            $_SESSION['message'] = 'User deleted successfully!';
            $_SESSION['message_type'] = 'success';

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = 'Error deleting user: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
    }
    header('location:admin_users.php');
    exit();
}

// Fetch all users for display
$select_users = $conn->query("SELECT u.UserID, u.Email_Address, u.First_Name, u.Last_Name, u.Phone_Number, u.UserType, a.AdminRole
                               FROM USERS u
                               LEFT JOIN ADMINISTRATORS a ON u.UserID = a.UserID
                               ORDER BY u.UserID DESC");

$users = [];
if ($select_users) {
    while ($fetch_users = $select_users->fetch_assoc()) {
        $users[] = $fetch_users;
    }
}

require_once '../includes/header.php'; // Use the main header
?>

<section class="users">
   <h1 class="title text-center mb-4">User Accounts</h1>

   <div class="box-container row row-cols-1 row-cols-md-2 g-4">
      <?php
         if (!empty($users)) {
            foreach ($users as $fetch_users) {
      ?>
      <div class="col">
         <div class="box card shadow-sm h-100 p-3">
            <p> User ID : <span><?php echo htmlspecialchars($fetch_users['UserID']); ?></span> </p>
            <p> Username : <span><?php echo htmlspecialchars($fetch_users['First_Name'] . ' ' . $fetch_users['Last_Name']); ?></span> </p>
            <p> Email : <span><?php echo htmlspecialchars($fetch_users['Email_Address']); ?></span> </p>
            <p> Phone : <span><?php echo htmlspecialchars($fetch_users['Phone_Number']); ?></span> </p>
            <p> User Type : <span style="color:<?php echo ($fetch_users['UserType'] == 'Admin') ? 'var(--bs-orange)' : 'var(--bs-primary)'; ?>"><?php echo htmlspecialchars($fetch_users['UserType']); ?></span> </p>
            <?php if ($fetch_users['UserType'] == 'Admin'): ?>
                <p> Admin Role : <span><?php echo htmlspecialchars($fetch_users['AdminRole'] ?? 'N/A'); ?></span> </p>
            <?php endif; ?>
            <a href="admin_users.php?delete=<?php echo htmlspecialchars($fetch_users['UserID']); ?>" onclick="return confirm('Are you sure you want to delete this user?');" class="btn btn-danger btn-sm mt-2">Delete User</a>
         </div>
      </div>
      <?php
            }
         } else {
            echo '<div class="col-12"><p class="empty text-center">No users found!</p></div>';
         }
      ?>
   </div>
</section>

<?php require_once '../includes/footer.php'; ?>

<!-- custom admin js file link - already included by footer.php -->
<!-- <script src="js/admin_script.js"></script> -->

</body>
</html>
