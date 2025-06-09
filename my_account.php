<?php

include 'includes/db_connect.php'; // Make sure this path is correct for your setup



$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
   header('location:login.php');
   exit(); // Always exit after a header redirect
}

// Fetch user data from the database
$select_user = mysqli_query($conn, "SELECT * FROM `users` WHERE id = '$user_id'") or die('query failed');
$fetch_user = mysqli_fetch_assoc($select_user);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>My Account</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <link rel="stylesheet" href="assets/css/style.css"> 
   </head>
<body>
   
<?php include 'includes/header.php'; ?> 
<div class="heading">
   <h3>My Account</h3>
   <p> <a href="home.php">home</a> / account </p>
</div>

<section class="user-account">

   <h1 class="title">My Profile Information</h1>

   <div class="box-container">

      <div class="box">
         <p> **Welcome,** <span><?php echo $fetch_user['name']; ?></span>! </p>
         <p> **Your Email:** <span><?php echo $fetch_user['email']; ?></span> </p>
         <p> **Account Type:** <span><?php echo $fetch_user['user_type']; ?></span> </p>
         
         <div class="flex-btn">
            <a href="update_profile.php" class="option-btn">Update Profile</a> 
            <a href="orders.php" class="option-btn">View My Orders</a>
         </div>
      </div>

   </div>

</section>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/script.js"></script>
</body>
</html>