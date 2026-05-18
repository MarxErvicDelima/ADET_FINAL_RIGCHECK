<?php 
require_once 'includes/config.php';
include 'includes/header.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?msg=Please login to view your profile');
    exit();
}

// Redirect admin users
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
    header('Location: admin_dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ensure database connection is available
if ($pdo === null) {
    die("Database connection failed. Please try again later.");
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT u.*, a.street_address, a.barangay_dist, a.municipality, a.province, a.postal_code 
                           FROM User u 
                           LEFT JOIN Address a ON u.address_id = a.address_id 
                           WHERE u.user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: login.php?error=user_not_found');
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching profile: " . $e->getMessage();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $street_address = trim($_POST['street_address'] ?? '');
    $barangay_dist = trim($_POST['barangay_dist'] ?? '');
    $municipality = trim($_POST['municipality'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');

    if (empty($first_name) || empty($last_name)) {
        $error = 'First name and last name are required.';
    } else {
        try {
            // Update User table
            $stmt = $pdo->prepare("UPDATE User SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?");
            $stmt->execute([$first_name, $last_name, $phone, $user_id]);

            // Update or create Address
            if ($user['address_id']) {
                $stmt = $pdo->prepare("UPDATE Address SET street_address = ?, barangay_dist = ?, municipality = ?, province = ?, postal_code = ? WHERE address_id = ?");
                $stmt->execute([$street_address, $barangay_dist, $municipality, $province, $postal_code, $user['address_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO Address (street_address, barangay_dist, municipality, province, postal_code) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$street_address, $barangay_dist, $municipality, $province, $postal_code]);
                $address_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("UPDATE User SET address_id = ? WHERE user_id = ?");
                $stmt->execute([$address_id, $user_id]);
            }

            $_SESSION['first_name'] = $first_name;
            $_SESSION['success_message'] = '✓ Profile updated successfully!';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT u.*, a.street_address, a.barangay_dist, a.municipality, a.province, a.postal_code 
                                   FROM User u 
                                   LEFT JOIN Address a ON u.address_id = a.address_id 
                                   WHERE u.user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            header('Location: user_profile.php');
            exit();
        } catch (PDOException $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // Verify current password
        if (!password_verify($current_password, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (empty($new_password) || empty($confirm_password)) {
            $error = 'New password fields cannot be empty.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE User SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$new_hash, $user_id]);
            $_SESSION['success_message'] = '✓ Password changed successfully!';
            header('Location: user_profile.php');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Error changing password: ' . $e->getMessage();
    }
}
?>

<section class="py-12 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <!-- Page Title -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-12 gap-6">
            <div>
                <h1 class="text-4xl font-bold text-gray-800">My <span class="text-primary">Profile</span></h1>
                <p class="text-gray-500 mt-2">Manage your account information and settings</p>
            </div>
            <a href="my_orders.php" class="bg-white text-primary border-2 border-primary px-6 py-3 rounded-xl font-bold hover:bg-primary hover:text-white transition-all shadow-sm">
                <i class="fas fa-history mr-2"></i> View Orders
            </a>
        </div>

        <!-- Alert Messages -->
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-8 flex items-center animate-fade-in-up">
                <i class="fas fa-exclamation-circle text-2xl mr-4"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Profile Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Profile Header -->
                    <div class="bg-gradient-to-r from-primary to-green-600 p-8 text-center text-white">
                        <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center text-primary text-4xl mx-auto mb-4 shadow-lg">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                        <p class="text-green-100 mt-2"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>

                    <!-- Profile Info -->
                    <div class="p-6">
                        <div class="mb-6 pb-6 border-b border-gray-100">
                            <p class="text-gray-500 text-sm font-bold uppercase tracking-wide mb-2">Account Type</p>
                            <p class="text-gray-800 font-semibold">Customer</p>
                        </div>

                        <div class="mb-6 pb-6 border-b border-gray-100">
                            <p class="text-gray-500 text-sm font-bold uppercase tracking-wide mb-2">Member Since</p>
                            <p class="text-gray-800 font-semibold"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                        </div>

                        <div>
                            <p class="text-gray-500 text-sm font-bold uppercase tracking-wide mb-2">Status</p>
                            <span class="inline-block bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-bold">
                                <i class="fas fa-check-circle mr-2"></i><?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-lightning-bolt text-primary mr-3"></i> Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <a href="my_orders.php" class="block w-full bg-gray-50 hover:bg-primary hover:text-white text-gray-800 px-4 py-3 rounded-xl font-semibold transition-all text-center">
                            <i class="fas fa-shopping-bag mr-2"></i> My Orders
                        </a>
                        <a href="products.php" class="block w-full bg-gray-50 hover:bg-primary hover:text-white text-gray-800 px-4 py-3 rounded-xl font-semibold transition-all text-center">
                            <i class="fas fa-shopping-cart mr-2"></i> Continue Shopping
                        </a>
                        <a href="logout.php" class="block w-full bg-red-50 hover:bg-red-500 text-red-600 hover:text-white px-4 py-3 rounded-xl font-semibold transition-all text-center">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Forms Section -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Personal Information Form -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-user-edit text-primary mr-3"></i> Personal Information
                    </h3>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 font-bold mb-3">First Name</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-bold mb-3">Last Name</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors">
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-bold mb-3">Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50 text-gray-500 cursor-not-allowed">
                            <p class="text-gray-500 text-sm mt-2">Email cannot be changed</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-bold mb-3">Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Your phone number">
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-green-600 text-white px-6 py-4 rounded-xl font-bold transition-all shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </form>
                </div>

                <!-- Address Information Form -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-map-marker-alt text-primary mr-3"></i> Address Information
                    </h3>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_profile">

                        <div>
                            <label class="block text-gray-700 font-bold mb-3">Street Address</label>
                            <input type="text" name="street_address" value="<?php echo htmlspecialchars($user['street_address'] ?? ''); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Street address">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 font-bold mb-3">Barangay / District</label>
                                <input type="text" name="barangay_dist" value="<?php echo htmlspecialchars($user['barangay_dist'] ?? ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Barangay / District">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-bold mb-3">Municipality</label>
                                <input type="text" name="municipality" value="<?php echo htmlspecialchars($user['municipality'] ?? ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Municipality">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 font-bold mb-3">Province</label>
                                <input type="text" name="province" value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Province">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-bold mb-3">Postal Code</label>
                                <input type="text" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Postal code">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-green-600 text-white px-6 py-4 rounded-xl font-bold transition-all shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-save mr-2"></i> Save Address
                        </button>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-lock text-primary mr-3"></i> Change Password
                    </h3>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="change_password">

                        <div>
                            <label class="block text-gray-700 font-bold mb-3">Current Password</label>
                            <input type="password" name="current_password" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Enter your current password">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 font-bold mb-3">New Password</label>
                                <input type="password" name="new_password" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Enter new password">
                                <p class="text-gray-500 text-sm mt-2">At least 6 characters</p>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-bold mb-3">Confirm New Password</label>
                                <input type="password" name="confirm_password" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-primary transition-colors" placeholder="Confirm new password">
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-green-600 text-white px-6 py-4 rounded-xl font-bold transition-all shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-key mr-2"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.5s ease-out;
}
</style>

<?php include 'includes/footer.php'; ?>
