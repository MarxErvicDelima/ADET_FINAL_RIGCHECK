<?php 
require_once 'includes/config.php';

// Handle actions (MUST BE BEFORE ANY HTML OUTPUT)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $cart_key = (string)($_POST['cart_key'] ?? '');
        if ($cart_key === '') {
            header('Location: cart.php');
            exit();
        }
        
        if ($_POST['action'] == 'update') {
            $qty = (int)$_POST['quantity'];
            if ($qty > 0) {
                if (isset($_SESSION['cart'][$cart_key])) {
                    $_SESSION['cart'][$cart_key]['quantity'] = $qty;
                    $_SESSION['success_message'] = '✓ Cart updated successfully!';
                }
            } else {
                unset($_SESSION['cart'][$cart_key]);
                $_SESSION['success_message'] = '✓ Item removed from cart!';
            }
        } elseif ($_POST['action'] == 'remove') {
            if (isset($_SESSION['cart'][$cart_key])) {
                $item_name = $_SESSION['cart'][$cart_key]['name'];
                unset($_SESSION['cart'][$cart_key]);
                $_SESSION['success_message'] = '✓ ' . $item_name . ' has been removed from your cart!';
            }
        }
        header('Location: cart.php');
        exit();
    }
    
    // Handle Checkout Selected
    if (isset($_POST['checkout_selected'])) {
        $selected_ids = $_POST['selected_items'] ?? [];
        $order_type = $_POST['order_type'] ?? 'standard';
        if (!empty($selected_ids)) {
            $_SESSION['checkout_items'] = $selected_ids;
            $_SESSION['order_type'] = $order_type;
            header('Location: checkout.php');
            exit();
        } else {
            $error = "Please select at least one item to checkout.";
        }
    }
}

include 'includes/header.php'; 

$cart_items = $_SESSION['cart'] ?? [];
?>

