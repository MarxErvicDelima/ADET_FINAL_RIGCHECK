<?php
require_once 'includes/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order_id'] ?? 0);
$error = '';
$success = '';

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, p.payment_status, pm.method_name,
               GROUP_CONCAT(oli.quantity) as quantities,
               GROUP_CONCAT(p_tbl.name) as product_names
        FROM Orders o
        JOIN Payment p ON o.order_id = p.order_id
        JOIN Payment_method pm ON p.method_id = pm.method_id
        LEFT JOIN Order_Line_Item oli ON o.order_id = oli.order_id
        LEFT JOIN Product_Variant pv ON oli.variant_id = pv.variant_id
        LEFT JOIN Product p_tbl ON pv.product_id = p_tbl.product_id
        WHERE o.order_id = ? AND o.user_id = ?
        GROUP BY o.order_id
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
} catch (Exception $e) {
    $order = null;
}

if (!$order) {
    header('Location: my_orders.php');
    exit;
}

require_once 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_number = trim($_POST['reference_number'] ?? '');
    
    // Validation
    if (empty($reference_number)) {
        $error = 'Please enter a reference number';
    } elseif (strlen($reference_number) < 5) {
        $error = 'Reference number must be at least 5 characters';
    }

    // Handle file upload
    $proof_image_path = null;
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['proof_image'];
        $file_size = $file['size'];
        $file_type = $file['type'];
        $file_name = $file['name'];

        // Validation
        if ($file_size > 3 * 1024 * 1024) {
            $error = 'File size must not exceed 3MB';
        } elseif (!in_array($file_type, ['image/png', 'image/jpeg'])) {
            $error = 'File must be PNG or JPG';
        } else {
            // Create upload directory if not exists
            $upload_dir = __DIR__ . '/uploads/payments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate unique filename
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_name = 'payment_' . $order_id . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $unique_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $proof_image_path = 'uploads/payments/' . $unique_name;
            } else {
                $error = 'Failed to upload file';
            }
        }
    }

    // If no errors, update payment record
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE Payment 
                SET reference_number = ?, proof_image_path = ?
                WHERE order_id = ?
            ");
            $stmt->execute([$reference_number, $proof_image_path, $order_id]);
            
            $success = 'Payment proof submitted successfully! We will verify your payment shortly.';
            
            // Refresh order data
            $stmt = $pdo->prepare("
                SELECT o.*, p.payment_status, p.reference_number, p.proof_image_path, pm.method_name
                FROM Orders o
                JOIN Payment p ON o.order_id = p.order_id
                JOIN Payment_method pm ON p.method_id = pm.method_id
                WHERE o.order_id = ? AND o.user_id = ?
            ");
            $stmt->execute([$order_id, $user_id]);
            $order = $stmt->fetch();
        } catch (Exception $e) {
            $error = 'Error submitting payment: ' . $e->getMessage();
        }
    }
}

?>

