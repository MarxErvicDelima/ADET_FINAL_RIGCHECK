<?php 
require_once 'includes/config.php';

// Set success message
$_SESSION['success_message'] = '✓ Your order has been placed successfully! Order #' . ($_SESSION['last_order_number'] ?? 'UNKNOWN');

include 'includes/header.php'; 
?>

<section class="py-24 bg-gray-50 flex items-center justify-center min-h-[calc(100vh-80px)]">
    <div class="bg-white p-16 rounded-3xl shadow-2xl border border-gray-100 w-full max-w-2xl text-center animate-fade-in-up">
        <div class="w-32 h-32 bg-green-100 rounded-full flex items-center justify-center text-primary mx-auto mb-10 shadow-inner relative">
            <div class="absolute inset-0 bg-primary rounded-full blur-2xl opacity-20 animate-pulse"></div>
            <i class="fas fa-check text-6xl relative z-10"></i>
        </div>
        
        <h1 class="text-5xl font-extrabold text-gray-800 mb-6">Order Placed Successfully!</h1>
        <p class="text-xl text-gray-500 mb-12 leading-relaxed max-w-md mx-auto">
            Thank you for choosing <span class="text-primary font-bold">RigCheck</span>. Your order has been received and is being processed by our team.
        </p>

        <div class="bg-gray-50 rounded-2xl p-8 mb-12 border border-gray-100">
            <div class="flex flex-col md:flex-row justify-around gap-8 text-sm">
                <div class="text-center">
                    <p class="text-gray-400 font-bold uppercase tracking-widest mb-2">Order Number</p>
                    <p class="text-gray-800 font-extrabold text-xl">#<?php echo $_SESSION['last_order_number'] ?? 'RC-UNKNOWN'; ?></p>
                </div>
                <div class="text-center">
                    <p class="text-gray-400 font-bold uppercase tracking-widest mb-2">Pick-up Window</p>
                    <p class="text-gray-800 font-extrabold text-xl"><?php echo date('M d, Y', strtotime('+3 days')); ?></p>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-6 justify-center">
            <a href="products.php" class="bg-primary hover:bg-green-600 text-white px-10 py-5 rounded-2xl font-bold transition-all shadow-xl text-lg transform hover:-translate-y-1 active:scale-95">
                Keep Shopping <i class="fas fa-shopping-bag ml-2"></i>
            </a>
            <a href="index.php" class="border-2 border-gray-200 text-gray-500 hover:border-primary hover:text-primary px-10 py-5 rounded-2xl font-bold transition-all text-lg transform hover:-translate-y-1 active:scale-95">
                Go to Home <i class="fas fa-home ml-2"></i>
            </a>
        </div>
        
        <p class="mt-12 text-gray-400 text-sm">
            A confirmation email has been sent to your registered email address.
        </p>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