<section class="py-12 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-10">
            <div>
                <h1 class="text-4xl font-bold text-gray-800">Your Shopping <span class="text-primary">Cart</span></h1>
                <p class="text-gray-500 font-medium mt-2"><?php echo count($cart_items); ?> item<?php echo count($cart_items) != 1 ? 's' : ''; ?> in your bag</p>
            </div>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="bg-white rounded-3xl p-12 md:p-20 text-center shadow-sm border border-gray-100 animate-fade-in-up">
                <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center text-primary mx-auto mb-8 shadow-inner">
                    <i class="fas fa-shopping-basket text-5xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Your cart is empty</h2>
                <p class="text-gray-500 mb-8 text-lg">Looks like you haven't added any high-performance gear yet.</p>
                <a href="products.php" class="bg-primary text-white px-10 py-4 rounded-xl font-bold hover:bg-green-600 transition-all shadow-lg inline-block text-lg">
                    <i class="fas fa-shopping-bag mr-2"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>
            <form action="cart.php" method="POST" id="cart-form">
                <?php if (isset($error)): ?>
                    <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 font-medium flex items-start gap-3 border border-red-200 animate-fade-in-up" role="alert">
                        <i class="fas fa-exclamation-circle mt-1 flex-shrink-0"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Store order type for checkout -->
                <input type="hidden" name="order_type" id="order_type_input" value="standard">

                <div class="flex flex-col lg:flex-row gap-8 md:gap-12">
                    <!-- Cart Items List -->
                    <div class="lg:w-2/3 space-y-6">
                        <div class="flex items-center justify-between px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-widest bg-gray-50 rounded-xl border border-gray-100">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" id="select-all" class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary mr-3 cursor-pointer" aria-label="Select all items">
                                Select All
                            </label>
                            <span>Item Details</span>
                        </div>

                        <?php foreach ($cart_items as $id => $item): ?>
                            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-center gap-6 group hover:shadow-md transition-all relative">
                                <!-- Selection Checkbox -->
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 md:static md:translate-y-0">
                                    <input type="checkbox" name="selected_items[]" value="<?php echo $id; ?>" class="item-checkbox w-6 h-6 text-primary rounded-lg border-gray-300 focus:ring-primary cursor-pointer transition-all" onchange="updateSummary()">
                                </div>

                                <div class="w-24 h-24 flex-shrink-0 bg-gray-50 rounded-xl overflow-hidden border border-gray-100 ml-8 md:ml-0">
                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                </div>
                                
                                <div class="flex-grow text-center md:text-left">
                                    <div class="flex items-center justify-center md:justify-start gap-2 mb-1">
                                        <h3 class="text-lg font-bold text-gray-800"><?php echo $item['name']; ?></h3>
                                        <?php if ($item['is_preorder']): ?>
                                            <span class="bg-yellow-100 text-yellow-600 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Pre-order</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-primary font-bold text-base">₱<?php echo number_format($item['price'], 2); ?></p>
                                </div>

                                <div class="flex items-center gap-6">
                                    <div class="flex items-center border border-gray-200 rounded-xl bg-gray-50 p-1">
                                        <button type="button" onclick='updateQty(<?php echo json_encode((string)$id); ?>, <?php echo $item['quantity'] - 1; ?>)' class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-primary transition-colors">
                                            <i class="fas fa-minus text-xs"></i>
                                        </button>
                                        <input type="text" value="<?php echo $item['quantity']; ?>" readonly class="w-10 text-center bg-transparent font-bold text-gray-700 text-sm">
                                        <button type="button" onclick='updateQty(<?php echo json_encode((string)$id); ?>, <?php echo $item['quantity'] + 1; ?>)' class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-primary transition-colors">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="text-right min-w-[110px]">
                                        <p class="text-lg font-extrabold text-dark">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                        <button type="button" onclick='removeItem(<?php echo json_encode((string)$id); ?>)' class="text-red-400 hover:text-red-600 text-xs font-bold mt-1 transition-colors">Remove</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Summary -->
                    <div class="lg:w-1/3">
                        <div class="bg-white p-10 rounded-3xl shadow-sm border border-gray-100 sticky top-28">
                            <h3 class="text-2xl font-bold mb-8 text-gray-800 border-b border-gray-100 pb-4">Order Summary</h3>
                            
                            <div class="space-y-6 mb-8" id="summary-details">
                                <div class="flex justify-between text-gray-600">
                                    <span>Selected Items</span>
                                    <span id="selected-count" class="font-bold">0</span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Subtotal</span>
                                    <span id="selected-total" class="font-bold">₱0.00</span>
                                </div>

                                <!-- Order Type Selection -->
                                <div class="border-t border-gray-100 pt-6">
                                    <label class="text-sm text-gray-600 font-bold uppercase tracking-widest block mb-3">Order Type</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary hover:bg-green-50 transition-all has-[:checked]:border-primary has-[:checked]:bg-green-50">
                                            <input type="radio" name="order_type" value="standard" checked onchange="updateSummary()" class="w-4 h-4 text-primary">
                                            <div class="ml-3 flex-grow">
                                                <span class="font-bold text-gray-800">Standard Pickup</span>
                                                <p class="text-[11px] text-gray-500">Regular stock availability</p>
                                            </div>
                                        </label>
                                        <label class="flex items-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-yellow-400 hover:bg-yellow-50 transition-all has-[:checked]:border-yellow-400 has-[:checked]:bg-yellow-50">
                                            <input type="radio" name="order_type" value="preorder" onchange="updateSummary()" class="w-4 h-4 text-yellow-500">
                                            <div class="ml-3 flex-grow">
                                                <span class="font-bold text-gray-800">Pre-order</span>
                                                <p class="text-[11px] text-gray-500">₱500 downpayment required</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div id="preorder-notice" class="hidden bg-yellow-50 border border-yellow-200 rounded-xl p-3">
                                    <div class="flex justify-between text-yellow-700 font-bold mb-2 text-sm">
                                        <span>Reservation Fee (Pre-order)</span>
                                        <span id="reservation-fee">₱500.00</span>
                                    </div>
                                    <p class="text-[10px] text-gray-600 italic leading-tight">* Mandatory ₱500 downpayment for pre-order items. Balance due upon availability.</p>
                                </div>

                                <div class="border-t border-gray-100 pt-6 flex justify-between items-end">
                                    <div>
                                        <span class="text-sm text-gray-400 block">Total Amount</span>
                                        <span class="text-2xl font-extrabold text-primary" id="grand-total">₱0.00</span>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="checkout_selected" id="checkout-btn" disabled class="w-full bg-gray-300 text-white py-5 rounded-2xl font-bold transition-all shadow-xl block text-center text-lg cursor-not-allowed">
                                Checkout Selected <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                            
                            <div class="mt-8 flex items-center justify-center gap-4 text-gray-400 text-sm">
                                <span class="font-bold uppercase tracking-widest text-xs">Accepted:</span>
                                <i class="fas fa-money-bill-wave text-2xl" title="Cash"></i>
                                <i class="fas fa-mobile-alt text-2xl" title="GCash/Maya"></i>
                                <i class="fas fa-shield-alt text-2xl" title="Secure"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<!-- Hidden actions form -->