<main style="background-color: var(--surface-gray); padding: 40px 20px; min-height: calc(100vh - 70px);">
    <div style="max-width: 1000px; margin: 0 auto;">
        
        <!-- Page Header -->
        <div style="margin-bottom: 30px;">
            <h1 style="font-size: 32px; font-weight: 700; margin-bottom: 10px;">Payment Verification</h1>
            <p style="color: var(--text-secondary);">Order #<?php echo htmlspecialchars($order['order_number']); ?></p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div style="
                background-color: var(--danger-red);
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            ">
                <div><i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i><?php echo htmlspecialchars($error); ?></div>
                <button onclick="this.parentElement.style.display='none'" style="
                    background: none;
                    border: none;
                    color: white;
                    cursor: pointer;
                    font-size: 16px;
                ">×</button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="
                background-color: var(--accent-green);
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            ">
                <div><i class="fas fa-check-circle" style="margin-right: 10px;"></i><?php echo htmlspecialchars($success); ?></div>
                <button onclick="this.parentElement.style.display='none'" style="
                    background: none;
                    border: none;
                    color: white;
                    cursor: pointer;
                    font-size: 16px;
                ">×</button>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 30px;">
            
            <!-- LEFT - PAYMENT FORM -->
            <div>
                
                <!-- Payment Status Card -->
                <div style="
                    background-color: var(--bg-white);
                    border-radius: 12px;
                    padding: 25px;
                    margin-bottom: 20px;
                    border-left: 6px solid var(--warning-amber);
                ">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div style="
                            width: 40px;
                            height: 40px;
                            background-color: var(--warning-amber);
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-size: 20px;
                        ">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div>
                            <p style="font-weight: 700; font-size: 16px;">Payment Pending Verification</p>
                            <p style="font-size: 12px; color: var(--text-secondary);">We're waiting for your payment proof</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Instructions -->
                <div style="
                    background-color: var(--bg-white);
                    border-radius: 12px;
                    padding: 25px;
                    margin-bottom: 20px;
                ">
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">
                        <i class="fas fa-info-circle" style="color: var(--primary-green); margin-right: 8px;"></i>
                        Payment Instructions
                    </h3>
                    
                    <div style="background-color: var(--surface-gray); padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                        <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 600;">Payment Method:</p>
                        <p style="font-size: 14px; font-weight: 700; color: var(--primary-green);">
                            <i class="fas fa-mobile" style="margin-right: 8px;"></i>
                            <?php echo htmlspecialchars($order['method_name']); ?>
                        </p>
                    </div>

                    <div style="background-color: var(--surface-gray); padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                        <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 600;">Amount to Pay:</p>
                        <p style="font-size: 24px; font-weight: 700; color: var(--primary-green);">₱<?php echo number_format($order['total_amount'], 2); ?></p>
                    </div>

                    <ol style="
                        list-style: decimal;
                        list-style-position: inside;
                        font-size: 13px;
                        color: var(--text-secondary);
                        line-height: 1.8;
                    ">
                        <li style="margin-bottom: 8px;">
                            <span style="color: var(--text-primary);">Send ₱<?php echo number_format($order['total_amount'], 2); ?> via <?php echo htmlspecialchars($order['method_name']); ?></span> to RigCheck's registered account
                        </li>
                        <li style="margin-bottom: 8px;">
                            <span style="color: var(--text-primary);">Take a screenshot or photo of the transaction confirmation</span>
                        </li>
                        <li style="margin-bottom: 8px;">
                            <span style="color: var(--text-primary);">Enter the reference number from the transaction</span>
                        </li>
                        <li>
                            <span style="color: var(--text-primary);">Upload the payment proof image</span>
                        </li>
                    </ol>
                </div>

                <!-- Payment Form -->
                <form method="POST" enctype="multipart/form-data" style="
                    background-color: var(--bg-white);
                    border-radius: 12px;
                    padding: 25px;
                ">
                    
                    <!-- Reference Number Input -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 700; margin-bottom: 8px; font-size: 14px;">
                            Reference Number <span style="color: var(--danger-red);">*</span>
                        </label>
                        <input type="text" name="reference_number" required value="<?php echo htmlspecialchars($_POST['reference_number'] ?? ''); ?>" style="
                            width: 100%;
                            padding: 12px;
                            border: 2px solid var(--border-light);
                            border-radius: 8px;
                            font-size: 14px;
                            font-family: monospace;
                        " placeholder="e.g., GCash-123456789" onchange="if(this.value.length < 5) this.style.borderColor='var(--danger-red)'; else this.style.borderColor='var(--border-light)';">
                        <p style="font-size: 12px; color: var(--text-secondary); margin-top: 6px;">
                            The reference number from your payment transaction (e.g., GCash reference code)
                        </p>
                    </div>

                    <!-- Payment Proof Upload -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 700; margin-bottom: 8px; font-size: 14px;">
                            Payment Proof <span style="color: var(--danger-red);">*</span>
                        </label>
                        <div style="
                            border: 2px dashed var(--border-light);
                            border-radius: 8px;
                            padding: 30px;
                            text-align: center;
                            background-color: var(--surface-gray);
                            cursor: pointer;
                            transition: all 0.3s ease;
                        " id="upload-area" onmouseover="this.style.borderColor='var(--primary-green)'; this.style.backgroundColor='#f0f8f0'" onmouseout="this.style.borderColor='var(--border-light)'; this.style.backgroundColor='var(--surface-gray)'">
                            <input type="file" name="proof_image" id="proof_image" accept="image/png,image/jpeg" style="display: none;" onchange="updateFileName(this)">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: var(--primary-green); display: block; margin-bottom: 12px;"></i>
                            <p style="font-weight: 600; color: var(--text-primary); margin-bottom: 6px;">Drop your screenshot here or click to browse</p>
                            <p style="font-size: 12px; color: var(--text-secondary);">PNG or JPG • Max 3MB</p>
                            <p id="file-name" style="font-size: 12px; color: var(--primary-green); margin-top: 8px; font-weight: 600;"></p>
                        </div>
                        <script>
                            const uploadArea = document.getElementById('upload-area');
                            const fileInput = document.getElementById('proof_image');
                            
                            uploadArea.addEventListener('click', () => fileInput.click());
                            
                            uploadArea.addEventListener('dragover', (e) => {
                                e.preventDefault();
                                uploadArea.style.borderColor = 'var(--primary-green)';
                                uploadArea.style.backgroundColor = '#f0f8f0';
                            });
                            
                            uploadArea.addEventListener('dragleave', () => {
                                uploadArea.style.borderColor = 'var(--border-light)';
                                uploadArea.style.backgroundColor = 'var(--surface-gray)';
                            });
                            
                            uploadArea.addEventListener('drop', (e) => {
                                e.preventDefault();
                                uploadArea.style.borderColor = 'var(--border-light)';
                                uploadArea.style.backgroundColor = 'var(--surface-gray)';
                                
                                const files = e.dataTransfer.files;
                                if (files.length > 0) {
                                    fileInput.files = files;
                                    updateFileName(fileInput);
                                }
                            });
                            
                            function updateFileName(input) {
                                const fileName = document.getElementById('file-name');
                                if (input.files && input.files.length > 0) {
                                    fileName.textContent = '✓ ' + input.files[0].name;
                                } else {
                                    fileName.textContent = '';
                                }
                            }
                        </script>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" style="
                        width: 100%;
                        padding: 14px;
                        background-color: var(--primary-green);
                        color: white;
                        border: none;
                        border-radius: 8px;
                        font-weight: 700;
                        font-size: 14px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.backgroundColor='#235a23'; this.style.transform='scale(1.02)'" onmouseout="this.style.backgroundColor='var(--primary-green)'; this.style.transform='scale(1)'">
                        <i class="fas fa-paper-plane" style="margin-right: 8px;"></i>Submit Payment Proof
                    </button>
                </form>

            </div>

            <!-- RIGHT - ORDER SUMMARY -->
            <div style="
                background-color: var(--bg-white);
                border-radius: 12px;
                padding: 25px;
                height: fit-content;
                position: sticky;
                top: 80px;
            ">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 20px;">Order Summary</h3>

                <!-- Order Number -->
                <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border-light);">
                    <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">Order Number</p>
                    <p style="font-size: 15px; font-weight: 700; color: var(--primary-green); font-family: monospace;">
                        <?php echo htmlspecialchars($order['order_number']); ?>
                    </p>
                </div>

                <!-- Order Items -->
                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-light);">
                    <p style="font-size: 12px; color: var(--text-secondary); font-weight: 600; margin-bottom: 10px;">Items</p>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php
                        try {
                            $stmt = $pdo->prepare("
                                SELECT oli.quantity, p.name as product_name, oli.unit_price
                                FROM Order_Line_Item oli
                                JOIN Product_Variant pv ON oli.variant_id = pv.variant_id
                                JOIN Product p ON pv.product_id = p.product_id
                                WHERE oli.order_id = ?
                            ");
                            $stmt->execute([$order_id]);
                            $line_items = $stmt->fetchAll();
                            
                            foreach ($line_items as $item):
                        ?>
                        <div style="display: flex; justify-content: space-between; font-size: 12px;">
                            <span><?php echo htmlspecialchars($item['product_name']); ?> x<?php echo $item['quantity']; ?></span>
                            <span style="font-weight: 600;">₱<?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php
                        } catch (Exception $e) {
                            echo '<p style="color: var(--danger-red);">Error loading items: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Total Amount -->
                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border-light);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 14px; font-weight: 600;">Total Amount:</span>
                        <span style="font-size: 20px; font-weight: 700; color: var(--primary-green);">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>

                <!-- Important Note -->
                <div style="
                    background-color: var(--surface-gray);
                    border-left: 4px solid var(--warning-amber);
                    padding: 12px;
                    border-radius: 4px;
                    font-size: 11px;
                ">
                    <p style="color: var(--text-secondary); line-height: 1.5; margin-bottom: 8px;">
                        <i class="fas fa-exclamation-triangle" style="color: var(--warning-amber); margin-right: 6px;"></i>
                        Payment verification typically takes 1-3 hours during business hours. We'll send you a confirmation email once verified.
                    </p>
                    <p style="color: var(--text-secondary); line-height: 1.5;">
                        <i class="fas fa-info-circle" style="color: var(--primary-green); margin-right: 6px;"></i>
                        Your order is reserved for 48 hours. Please ensure payment is submitted within this period.
                    </p>
                </div>

            </div>

        </div>

    </div>
</main>

<?php require_once 'includes/footer.php'; ?>

