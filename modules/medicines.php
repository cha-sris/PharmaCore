<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/config.php";

// ----------------------------------------------------------------
// 1. HANDLE POST SUBMISSIONS (ADD, EDIT, DELETE - PRG Pattern)
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- ADD MEDICINE ---
    if ($_POST['action'] === 'add_medicine') {
        $name             = trim($_POST['name'] ?? '');
        $category         = trim($_POST['category'] ?? '');
        $manufacture_date = !empty($_POST['manufacture_date']) ? $_POST['manufacture_date'] : null;
        $expiry_date      = $_POST['expiry_date'] ?? '';
        $stock            = (int)($_POST['stock'] ?? 0);
        $supplier         = trim($_POST['supplier'] ?? '');
        $price            = (float)($_POST['price'] ?? 0.00);
        $batch_number     = trim($_POST['batch_number'] ?? '');
        $dosage           = trim($_POST['dosage'] ?? '');
        $location         = trim($_POST['location'] ?? '');
        $min_stock        = (int)($_POST['min_stock'] ?? 5);
        $description      = trim($_POST['description'] ?? '');

        if (empty($name) || empty($expiry_date)) {
            $_SESSION['flash_error'] = "Medicine name and expiry date are required.";
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
        $name             = trim($_POST['name'] ?? '');
        $category         = trim($_POST['category'] ?? '');
        $manufacture_date = !empty($_POST['manufacture_date']) ? $_POST['manufacture_date'] : null;
        $expiry_date      = $_POST['expiry_date'] ?? '';
        $stock            = (int)($_POST['stock'] ?? 0);
        $supplier         = trim($_POST['supplier'] ?? '');
        $price            = (float)($_POST['price'] ?? 0.00);
        $batch_number     = trim($_POST['batch_number'] ?? '');
        $dosage           = trim($_POST['dosage'] ?? '');
        $location         = trim($_POST['location'] ?? '');
        $min_stock        = (int)($_POST['min_stock'] ?? 5);
        $description      = trim($_POST['description'] ?? '');

        if ($id <= 0 || empty($name) || empty($expiry_date)) {
            $_SESSION['flash_error'] = "Valid Medicine ID, name, and expiry date are required.";
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

    // REDIRECT (Prevents form re-submission on refresh)
    header("Location: medicines.php");
    exit;
}

// Extract & Clear Flash Messages
$db_error = $_SESSION['flash_error'] ?? '';
$success_msg = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// ----------------------------------------------------------------
// 2. FETCH MEDICINES INVENTORY
// ----------------------------------------------------------------
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
                <h2>
                    <!-- <img src="../assets/images/medicine.svg" alt="Logo" class="header-icon">  -->
                    Medicines Inventory
                </h2>
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

            <form action="medicines.php" method="POST">
                <input type="hidden" name="action" value="add_medicine">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Medicine Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. Paracetamol">
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
                        <label for="dosage">Dosage / Strength</label>
                        <input type="text" id="dosage" name="dosage" placeholder="e.g. 500mg, 10ml">
                    </div>

                    <div class="form-group">
                        <label for="batch_number">Batch / Lot Number</label>
                        <input type="text" id="batch_number" name="batch_number" placeholder="e.g. BATCH-992">
                    </div>

                    <div class="form-group">
                        <label for="manufacture_date">Manufacture Date</label>
                        <input type="date" id="manufacture_date" name="manufacture_date">
                    </div>

                    <div class="form-group">
                        <label for="expiry_date">Expiry Date *</label>
                        <input type="date" id="expiry_date" name="expiry_date" required>
                    </div>

                    <div class="form-group">
                        <label for="stock">No. of Items (Stock) *</label>
                        <input type="number" id="stock" name="stock" value="0" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="min_stock">Low Stock Alert Threshold</label>
                        <input type="number" id="min_stock" name="min_stock" value="10" min="1">
                    </div>

                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="number" step="0.01" id="price" name="price" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label for="supplier">Supplier</label>
                        <input type="text" id="supplier" name="supplier" placeholder="e.g. MedicoPharma Ltd.">
                    </div>

                    <div class="form-group">
                        <label for="location">Rack / Storage Location</label>
                        <input type="text" id="location" name="location" placeholder="e.g. Rack A-2">
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
            </div>

            <div class="details-body">
                <div class="detail-row"><span>Category:</span> <strong id="det_category">N/A</strong></div>
                <div class="detail-row"><span>Dosage / Strength:</span> <strong id="det_dosage">N/A</strong></div>
                <div class="detail-row"><span>Batch Number:</span> <strong id="det_batch">N/A</strong></div>
                <div class="detail-row"><span>Manufacture Date:</span> <strong id="det_mfg">N/A</strong></div>
                <div class="detail-row"><span>Expiry Date:</span> <strong id="det_exp">N/A</strong></div>
                <div class="detail-row"><span>Current Stock:</span> <strong id="det_stock">0</strong></div>
                <div class="detail-row"><span>Price:</span> <strong id="det_price">0.00</strong></div>
                <div class="detail-row"><span>Supplier:</span> <strong id="det_supplier">N/A</strong></div>
                <div class="detail-row"><span>Location:</span> <strong id="det_location">N/A</strong></div>
                <div class="detail-row full"><span>Description:</span> <p id="det_desc">N/A</p></div>
            </div>

            <div class="modal-footer">
                <!-- Delete Form -->
                <form method="POST" action="medicines.php" onsubmit="return confirm('Are you sure you want to delete this medicine?');" style="margin-right: auto;">
                    <input type="hidden" name="action" value="delete_medicine">
                    <input type="hidden" name="id" id="det_delete_id">
                    <button type="submit" class="btn-danger">Delete</button>
                </form>

                <button type="button" class="btn-edit" onclick="triggerEditModal()">Edit</button>

                <!-- Bottom-right close button commented out below -->
                <!-- <button type="button" class="btn-cancel" onclick="closeModal('viewDetailsModal')">Close</button> -->
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

            <form action="medicines.php" method="POST">
                <input type="hidden" name="action" value="edit_medicine">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_name">Medicine Name *</label>
                        <input type="text" id="edit_name" name="name" required>
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
                        <label for="edit_dosage">Dosage / Strength</label>
                        <input type="text" id="edit_dosage" name="dosage">
                    </div>

                    <div class="form-group">
                        <label for="edit_batch_number">Batch / Lot Number</label>
                        <input type="text" id="edit_batch_number" name="batch_number">
                    </div>

                    <div class="form-group">
                        <label for="edit_manufacture_date">Manufacture Date</label>
                        <input type="date" id="edit_manufacture_date" name="manufacture_date">
                    </div>

                    <div class="form-group">
                        <label for="edit_expiry_date">Expiry Date *</label>
                        <input type="date" id="edit_expiry_date" name="expiry_date" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_stock">No. of Items (Stock) *</label>
                        <input type="number" id="edit_stock" name="stock" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_min_stock">Low Stock Alert Threshold</label>
                        <input type="number" id="edit_min_stock" name="min_stock" min="1">
                    </div>

                    <div class="form-group">
                        <label for="edit_price">Price</label>
                        <input type="number" step="0.01" id="edit_price" name="price">
                    </div>

                    <div class="form-group">
                        <label for="edit_supplier">Supplier</label>
                        <input type="text" id="edit_supplier" name="supplier">
                    </div>

                    <div class="form-group">
                        <label for="edit_location">Rack / Storage Location</label>
                        <input type="text" id="edit_location" name="location">
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
            document.getElementById('det_stock').textContent = currentMedicineData.stock + ' Units';
            document.getElementById('det_price').textContent = 'Rs ' + (parseFloat(currentMedicineData.price) || 0).toFixed(2);
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
            document.getElementById('edit_stock').value = currentMedicineData.stock || 0;
            document.getElementById('edit_min_stock').value = currentMedicineData.min_stock || 10;
            document.getElementById('edit_price').value = currentMedicineData.price || 0.00;
            document.getElementById('edit_supplier').value = currentMedicineData.supplier || '';
            document.getElementById('edit_location').value = currentMedicineData.location || '';
            document.getElementById('edit_description').value = currentMedicineData.description || '';

            document.getElementById('editMedicineModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal when clicking outside on overlay backdrop
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>