<form id="action-form" action="cart.php" method="POST" style="display:none;">
    <input type="hidden" name="cart_key" id="action-cart-key">
    <input type="hidden" name="action" id="action-type">
    <input type="hidden" name="quantity" id="action-quantity">
</form>

<script>
const cartData = <?php echo json_encode($cart_items); ?>;

function updateQty(id, qty) {
    document.getElementById('action-cart-key').value = String(id);
    document.getElementById('action-type').value = 'update';
    document.getElementById('action-quantity').value = qty;
    document.getElementById('action-form').submit();
}

function removeItem(id) {
    const item = cartData[id];
    showModal('confirm', 'Remove Item?', `Are you sure you want to remove <strong>${item.name}</strong> from your cart?`, () => {
        document.getElementById('action-cart-key').value = String(id);
        document.getElementById('action-type').value = 'remove';
        document.getElementById('action-form').submit();
    });
}

function updateSummary() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const checkoutBtn = document.getElementById('checkout-btn');
    const orderTypeRadios = document.querySelectorAll('input[name="order_type"]');
    const selectedOrderType = Array.from(orderTypeRadios).find(r => r.checked)?.value || 'standard';
    
    let total = 0;
    let count = 0;

    checkboxes.forEach(cb => {
        const id = cb.value;
        const item = cartData[id];
        total += item.price * item.quantity;
        count += item.quantity;
    });

    document.getElementById('selected-count').textContent = checkboxes.length + ' item(s)';
    document.getElementById('selected-total').textContent = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
    
    // Only add fee for PRE-ORDER
    let fee = 0;
    if (selectedOrderType === 'preorder') {
        fee = 500;
    }
    
    const finalTotal = total + fee;
    document.getElementById('grand-total').textContent = '₱' + finalTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
    
    const notice = document.getElementById('preorder-notice');
    if (selectedOrderType === 'preorder') {
        notice.classList.remove('hidden');
    } else {
        notice.classList.add('hidden');
    }

    if (checkboxes.length > 0) {
        checkoutBtn.disabled = false;
        checkoutBtn.classList.remove('bg-gray-300', 'cursor-not-allowed');
        checkoutBtn.classList.add('bg-primary', 'hover:bg-green-600', 'transform', 'hover:-translate-y-1');
        checkoutBtn.innerHTML = (selectedOrderType === 'preorder' ? 'Pre-order Selected' : 'Checkout Selected') + ' <i class="fas fa-arrow-right ml-2"></i>';
    } else {
        checkoutBtn.disabled = true;
        checkoutBtn.classList.add('bg-gray-300', 'cursor-not-allowed');
        checkoutBtn.classList.remove('bg-primary', 'hover:bg-green-600', 'transform', 'hover:-translate-y-1');
        checkoutBtn.innerHTML = 'Checkout Selected <i class="fas fa-arrow-right ml-2"></i>';
    }
}

document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
    updateSummary();
});

// Initial summary update
document.addEventListener('DOMContentLoaded', updateSummary);

// Update hidden order type input when radio changes
document.querySelectorAll('input[name="order_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('order_type_input').value = this.value;
        updateSummary();
    });
});

// Update order type in session before checkout
document.getElementById('cart-form')?.addEventListener('submit', function(e) {
    if (e.submitter?.name === 'checkout_selected') {
        const orderType = document.querySelector('input[name="order_type"]:checked').value;
        // Create hidden input for order type
        if (!document.getElementById('order_type_input').value) {
            document.getElementById('order_type_input').value = orderType;
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
