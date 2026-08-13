<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/config.php";

// Helper function to sanitize text input
if (!function_exists('sanitize_text')) {
    function sanitize_text($data) {
        if (empty($data)) return '';
        $data = trim($data);
        $data = stripslashes($data);
        $data = strip_tags($data);
        return $data;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- ADD MEDICINE ---
    if ($_POST['action'] === 'add_medicine') {
        $name             = sanitize_text($_POST['name'] ?? '');
        $category         = sanitize_text($_POST['category'] ?? '');
        $manufacture_date = !empty($_POST['manufacture_date']) ? $_POST['manufacture_date'] : null;
        $expiry_date      = $_POST['expiry_date'] ?? '';
        $stock            = (int)($_POST['stock'] ?? 0);
        $supplier         = sanitize_text($_POST['supplier'] ?? '');
        $price            = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : 0.00;
        $batch_number     = sanitize_text($_POST['batch_number'] ?? '');
        $dosage           = sanitize_text($_POST['dosage'] ?? '');
        $location         = sanitize_text($_POST['location'] ?? '');
        $min_stock        = (int)($_POST['min_stock'] ?? 5);
        $description      = sanitize_text($_POST['description'] ?? '');

        $errors = [];

        if (empty($name) || empty($expiry_date) || empty($manufacture_date)) {
            $errors[] = "Medicine name, manufacture date, and expiry date are required.";
        }

        if ($price <= 0) {
            $errors[] = "Price is required and must be greater than 0.";
        }

        if ($stock <= 0) {
            $errors[] = "Stock units must be greater than 0.";
        }

        $today = date('Y-m-d');
        if (!empty($manufacture_date) && $manufacture_date > $today) {
            $errors[] = "Manufacture date cannot be in the future.";
        }

        if (!empty($expiry_date) && $expiry_date <= $today) {
            $errors[] = "Expiry date must be in the future.";
        }

        if (!empty($manufacture_date) && !empty($expiry_date)) {
            if (strtotime($manufacture_date) >= strtotime($expiry_date)) {
                $errors[] = "Manufacture date must be earlier than the expiry date.";
            }
        }

        if (!empty($dosage) && !preg_match('/^\d+(\.\d+)?\s*(mg|ml)$/i', $dosage)) {
            $errors[] = "Dosage must be a valid number followed by 'mg' or 'ml' (e.g., 500mg, 10.5ml).";
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(" ", $errors);
        } else {
            try {
                $sql = "INSERT INTO medicines (name, category, manufacture_date, expiry_date, stock, supplier, price, batch_number, dosage, location, min_stock, description) 
                        VALUES (:name, :category, :mfg, :exp, :stock, :supplier, :price, :batch, :dosage, :location, :min_stock, :desc)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name'      => $name,
                    ':category'  => $category,
                    ':mfg'       => $manufacture_date,
                    ':exp'       => $expiry_date,
                    ':stock'     => $stock,
                    ':supplier'  => $supplier,
                    ':price'     => $price,
                    ':batch'     => $batch_number,
                    ':dosage'    => $dosage,
                    ':location'  => $location,
                    ':min_stock' => $min_stock,
                    ':desc'      => $description
                ]);

                $_SESSION['flash_success'] = "Medicine added successfully!";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Database Error: " . $e->getMessage();
            }
        }
    }

    // --- EDIT MEDICINE ---
    elseif ($_POST['action'] === 'edit_medicine') {
        $id               = (int)($_POST['id'] ?? 0);
        $name             = sanitize_text($_POST['name'] ?? '');
        $category         = sanitize_text($_POST['category'] ?? '');
        $manufacture_date = !empty($_POST['manufacture_date']) ? $_POST['manufacture_date'] : null;
        $expiry_date      = $_POST['expiry_date'] ?? '';
        $stock            = (int)($_POST['stock'] ?? 0);
        $supplier         = sanitize_text($_POST['supplier'] ?? '');
        $price            = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : 0.00;
        $batch_number     = sanitize_text($_POST['batch_number'] ?? '');
        $dosage           = sanitize_text($_POST['dosage'] ?? '');
        $location         = sanitize_text($_POST['location'] ?? '');
        $min_stock        = (int)($_POST['min_stock'] ?? 5);
        $description      = sanitize_text($_POST['description'] ?? '');

        $errors = [];

        if ($id <= 0 || empty($name) || empty($expiry_date) || empty($manufacture_date)) {
            $errors[] = "Valid Medicine ID, name, manufacture date, and expiry date are required.";
        }

        if ($price <= 0) {
            $errors[] = "Price is required and must be greater than 0.";
        }

        if ($stock <= 0) {
            $errors[] = "Stock units must be greater than 0.";
        }

        $today = date('Y-m-d');
        if (!empty($manufacture_date) && $manufacture_date > $today) {
            $errors[] = "Manufacture date cannot be in the future.";
        }

        if (!empty($expiry_date) && $expiry_date <= $today) {
            $errors[] = "Expiry date must be in the future.";
        }

        if (!empty($manufacture_date) && !empty($expiry_date)) {
            if (strtotime($manufacture_date) >= strtotime($expiry_date)) {
                $errors[] = "Manufacture date must be earlier than the expiry date.";
            }
        }

        if (!empty($dosage) && !preg_match('/^\d+(\.\d+)?\s*(mg|ml)$/i', $dosage)) {
            $errors[] = "Dosage must be a valid number followed by 'mg' or 'ml' (e.g., 500mg, 10.5ml).";
        }

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(" ", $errors);
        } else {
            try {
                $sql = "UPDATE medicines SET 
                            name = :name, category = :category, manufacture_date = :mfg, 
                            expiry_date = :exp, stock = :stock, supplier = :supplier, 
                            price = :price, batch_number = :batch, dosage = :dosage, 
                            location = :location, min_stock = :min_stock, description = :desc 
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id'        => $id,
                    ':name'      => $name,
                    ':category'  => $category,
                    ':mfg'       => $manufacture_date,
                    ':exp'       => $expiry_date,
                    ':stock'     => $stock,
                    ':supplier'  => $supplier,
                    ':price'     => $price,
                    ':batch'     => $batch_number,
                    ':dosage'    => $dosage,
                    ':location'  => $location,
                    ':min_stock' => $min_stock,
                    ':desc'      => $description
                ]);

                $_SESSION['flash_success'] = "Medicine updated successfully!";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Database Error: " . $e->getMessage();
            }
        }
    }

    // --- DELETE MEDICINE ---
    elseif ($_POST['action'] === 'delete_medicine') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $_SESSION['flash_success'] = "Medicine deleted successfully!";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Database Error: " . $e->getMessage();
            }
        }
    }

    header("Location: medicines.php");
    exit;
}

