<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RigCheck - Your Computer Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#10b981', // green-500
                        secondary: '#ffffff', // white
                        dark: '#064e3b', // green-900
                    },
                    keyframes: {
                        'fade-in-up': {
                            '0%': {
                                opacity: '0',
                                transform: 'translateY(20px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            }
                        },
                        'float': {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' }
                        },
                        'bounce-subtle': {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' }
                        },
                        'pulse-light': {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '.8' }
                        }
                    },
                    animation: {
                        'fade-in-up': 'fade-in-up 0.6s ease-out',
                        'float': 'float 3s ease-in-out infinite',
                        'bounce-subtle': 'bounce-subtle 1s infinite',
                        'pulse-light': 'pulse-light 2s cubic-bezier(0.4, 0, 0.6, 1) infinite'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #10b981;
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #059669;
        }
        
        /* Smooth transitions */
        * {
            transition-property: color, background-color, border-color, box-shadow;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }
        
        /* Prevent transition delays on critical properties */
        button, a, input, select, textarea {
            transition-property: all;
        }
        
        /* Better focus states for accessibility */
        button:focus-visible, a:focus-visible, input:focus-visible {
            outline: 2px solid #10b981;
            outline-offset: 2px;
        }
        
        /* Glassmorphism effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Product card hover effect */
        .product-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Gradient text for primary titles */
        .gradient-text {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Subtle button animations */
        .btn-animate {
            position: relative;
            overflow: hidden;
        }
        
        .btn-animate::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn-animate:hover::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(100, 100);
                opacity: 0;
            }
        }
    </style>
    <script>
        // Modal functions
        function showModal(type, title, message, onConfirm = null) {
            const modal = document.getElementById('actionModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const modalIcon = document.getElementById('modalIcon');
            const confirmBtn = document.getElementById('modalConfirmBtn');
            const cancelBtn = document.getElementById('modalCancelBtn');
            
            // Set title and message
            modalTitle.textContent = title;
            modalMessage.innerHTML = message;
            
            // Update styling based on type
            modal.className = `fixed inset-0 bg-black/50 flex items-center justify-center z-[100] ${type === 'hidden' ? 'hidden' : ''}`;
            
            const modalContent = document.getElementById('modalContent');
            if (type === 'success') {
                modalContent.className = 'bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 overflow-hidden border-t-4 border-green-500 animate-fade-in-up';
                modalIcon.className = 'w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-green-500 mx-auto mb-6 shadow-inner text-4xl';
                modalIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                cancelBtn.style.display = 'none';
                confirmBtn.textContent = 'OK';
                confirmBtn.className = 'w-full bg-green-500 hover:bg-green-600 text-white py-3 rounded-b-3xl font-bold transition-all text-lg';
            } else if (type === 'error') {
                modalContent.className = 'bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 overflow-hidden border-t-4 border-red-500 animate-fade-in-up';
                modalIcon.className = 'w-20 h-20 bg-red-100 rounded-full flex items-center justify-center text-red-500 mx-auto mb-6 shadow-inner text-4xl';
                modalIcon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                cancelBtn.style.display = 'none';
                confirmBtn.textContent = 'OK';
                confirmBtn.className = 'w-full bg-red-500 hover:bg-red-600 text-white py-3 rounded-b-3xl font-bold transition-all text-lg';
            } else if (type === 'confirm') {
                modalContent.className = 'bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 overflow-hidden border-t-4 border-orange-500 animate-fade-in-up';
                modalIcon.className = 'w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center text-orange-500 mx-auto mb-6 shadow-inner text-4xl';
                modalIcon.innerHTML = '<i class="fas fa-question-circle"></i>';
                cancelBtn.style.display = 'block';
                confirmBtn.textContent = 'Yes, Confirm';
                confirmBtn.className = 'bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-xl font-bold transition-all';
                cancelBtn.textContent = 'Cancel';
            }
            
            // Set confirm callback
            confirmBtn.onclick = () => {
                closeModal();
                if (onConfirm) onConfirm();
            };
            
            cancelBtn.onclick = closeModal;
            
            // Show modal
            modal.classList.remove('hidden');
        }
        
        function closeModal() {
            const modal = document.getElementById('actionModal');
            modal.classList.add('hidden');
        }
        
        // Check for session messages on page load
        window.addEventListener('load', () => {
            const successMsg = document.getElementById('successMessage')?.textContent;
            const errorMsg = document.getElementById('errorMessage')?.textContent;
            
            if (successMsg) {
                showModal('success', 'Success!', successMsg);
            } else if (errorMsg) {
                showModal('error', 'Error', errorMsg);
            }
        });
    </script>
</head>
<body class="bg-gray-50 text-gray-800">
    <!-- Modal System -->
    <div id="actionModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[100] hidden">
        <div id="modalContent" class="bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 overflow-hidden border-t-4 border-green-500 animate-fade-in-up">
            <div class="p-8 text-center">
                <div id="modalIcon" class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-green-500 mx-auto mb-6 shadow-inner text-4xl">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 id="modalTitle" class="text-2xl font-bold text-gray-800 mb-4">Success!</h2>
                <p id="modalMessage" class="text-gray-600 mb-8 leading-relaxed"></p>
            </div>
            <div class="flex gap-3 justify-center p-8 border-t border-gray-100">
                <button id="modalCancelBtn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-xl font-bold transition-all hidden">Cancel</button>
                <button id="modalConfirmBtn" class="w-full bg-green-500 hover:bg-green-600 text-white py-3 rounded-b-3xl font-bold transition-all text-lg">OK</button>
            </div>
        </div>
    </div>
    
    <!-- Hidden message containers for PHP messages -->
    <div id="successMessage" class="hidden"><?php echo isset($_SESSION['success_message']) ? htmlspecialchars($_SESSION['success_message']) : ''; unset($_SESSION['success_message']); ?></div>
    <div id="errorMessage" class="hidden"><?php echo isset($_SESSION['error_message']) ? htmlspecialchars($_SESSION['error_message']) : ''; unset($_SESSION['error_message']); ?></div>

    <!-- Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-primary flex items-center">
                <i class="fas fa-microchip mr-2"></i> RigCheck
            </a>
            
            <div class="hidden md:flex space-x-8 font-medium">
                <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
                    <!-- Admin Navigation -->
                    <a href="admin_dashboard.php" class="text-primary hover:text-green-700 transition font-bold"><i class="fas fa-chart-line mr-1"></i> Admin Dashboard</a>
                    <a href="admin_dashboard.php#orders" class="hover:text-primary transition">Manage Orders</a>
                    <a href="admin_dashboard.php#inventory" class="hover:text-primary transition">Inventory</a>
                <?php else: ?>
                    <!-- Customer/Guest Navigation -->
                    <a href="index.php" class="hover:text-primary transition">Home</a>
                    <a href="products.php" class="hover:text-primary transition">Shop</a>
                    <div class="relative group">
                        <button class="hover:text-primary transition flex items-center">Categories <i class="fas fa-chevron-down text-[10px] ml-1.5 opacity-50 group-hover:rotate-180 transition-transform"></i></button>
                        <div class="absolute hidden group-hover:block bg-white shadow-xl rounded-2xl mt-2 w-56 py-3 border border-gray-100 animate-fade-in-up">
                            <a href="products.php?category=Laptops" class="flex items-center px-5 py-2.5 hover:bg-green-50 text-gray-700 hover:text-primary transition-colors">
                                <i class="fas fa-laptop w-6 text-xs"></i> Laptops
                            </a>
                            <a href="products.php?category=Pre-built PC" class="flex items-center px-5 py-2.5 hover:bg-green-50 text-gray-700 hover:text-primary transition-colors">
                                <i class="fas fa-desktop w-6 text-xs"></i> Pre-built PCs
                            </a>
                            <div class="relative group/sub">
                                <button class="w-full flex items-center justify-between px-5 py-2.5 hover:bg-green-50 text-gray-700 hover:text-primary transition-colors">
                                    <span class="flex items-center"><i class="fas fa-keyboard w-6 text-xs"></i> Peripherals</span>
                                    <i class="fas fa-chevron-right text-[10px] opacity-50"></i>
                                </button>
                                <div class="absolute left-full top-0 hidden group-hover/sub:block bg-white shadow-xl rounded-2xl ml-1 w-48 py-3 border border-gray-100">
                                    <a href="products.php?category=Mouse" class="block px-5 py-2 hover:bg-green-50 text-sm">Mouse</a>
                                    <a href="products.php?category=Headset" class="block px-5 py-2 hover:bg-green-50 text-sm">Headsets</a>
                                    <a href="products.php?category=Microphone" class="block px-5 py-2 hover:bg-green-50 text-sm">Microphones</a>
                                    <a href="products.php?category=Speaker" class="block px-5 py-2 hover:bg-green-50 text-sm">Speakers</a>
                                    <a href="products.php?category=Mousepad" class="block px-5 py-2 hover:bg-green-50 text-sm">Mousepads</a>
                                    <a href="products.php?category=Other Peripherals" class="block px-5 py-2 hover:bg-green-50 text-sm">Other Gear</a>
                                </div>
                            </div>
                            <a href="products.php?category=Monitors" class="flex items-center px-5 py-2.5 hover:bg-green-50 text-gray-700 hover:text-primary transition-colors">
                                <i class="fas fa-tv w-6 text-xs"></i> Monitors
                            </a>
                            <a href="products.php?category=CPU" class="flex items-center px-5 py-2.5 hover:bg-green-50 text-gray-700 hover:text-primary transition-colors">
                                <i class="fas fa-microchip w-6 text-xs"></i> CPUs
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex items-center space-x-6">
                <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
                    <!-- Admin Tools -->
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Admin Panel</span>
                <?php else: ?>
                    <!-- Customer Tools -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="my_orders.php" class="text-gray-600 hover:text-primary transition" title="My Orders" aria-label="View my orders">
                            <i class="fas fa-receipt text-xl"></i>
                        </a>
                    <?php endif; ?>
                    <a href="cart.php" class="relative text-gray-600 hover:text-primary transition" title="Shopping Cart" aria-label="View shopping cart">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="cart-count" class="absolute -top-2 -right-2 bg-primary text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center font-bold"><?php echo $cart_count; ?></span>
                    </a>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) ? 'admin_dashboard.php' : 'user_profile.php'; ?>" class="text-gray-600 hover:text-primary transition flex items-center justify-center w-10 h-10 rounded-full hover:bg-green-50" title="My Profile" aria-label="View my profile">
                        <i class="fas fa-user text-xl"></i>
                    </a>
                    <a href="logout.php" class="text-gray-600 hover:text-red-500 transition" title="Logout" aria-label="Logout">
                        <i class="fas fa-sign-out-alt text-xl"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-600 hover:text-primary transition" title="Login" aria-label="Login">
                        <i class="fas fa-user text-xl"></i>
                    </a>
                <?php endif; ?>
                
                <!-- Mobile menu button -->
                <button id="mobile-menu-toggle" class="md:hidden text-gray-600 hover:text-primary transition" aria-label="Toggle mobile menu" aria-expanded="false">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-100 shadow-lg animate-fade-in-up">
            <div class="container mx-auto px-4 py-4 space-y-4">
                <?php if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1): ?>
                    <a href="index.php" class="block py-2 text-gray-700 hover:text-primary transition font-medium">
                        <i class="fas fa-home mr-2 text-primary"></i> Home
                    </a>
                    <a href="products.php" class="block py-2 text-gray-700 hover:text-primary transition font-medium">
                        <i class="fas fa-store mr-2 text-primary"></i> Shop
                    </a>
                    <button id="categories-toggle" class="w-full text-left py-2 text-gray-700 hover:text-primary transition font-medium flex justify-between items-center" aria-expanded="false">
                        <span><i class="fas fa-list mr-2 text-primary"></i> Categories</span>
                        <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                    <div id="categories-menu" class="hidden pl-4 space-y-2 border-l border-gray-200">
                        <a href="products.php?category=Laptops" class="block py-1 text-gray-600 hover:text-primary transition">
                            <i class="fas fa-laptop mr-2 text-primary text-xs"></i> Laptops
                        </a>
                        <a href="products.php?category=Pre-built PC" class="block py-1 text-gray-600 hover:text-primary transition">
                            <i class="fas fa-desktop mr-2 text-primary text-xs"></i> Pre-built PCs
                        </a>
                        <a href="products.php?category=Monitors" class="block py-1 text-gray-600 hover:text-primary transition">
                            <i class="fas fa-tv mr-2 text-primary text-xs"></i> Monitors
                        </a>
                        <a href="products.php?category=CPU" class="block py-1 text-gray-600 hover:text-primary transition">
                            <i class="fas fa-microchip mr-2 text-primary text-xs"></i> CPUs
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Toast Notification System -->
    <div id="toast-container" class="fixed top-4 right-4 z-[9999] space-y-3" aria-live="polite" aria-atomic="true"></div>

    <script>
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        const categoriesToggle = document.getElementById('categories-toggle');
        const categoriesMenu = document.getElementById('categories-menu');

        mobileMenuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            mobileMenuToggle.setAttribute('aria-expanded', mobileMenu.classList.contains('hidden') ? 'false' : 'true');
        });

        categoriesToggle?.addEventListener('click', () => {
            categoriesMenu.classList.toggle('hidden');
            categoriesToggle.setAttribute('aria-expanded', categoriesMenu.classList.contains('hidden') ? 'false' : 'true');
        });

        // Toast notification system
        function showToast(message, type = 'info', duration = 3000) {
            const container = document.getElementById('toast-container');
            const toastId = 'toast-' + Date.now();
            
            const bgColor = {
                'success': 'bg-green-50 border-green-200 text-green-700',
                'error': 'bg-red-50 border-red-200 text-red-700',
                'warning': 'bg-yellow-50 border-yellow-200 text-yellow-700',
                'info': 'bg-blue-50 border-blue-200 text-blue-700'
            }[type] || 'bg-blue-50 border-blue-200 text-blue-700';

            const iconClass = {
                'success': 'fa-check-circle text-green-600',
                'error': 'fa-exclamation-circle text-red-600',
                'warning': 'fa-triangle-exclamation text-yellow-600',
                'info': 'fa-info-circle text-blue-600'
            }[type] || 'fa-info-circle text-blue-600';

            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `${bgColor} border rounded-xl p-4 shadow-lg animate-fade-in-up flex items-center gap-3 min-w-[300px]`;
            toast.innerHTML = `
                <i class="fas ${iconClass} text-lg flex-shrink-0"></i>
                <p class="text-sm font-medium flex-1">${message}</p>
                <button onclick="document.getElementById('${toastId}').remove()" class="flex-shrink-0 opacity-50 hover:opacity-100 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            `;

            container.appendChild(toast);

            if (duration > 0) {
                setTimeout(() => {
                    toast.style.animation = 'fade-in-up 0.6s ease-out reverse forwards';
                    setTimeout(() => toast.remove(), 600);
                }, duration);
            }
        }

        // Expose showToast globally
        window.showToast = showToast;

        // Handle flash messages from PHP
        <?php if (!empty($_SESSION['flash_success'])): ?>
            showToast('<?php echo addslashes($_SESSION['flash_success']); ?>', 'success');
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            showToast('<?php echo addslashes($_SESSION['flash_error']); ?>', 'error');
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        // Close mobile menu when clicking a link
        const mobileMenuLinks = mobileMenu.querySelectorAll('a');
        mobileMenuLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            });
        });
    </script>
