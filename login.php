<?php 
require_once 'includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($pdo === null) {
        $error = 'Database connection failed. Please try again later.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM User WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['role_id'] = $user['role_id'];
                
                // Redirect based on role
                if ($user['role_id'] == 1) {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again later.';
        }
    }
}

include 'includes/header.php'; 
?>

<section class="py-20 bg-gray-50 flex items-center justify-center min-h-[calc(100vh-80px)]">
    <div class="bg-white p-8 md:p-12 rounded-3xl shadow-2xl border border-gray-100 w-full max-w-md animate-fade-in-up">
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center text-primary mx-auto mb-6 shadow-inner">
                <i class="fas fa-lock text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-800">Welcome Back</h2>
            <p class="text-gray-500 mt-2">Log in to your RigCheck account</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 text-sm font-medium flex items-start gap-3 border border-red-200 animate-fade-in-up" role="alert">
                <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-6" novalidate>
            <div>
                <label class="block text-gray-700 font-bold mb-2 ml-1" for="email">Email Address</label>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="email" id="email" name="email" required placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 focus:border-primary focus:outline-none focus:ring-4 focus:ring-green-50 transition-all text-gray-700 placeholder-gray-400" aria-label="Email address">
                </div>
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2 ml-1" for="password">Password</label>
                <div class="relative">
                    <i class="fas fa-key absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 focus:border-primary focus:outline-none focus:ring-4 focus:ring-green-50 transition-all text-gray-700 placeholder-gray-400" aria-label="Password">
                </div>
                <div class="text-right mt-2">
                    <a href="#" class="text-primary text-sm font-bold hover:underline transition">Forgot Password?</a>
                </div>
            </div>
            <button type="submit" class="w-full bg-primary hover:bg-green-600 text-white py-4 rounded-xl font-bold transition-all shadow-lg active:scale-95 text-lg flex items-center justify-center gap-2" aria-label="Log in">
                <i class="fas fa-sign-in-alt"></i> Log In
            </button>
        </form>

        <div class="mt-8 pt-8 border-t border-gray-200 text-center">
            <p class="text-gray-600 font-medium mb-4">Don't have an account?</p>
            <a href="register.php" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 py-4 rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                <i class="fas fa-user-plus"></i> Create Account
            </a>
        </div>

        <p class="text-center text-gray-400 text-xs mt-6">By logging in, you agree to our Terms of Service and Privacy Policy</p>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
