    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-users me-2"></i><?php echo APP_NAME; ?></h5>
                    <p class="text-muted">Connect with like-minded people in your area. Join groups, attend events, and build meaningful relationships.</p>
                </div>
                <div class="col-md-2">
                    <h6>Platform</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>/under-construction.php" class="text-muted">Events</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/under-construction.php" class="text-muted">Groups</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/under-construction.php" class="text-muted">Categories</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6>Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>/under-construction.php" class="text-muted">Help Center</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/under-construction.php" class="text-muted">Contact</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/under-construction.php" class="text-muted">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6>Legal</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>/under-construction.php" class="text-muted">Privacy Policy</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/under-construction.php" class="text-muted">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6>Follow Us</h6>
                    <div class="d-flex">
                        <a href="#" class="text-muted me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-muted me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-muted me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-muted"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">Version <?php echo APP_VERSION; ?></p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <!-- Mobile Optimizations - DISABLED -->
    <!-- <script src="<?php echo BASE_URL; ?>/assets/js/mobile-optimizations.js"></script> -->
    
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>