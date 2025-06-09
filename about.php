<?php
// about.php
require_once 'includes/db_connect.php'; // Use the new connection file

// Check if user is logged in, though not strictly necessary for 'about' page content
// The original code had a redirect, let's keep it if it's meant to be a protected page
// If it's a public 'about us' page, remove this block.
$user_id = $_SESSION['user_id'] ?? null;
if(!isset($user_id)){
   // header('location:login.php'); // Uncomment if this page should be protected
   // exit();
}

require_once 'includes/header.php'; // Use the new header
?>

<div class="heading text-center my-4">
   <h3>about us</h3>
   <p> <a href="index.php" class="text-decoration-none">home</a> / about </p>
</div>

<section class="about py-5">
   <div class="flex row align-items-center">
      <div class="image col-md-6 mb-4 mb-md-0">
         <img src="https://placehold.co/600x400/E0E0E0/333333?text=About+Us+Image" alt="About Us" class="img-fluid rounded shadow-sm">
      </div>
      <div class="content col-md-6">
         <h3>why choose us?</h3>
         <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Eveniet voluptatibus aut hic molestias, reiciendis natus fuga, cumque excepturi veniam ratione iure. Excepturi fugiat placeat iusto facere id officia assumenda temporibus?</p>
         <p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Impedit quos enim minima ipsa dicta officia corporis ratione saepe sed adipisci?</p>
         <a href="contact.php" class="btn btn-primary mt-3">contact us</a>
      </div>
   </div>
</section>

<section class="reviews py-5 bg-light">
   <h1 class="title text-center mb-5">client's reviews</h1>
   <div class="box-container row row-cols-1 row-cols-md-3 g-4">
      <!-- Dummy reviews - In a real app, these would come from the BOOKREVIEWS table -->
      <div class="col">
         <div class="box card h-100 shadow-sm text-center p-4">
            <img src="https://placehold.co/100x100/E0E0E0/333333?text=P1" alt="Reviewer 1" class="rounded-circle mx-auto mb-3">
            <p>"Lorem ipsum dolor sit amet consectetur adipisicing elit. Sunt ad, quo labore fugiat nam accusamus quia. Ducimus repudiandae dolore placeat."</p>
            <div class="stars text-warning mb-2">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star-half-alt"></i>
            </div>
            <h3>john deo</h3>
         </div>
      </div>
      <div class="col">
         <div class="box card h-100 shadow-sm text-center p-4">
            <img src="https://placehold.co/100x100/E0E0E0/333333?text=P2" alt="Reviewer 2" class="rounded-circle mx-auto mb-3">
            <p>"Lorem ipsum dolor sit amet consectetur adipisicing elit. Sunt ad, quo labore fugiat nam accusamus quia. Ducimus repudiandae dolore placeat."</p>
            <div class="stars text-warning mb-2">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star-half-alt"></i>
            </div>
            <h3>jane smith</h3>
         </div>
      </div>
      <div class="col">
         <div class="box card h-100 shadow-sm text-center p-4">
            <img src="https://placehold.co/100x100/E0E0E0/333333?text=P3" alt="Reviewer 3" class="rounded-circle mx-auto mb-3">
            <p>"Lorem ipsum dolor sit amet consectetur adipisicing elit. Sunt ad, quo labore fugiat nam accusamus quia. Ducimus repudiandae dolore placeat."</p>
            <div class="stars text-warning mb-2">
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star"></i>
               <i class="fas fa-star-half-alt"></i>
            </div>
            <h3>peter jones</h3>
         </div>
      </div>
      <!-- Add more boxes as needed -->
   </div>
</section>