// Extract & Clear Flash Messages
$db_error = $_SESSION['flash_error'] ?? '';
$success_msg = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Fetch Medicines Inventory
$medicines = [];
try {
    $sql = "SELECT *, DATEDIFF(expiry_date, CURDATE()) AS days_to_expiry 
            FROM medicines 
            ORDER BY id DESC";
    $stmt = $pdo->query($sql);
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_error = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines Inventory - PharmaCore</title>
    <link rel="shortcut icon" href="../assets/images/pharmacore_icon.svg" type="image/svg+xml">
    
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/medicines.css">
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">

            <div class="page-header">
                <h2>Medicines Inventory</h2>
            </div>

            <?php if (!empty($db_error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($db_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>

            <div class="medicine-grid">

                <!-- 1. Add Medicine Tile -->
                <div class="med-card add-card" onclick="openAddModal()">
                    <div class="add-icon">
                        <img src="../assets/images/add_icon.svg" alt="Add" class="icon-img">
                    </div>
                    <span class="add-text">Add Medicine</span>
                </div>

                <!-- 2. Dynamic Medicine Cards -->
                <?php foreach ($medicines as $med): ?>
                    <?php 
                        $stock = (int)$med['stock'];
                        $json_data = htmlspecialchars(json_encode($med), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="med-card clickable" onclick="openDetailsModal(this)" data-medicine='<?php echo $json_data; ?>'>
                        <div class="med-collapsed">
                            <div class="med-icon">
                                <img src="../assets/images/medicine_icon.svg" alt="Medicine" class="icon-img">
                            </div>
                            <h3 class="med-title"><?php echo htmlspecialchars($med['name']); ?></h3>
                            <span class="badge-compact"><?php echo $stock; ?> Units</span>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

        </div>
    </main>

    <!-- ================================================================ -->
    <!-- MODAL 1: ADD NEW MEDICINE                                        -->
    <!-- ================================================================ -->
    <div class="modal-overlay" id="addMedicineModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Medicine</h3>
                <button type="button" class="close-btn" onclick="closeModal('addMedicineModal')">
                    <img src="../assets/images/close_icon.svg" alt="Close" class="close-icon">
                </button>
            </div>

            <form action="medicines.php" method="POST" onsubmit="return validateForm(this)" novalidate>
                <input type="hidden" name="action" value="add_medicine">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Medicine Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. Paracetamol" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="Analgesic">Analgesic</option>
                            <option value="Antibiotic">Antibiotic</option>
                            <option value="Antiseptic">Antiseptic</option>
                            <option value="Supplement">Supplement / Vitamin</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dosage">Dosage / Strength (e.g. 500mg, 10.5ml)</label>
                        <input type="text" id="dosage" name="dosage" placeholder="e.g. 500mg, 10.5ml" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="batch_number">Batch / Lot Number</label>
                        <input type="text" id="batch_number" name="batch_number" placeholder="e.g. BATCH-992" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="manufacture_date">Manufacture Date *</label>
                        <input type="date" id="manufacture_date" name="manufacture_date" required max="<?= date('Y-m-d'); ?>" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="expiry_date">Expiry Date *</label>
                        <input type="date" id="expiry_date" name="expiry_date" required oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="stock">No. of Items (Stock) *</label>
                        <input type="number" id="stock" name="stock" value="1" min="1" required oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="min_stock">Low Stock Alert Threshold</label>
                        <input type="number" id="min_stock" name="min_stock" value="10" min="1" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="price">Price *</label>
                        <input type="number" step="0.01" id="price" name="price" required placeholder="0.00" min="0.01" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="supplier">Supplier</label>
                        <input type="text" id="supplier" name="supplier" placeholder="e.g. MedicoPharma Ltd." oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="location">Rack / Storage Location</label>
                        <input type="text" id="location" name="location" placeholder="e.g. Rack A-2" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group full">
                        <label for="description">Optional Description</label>
                        <textarea id="description" name="description" rows="3" placeholder="Enter additional notes or usage details..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('addMedicineModal')">Cancel</button>
                    <button type="submit" class="btn-submit">Save Medicine</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- MODAL 2: VIEW MEDICINE DETAILS                                    -->
    <!-- ================================================================ -->
    <div class="modal-overlay" id="viewDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="det_name">Medicine Details</h3>
                <button type="button" class="close-btn" onclick="closeModal('viewDetailsModal')">
                    <img src="../assets/images/close_icon.svg" alt="Close" class="close-icon">
                </button>
            </div>

            <div class="details-body">
                <div class="detail-row"><span>Category:</span> <strong id="det_category">N/A</strong></div>
                <div class="detail-row"><span>Dosage / Strength:</span> <strong id="det_dosage">N/A</strong></div>
                <div class="detail-row"><span>Batch Number:</span> <strong id="det_batch">N/A</strong></div>
                <div class="detail-row"><span>Manufacture Date:</span> <strong id="det_mfg">N/A</strong></div>
                <div class="detail-row"><span>Expiry Date:</span> <strong id="det_exp">N/A</strong></div>
                <div class="detail-row"><span>Current Stock:</span> <strong id="det_stock">0 Units</strong></div>
                <div class="detail-row"><span>Price:</span> <strong id="det_price">Rs 0.00</strong></div>
                <div class="detail-row"><span>Supplier:</span> <strong id="det_supplier">N/A</strong></div>
                <div class="detail-row"><span>Location:</span> <strong id="det_location">N/A</strong></div>
                <div class="detail-row full"><span>Description:</span> <p id="det_desc">N/A</p></div>
            </div>

            <div class="modal-footer">
                <form method="POST" action="medicines.php" onsubmit="return confirm('Are you sure you want to delete this medicine?');">
                    <input type="hidden" name="action" value="delete_medicine">
                    <input type="hidden" name="id" id="det_delete_id">
                    <button type="submit" class="btn-danger">Delete</button>
                </form>

                <button type="button" class="btn-edit" onclick="triggerEditModal()">Edit</button>
                <button type="button" class="btn-cancel" onclick="closeModal('viewDetailsModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- MODAL 3: EDIT MEDICINE                                           -->
    <!-- ================================================================ -->
    <div class="modal-overlay" id="editMedicineModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Medicine</h3>
                <button type="button" class="close-btn" onclick="closeModal('editMedicineModal')">
                    <img src="../assets/images/close_icon.svg" alt="Close" class="close-icon">
                </button>
            </div>

            <form action="medicines.php" method="POST" onsubmit="return validateForm(this)" novalidate>
                <input type="hidden" name="action" value="edit_medicine">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_name">Medicine Name *</label>
                        <input type="text" id="edit_name" name="name" required oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_category">Category</label>
                        <select id="edit_category" name="category">
                            <option value="Analgesic">Analgesic</option>
                            <option value="Antibiotic">Antibiotic</option>
                            <option value="Antiseptic">Antiseptic</option>
                            <option value="Supplement">Supplement / Vitamin</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_dosage">Dosage / Strength (e.g. 500mg, 10.5ml)</label>
                        <input type="text" id="edit_dosage" name="dosage" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_batch_number">Batch / Lot Number</label>
                        <input type="text" id="edit_batch_number" name="batch_number" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_manufacture_date">Manufacture Date *</label>
                        <input type="date" id="edit_manufacture_date" name="manufacture_date" required max="<?= date('Y-m-d'); ?>" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_expiry_date">Expiry Date *</label>
                        <input type="date" id="edit_expiry_date" name="expiry_date" required oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_stock">No. of Items (Stock) *</label>
                        <input type="number" id="edit_stock" name="stock" min="1" required oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_min_stock">Low Stock Alert Threshold</label>
                        <input type="number" id="edit_min_stock" name="min_stock" min="1" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_price">Price *</label>
                        <input type="number" step="0.01" id="edit_price" name="price" required min="0.01" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_supplier">Supplier</label>
                        <input type="text" id="edit_supplier" name="supplier" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_location">Rack / Storage Location</label>
                        <input type="text" id="edit_location" name="location" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group full">
                        <label for="edit_description">Optional Description</label>
                        <textarea id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editMedicineModal')">Cancel</button>
                    <button type="submit" class="btn-submit">Update Medicine</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let currentMedicineData = null;

    function openAddModal() {
        document.getElementById('addMedicineModal').classList.add('active');
    }

    function openDetailsModal(card) {
        currentMedicineData = JSON.parse(card.getAttribute('data-medicine'));

        document.getElementById('det_name').textContent = currentMedicineData.name || 'N/A';
        document.getElementById('det_category').textContent = currentMedicineData.category || 'N/A';
        document.getElementById('det_dosage').textContent = currentMedicineData.dosage || 'N/A';
        document.getElementById('det_batch').textContent = currentMedicineData.batch_number || 'N/A';
        document.getElementById('det_mfg').textContent = currentMedicineData.manufacture_date || 'N/A';
        document.getElementById('det_exp').textContent = currentMedicineData.expiry_date || 'N/A';
        
        const stockCount = parseInt(currentMedicineData.stock, 10) || 0;
        document.getElementById('det_stock').textContent = stockCount + (stockCount === 1 ? ' Unit' : ' Units');
        
        const priceVal = (parseFloat(currentMedicineData.price) || 0).toFixed(2);
        document.getElementById('det_price').innerHTML = 'Rs. ' + priceVal + ' <span class="unit-text">per unit</span>';
        
        document.getElementById('det_supplier').textContent = currentMedicineData.supplier || 'N/A';
        document.getElementById('det_location').textContent = currentMedicineData.location || 'N/A';
        document.getElementById('det_desc').textContent = currentMedicineData.description || 'No description provided.';
        
        document.getElementById('det_delete_id').value = currentMedicineData.id;

        document.getElementById('viewDetailsModal').classList.add('active');
    }

    function triggerEditModal() {
        if (!currentMedicineData) return;

        closeModal('viewDetailsModal');

        document.getElementById('edit_id').value = currentMedicineData.id;
        document.getElementById('edit_name').value = currentMedicineData.name || '';
        document.getElementById('edit_category').value = currentMedicineData.category || 'Analgesic';
        document.getElementById('edit_dosage').value = currentMedicineData.dosage || '';
        document.getElementById('edit_batch_number').value = currentMedicineData.batch_number || '';
        document.getElementById('edit_manufacture_date').value = currentMedicineData.manufacture_date || '';
        document.getElementById('edit_expiry_date').value = currentMedicineData.expiry_date || '';
        document.getElementById('edit_stock').value = currentMedicineData.stock || 1;
        document.getElementById('edit_min_stock').value = currentMedicineData.min_stock || 10;
        document.getElementById('edit_price').value = currentMedicineData.price || 0.00;
        document.getElementById('edit_supplier').value = currentMedicineData.supplier || '';
        document.getElementById('edit_location').value = currentMedicineData.location || '';
        document.getElementById('edit_description').value = currentMedicineData.description || '';

        document.getElementById('editMedicineModal').classList.add('active');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function validateForm(form) {
        const nameInput     = form.querySelector('input[name="name"]');
        const dosageInput   = form.querySelector('input[name="dosage"]');
        const batchInput    = form.querySelector('input[name="batch_number"]');
        const mfgInput      = form.querySelector('input[name="manufacture_date"]');
        const expInput      = form.querySelector('input[name="expiry_date"]');
        const stockInput    = form.querySelector('input[name="stock"]');
        const priceInput    = form.querySelector('input[name="price"]');
        const supplierInput = form.querySelector('input[name="supplier"]');
        const locationInput = form.querySelector('input[name="location"]');

        // Reset all previous error messages
        [nameInput, dosageInput, batchInput, mfgInput, expInput, stockInput, priceInput, supplierInput, locationInput].forEach(input => {
            if (input) input.setCustomValidity('');
        });

        // 1. Medicine Name
        if (nameInput) {
            const nameVal = nameInput.value.trim();
            if (!nameVal) {
                nameInput.setCustomValidity("Please enter the medicine name.");
                nameInput.reportValidity();
                return false;
            }
            if (!/^(?=.*[a-zA-Z0-9])(?!.*--)(?!.*\.\.)[a-zA-Z0-9\s\.\-\(\)]{2,100}$/.test(nameVal)) {
                nameInput.setCustomValidity("Medicine name must contain letters/numbers and cannot consist only of symbols.");
                nameInput.reportValidity();
                return false;
            }
        }

        // 2. Dosage Format
        if (dosageInput && dosageInput.value.trim() !== '') {
            const dosagePattern = /^\d+(\.\d+)?\s*(mg|ml)$/i;
            if (!dosagePattern.test(dosageInput.value.trim())) {
                dosageInput.setCustomValidity("Please enter a valid dosage format (e.g., 500mg, 10.5ml).");
                dosageInput.reportValidity();
                return false;
            }
        }

        // 3. Batch / Lot Number
        if (batchInput && batchInput.value.trim() !== '') {
            if (!/^(?=.*[a-zA-Z0-9])(?!.*--)(?!.*\.\.)[a-zA-Z0-9\s\.\-\/]{1,50}$/.test(batchInput.value.trim())) {
                batchInput.setCustomValidity("Batch number must contain letters or numbers and cannot be just dots or hyphens.");
                batchInput.reportValidity();
                return false;
            }
        }

        // 4. Manufacture Date (Mandatory & Cannot be in the future)
        if (mfgInput) {
            if (!mfgInput.value) {
                mfgInput.setCustomValidity("Manufacture date is required.");
                mfgInput.reportValidity();
                return false;
            }

            const mfgDate = new Date(mfgInput.value + 'T00:00:00');
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (mfgDate > today) {
                mfgInput.setCustomValidity("Manufacture date cannot be in the future.");
                mfgInput.reportValidity();
                return false;
            }
        }

        // 5. Expiry Date (Mandatory & Must be in the future)
        if (expInput) {
            if (!expInput.value) {
                expInput.setCustomValidity("Please select an expiry date.");
                expInput.reportValidity();
                return false;
            }

            const expDate = new Date(expInput.value + 'T00:00:00');
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (expDate <= today) {
                expInput.setCustomValidity("Expiry date must be in the future (cannot be today or in the past).");
                expInput.reportValidity();
                return false;
            }
        }

        // 6. Date Comparison (Manufacture Date < Expiry Date)
        if (mfgInput && expInput && mfgInput.value && expInput.value) {
            const mfgDate = new Date(mfgInput.value + 'T00:00:00');
            const expDate = new Date(expInput.value + 'T00:00:00');

            if (mfgDate >= expDate) {
                mfgInput.setCustomValidity("Manufacture Date must be earlier than Expiry Date.");
                mfgInput.reportValidity();
                return false;
            }
        }

        // 7. Stock Check
        if (stockInput && (stockInput.value === '' || parseInt(stockInput.value, 10) <= 0)) {
            stockInput.setCustomValidity("Stock units must be greater than 0.");
            stockInput.reportValidity();
            return false;
        }

        // 8. Price Check (Mandatory & Must be > 0)
        if (priceInput) {
            const priceVal = parseFloat(priceInput.value);
            if (priceInput.value.trim() === '' || isNaN(priceVal) || priceVal <= 0) {
                priceInput.setCustomValidity("Price is required and must be greater than 0.");
                priceInput.reportValidity();
                return false;
            }
        }

        // 9. Supplier Validation
        if (supplierInput && supplierInput.value.trim() !== '') {
            if (!/^(?=.*[a-zA-Z0-9])(?!.*--)(?!.*\.\.)[a-zA-Z0-9\s\.\-\&]{2,100}$/.test(supplierInput.value.trim())) {
                supplierInput.setCustomValidity("Supplier name must contain letters/numbers and cannot consist only of dots or symbols.");
                supplierInput.reportValidity();
                return false;
            }
        }

        // 10. Storage Location
        if (locationInput && locationInput.value.trim() !== '') {
            if (!/^(?=.*[a-zA-Z0-9])(?!.*--)(?!.*\.\.)[a-zA-Z0-9\s\.\-\/]{1,50}$/.test(locationInput.value.trim())) {
                locationInput.setCustomValidity("Storage location must contain letters/numbers and cannot consist only of dots or symbols.");
                locationInput.reportValidity();
                return false;
            }
        }

        return true;
    }

    // Close modal when clicking on backdrop
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('active');
        }
    }
    </script>

</body>
</html>