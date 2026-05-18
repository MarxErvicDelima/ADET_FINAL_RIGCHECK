<?php 
require_once 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif ($pdo === null) {
        $error = 'Database connection failed. Please try again later.';
    } else {
        try {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Default role is customer (role_id = 2)
            $stmt = $pdo->prepare("INSERT INTO User (first_name, last_name, email, password_hash, role_id) VALUES (?, ?, ?, ?, 2)");
            $stmt->execute([$first_name, $last_name, $email, $password_hash]);
            
            $success = 'Account created successfully! You can now log in.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Unique constraint violation
                $error = 'Email already registered.';
            } else {
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}

include 'includes/header.php'; 
?>

<section class="py-20 bg-gray-50 flex items-center justify-center min-h-[calc(100vh-80px)]">
    <div class="bg-white p-8 md:p-12 rounded-3xl shadow-2xl border border-gray-100 w-full max-w-lg animate-fade-in-up">
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center text-primary mx-auto mb-6 shadow-inner">
                <i class="fas fa-user-plus text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-800">Create Account</h2>
            <p class="text-gray-500 mt-2">Join RigCheck community today</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 text-sm font-medium flex items-start gap-3 border border-red-200 animate-fade-in-up" role="alert">
                <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php elseif ($success): ?>
            <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 text-sm font-medium flex items-start gap-3 border border-green-200 animate-fade-in-up" role="status">
                <i class="fas fa-check-circle mt-0.5 flex-shrink-0"></i>
                <div>
                    <p class="font-bold"><?php echo htmlspecialchars($success); ?></p>
                    <p class="text-xs mt-1">Redirecting to login page... <a href="login.php" class="underline font-bold">Click here</a> if not redirected automatically.</p>
                </div>
            </div>
            <script>
                setTimeout(() => window.location.href = 'login.php', 2000);
            </script>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-6" novalidate>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-bold mb-2 ml-1" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required placeholder="John" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" class="w-full px-4 py-4 rounded-xl border border-gray-200 focus:border-primary focus:outline-none focus:ring-4 focus:ring-green-50 transition-all text-gray-700 placeholder-gray-400" aria-label="First name">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2 ml-1" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required placeholder="Doe" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" class="w-full px-4 py-4 rounded-xl border border-gray-200 focus:border-primary focus:outline-none focus:ring-4 focus:ring-green-50 transition-all text-gray-700 placeholder-gray-400" aria-label="Last name">
                </div>
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2 ml-1" for="email">Email Address</label>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="email" id="email" name="email" required placeholder="john.doe@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 focus:border-primary focus:outline-none focus:ring-4 focus:ring-green-50 transition-all text-gray-700 placeholder-gray-400" aria-label="Email address">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-bold mb-2 ml-1" for="password">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 focus:border-primary focus:outline-none focus:ring-4 focus:ring-green-50 transition-all text-gray-700 placeholder-gray-400" aria-label="Password">
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2 ml-1" for="confirm_password">Confirm Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••" class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 focus:border-primary focus:outline-none focus:ring-4 focus:ring-green-50 transition-all text-gray-700 placeholder-gray-400" aria-label="Confirm password">
                    </div>
                </div>
            </div>
            <button type="submit" class="w-full bg-primary hover:bg-green-600 text-white py-4 rounded-xl font-bold transition-all shadow-lg active:scale-95 text-lg flex items-center justify-center gap-2" aria-label="Sign up">
                <i class="fas fa-user-plus"></i> Sign Up
            </button>
        </form>

        <div class="mt-8 pt-8 border-t border-gray-200 text-center">
            <p class="text-gray-600 font-medium">Already have an account?</p>
            <a href="login.php" class="text-primary font-bold hover:underline inline-block mt-2">
                <i class="fas fa-sign-in-alt mr-1"></i> Log In Here
            </a>
        </div>

        <p class="text-center text-gray-400 text-xs mt-6">By creating an account, you agree to our Terms of Service and Privacy Policy</p>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