<section class="authors py-5">
   <h1 class="title text-center mb-5">great authors</h1>
   <div class="box-container row row-cols-1 row-cols-md-3 row-cols-lg-6 g-4">
      <!-- Dummy authors - In a real app, these might come from a dedicated Authors table or derived from products -->
      <div class="col">
         <div class="box card h-100 shadow-sm text-center p-3">
            <img src="https://placehold.co/150x150/E0E0E0/333333?text=Author+1" alt="Author 1" class="img-fluid rounded-circle mx-auto mb-3">
            <div class="share mb-2">
               <a href="#" class="fab fa-facebook-f text-primary mx-1"></a>
               <a href="#" class="fab fa-twitter text-info mx-1"></a>
               <a href="#" class="fab fa-instagram text-danger mx-1"></a>
               <a href="#" class="fab fa-linkedin text-secondary mx-1"></a>
            </div>
            <h3>john deo</h3>
         </div>
      </div>
      <div class="col">
         <div class="box card h-100 shadow-sm text-center p-3">
            <img src="https://placehold.co/150x150/E0E0E0/333333?text=Author+2" alt="Author 2" class="img-fluid rounded-circle mx-auto mb-3">
            <div class="share mb-2">
               <a href="#" class="fab fa-facebook-f text-primary mx-1"></a>
               <a href="#" class="fab fa-twitter text-info mx-1"></a>
               <a href="#" class="fab fa-instagram text-danger mx-1"></a>
               <a href="#" class="fab fa-linkedin text-secondary mx-1"></a>
            </div>
            <h3>jane doe</h3>
         </div>
      </div>
      <div class="col">
         <div class="box card h-100 shadow-sm text-center p-3">
            <img src="https://placehold.co/150x150/E0E0E0/333333?text=Author+3" alt="Author 3" class="img-fluid rounded-circle mx-auto mb-3">
            <div class="share mb-2">
               <a href="#" class="fab fa-facebook-f text-primary mx-1"></a>
               <a href="#" class="fab fa-twitter text-info mx-1"></a>
               <a href="#" class="fab fa-instagram text-danger mx-1"></a>
               <a href="#" class="fab fa-linkedin text-secondary mx-1"></a>
            </div>
            <h3>peter pan</h3>
         </div>
      </div>
      <div class="col">
         <div class="box card h-100 shadow-sm text-center p-3">
            <img src="https://placehold.co/150x150/E0E0E0/333333?text=Author+4" alt="Author 4" class="img-fluid rounded-circle mx-auto mb-3">
            <div class="share mb-2">
               <a href="#" class="fab fa-facebook-f text-primary mx-1"></a>
               <a href="#" class="fab fa-twitter text-info mx-1"></a>
               <a href="#" class="fab fa-instagram text-danger mx-1"></a>
               <a href="#" class="fab fa-linkedin text-secondary mx-1"></a>
            </div>
            <h3>mary jane</h3>
         </div>
      </div>
      <div class="col">
         <div class="box card h-100 shadow-sm text-center p-3">
            <img src="https://placehold.co/150x150/E0E0E0/333333?text=Author+5" alt="Author 5" class="img-fluid rounded-circle mx-auto mb-3">
            <div class="share mb-2">
               <a href="#" class="fab fa-facebook-f text-primary mx-1"></a>
               <a href="#" class="fab fa-twitter text-info mx-1"></a>
               <a href="#" class="fab fa-instagram text-danger mx-1"></a>
               <a href="#" class="fab fa-linkedin text-secondary mx-1"></a>
            </div>
            <h3>bruce wayne</h3>
         </div>
      </div>
      <div class="col">
         <div class="box card h-100 shadow-sm text-center p-3">
            <img src="https://placehold.co/150x150/E0E0E0/333333?text=Author+6" alt="Author 6" class="img-fluid rounded-circle mx-auto mb-3">
            <div class="share mb-2">
               <a href="#" class="fab fa-facebook-f text-primary mx-1"></a>
               <a href="#" class="fab fa-twitter text-info mx-1"></a>
               <a href="#" class="fab fa-instagram text-danger mx-1"></a>
               <a href="#" class="fab fa-linkedin text-secondary mx-1"></a>
            </div>
            <h3>clark kent</h3>
         </div>
      </div>
   </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<!-- custom js file link - already included by footer.php -->
<!-- <script src="js/script.js"></script> -->

</body>
</html>
