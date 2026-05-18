    <!-- Footer -->
    <footer class="bg-dark text-white pt-16 pb-8">
        <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-12 border-b border-green-800 pb-12 mb-8">
            <div class="space-y-6">
                <a href="index.php" class="text-3xl font-bold flex items-center text-primary">
                    <i class="fas fa-microchip mr-3"></i> RigCheck
                </a>
                <p class="text-gray-300 leading-relaxed">Your ultimate destination for high-performance computing. We build with passion, you perform with power.</p>
                <div class="bg-green-800/50 p-4 rounded-xl border border-green-700">
                    <p class="text-primary font-bold text-sm mb-1 uppercase tracking-wider"><i class="fas fa-store mr-2"></i> Shop Policy</p>
                    <p class="text-xs text-gray-300">Store Pick-up Only. No deliveries. Pre-order requires ₱500 downpayment.</p>
                </div>
                <div class="flex space-x-5">
                    <a href="#" class="text-white hover:text-primary transition-colors text-xl"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white hover:text-primary transition-colors text-xl"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white hover:text-primary transition-colors text-xl"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white hover:text-primary transition-colors text-xl"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-semibold mb-6 border-l-4 border-primary pl-3">Quick Links</h3>
                <ul class="space-y-4 text-gray-300">
                    <li><a href="index.php" class="hover:text-primary transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-primary"></i> Home</a></li>
                    <li><a href="products.php" class="hover:text-primary transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-primary"></i> Shop All Products</a></li>
                    <li><a href="#" class="hover:text-primary transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-primary"></i> About Us</a></li>
                    <li><a href="#" class="hover:text-primary transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-primary"></i> Contact Support</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold mb-6 border-l-4 border-primary pl-3">Categories</h3>
                <ul class="space-y-4 text-gray-300">
                    <li><a href="products.php?category=Laptops" class="hover:text-primary transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-primary"></i> Laptops</a></li>
                    <li><a href="products.php?category=Pre-built PC" class="hover:text-primary transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-primary"></i> Pre-built PCs</a></li>
                    <li><a href="products.php?category=Peripherals" class="hover:text-primary transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-primary"></i> Peripherals</a></li>
                    <li><a href="products.php?category=Monitors" class="hover:text-primary transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-primary"></i> Monitors</a></li>
                    <li><a href="products.php?category=CPU" class="hover:text-primary transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-primary"></i> CPUs</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-xl font-semibold mb-6 border-l-4 border-primary pl-3">Contact Info</h3>
                <ul class="space-y-5 text-gray-300">
                    <li class="flex items-start">
                        <i class="fas fa-map-marker-alt mt-1.5 mr-4 text-primary"></i>
                        <span>123 Tech Avenue, Silicon Valley,<br>Metro Manila, Philippines</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-phone-alt mr-4 text-primary"></i>
                        <span>+63 912 345 6789</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-envelope mr-4 text-primary"></i>
                        <span>support@rigcheck.com</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-clock mr-4 text-primary"></i>
                        <span>Mon - Sat: 9:00 AM - 6:00 PM</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-400">&copy; 2024 RigCheck. All rights reserved. Your ultimate computer shop partner.</p>
        </div>
    </footer>

    <?php if (!empty($_SESSION['flash_success']) || !empty($_SESSION['flash_error'])): ?>
        <div id="flash-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-4">
            <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl border border-gray-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.35em] <?php echo !empty($_SESSION['flash_success']) ? 'text-green-600' : 'text-red-600'; ?> mb-3">
                            <?php echo !empty($_SESSION['flash_success']) ? 'Success' : 'Error'; ?>
                        </p>
                        <h3 class="text-2xl font-black text-gray-800 mb-2">
                            <?php echo !empty($_SESSION['flash_success']) ? 'Action completed' : 'Something went wrong'; ?>
                        </h3>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            <?php echo htmlspecialchars($_SESSION['flash_success'] ?? $_SESSION['flash_error'] ?? ''); ?>
                        </p>
                    </div>
                    <button type="button" onclick="document.getElementById('flash-modal').remove()" class="text-gray-400 hover:text-gray-700 transition text-2xl leading-none">&times;</button>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" onclick="document.getElementById('flash-modal').remove()" class="rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white hover:bg-green-600 transition">
                        Continue
                    </button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['flash_success'], $_SESSION['flash_error']); ?>
    <?php endif; ?>
</body>
</html>
