<?php
// includes/footer.php
// This file will be included at the end of every page.
// It closes the main container and includes Bootstrap JS and your custom JS.
?>
    </main>
    <footer class="bg-light text-center text-lg-start mt-auto py-3">
        <div class="container text-center">
            <p>&copy; <?php echo date("Y"); ?> Online Bookstore. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (if you choose to use it) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Your Custom JavaScript -->
    <script src="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? '../assets/js/script.js' : 'assets/js/script.js'; ?>"></script>
    <!-- Custom Admin JS (only if this footer is used for admin pages too) -->
    <?php if (strpos($_SERVER['REQUEST_URI'], 'admin') !== false): ?>
        <script src="../assets/js/admin_script.js"></script>
    <?php endif; ?>
</body>
</html>
<?php
// Close database connection at the very end of the script execution
if (isset($conn)) {
    $conn->close();
}
?>
