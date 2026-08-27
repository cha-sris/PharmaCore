<?php
// modules/medicines.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/config.php";
require_once "../includes/validation.php";

// Dynamic category icon mapper
function getCategoryIcon($category) {
    $cat = strtolower(trim($category));
    
    if (strpos($cat, 'antibiotic') !== false) {
        return 'pill-box.svg';
    } elseif (strpos($cat, 'antiseptic') !== false) {
        return 'ointment.svg';
    } elseif (strpos($cat, 'supplement') !== false || strpos($cat, 'vitamin') !== false) {
        return 'pill-box.svg';
    } elseif (strpos($cat, 'analgesic') !== false || strpos($cat, 'pain') !== false) {
        return 'medicine.svg';
    }
    
    return 'medicine.svg';
}

// Handle Form Submissions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_medicine' || $_POST['action'] === 'edit_medicine') {
        $is_edit = ($_POST['action'] === 'edit_medicine');

        // Sanitize inputs via validation.php helpers
        $id               = sanitize_int($_POST['id'] ?? 0);
        $name             = sanitize_text($_POST['name'] ?? '');
        $category         = sanitize_text($_POST['category'] ?? '');
        $manufacture_date = sanitize_text($_POST['manufacture_date'] ?? '');
        $expiry_date      = sanitize_text($_POST['expiry_date'] ?? '');
        $stock            = sanitize_int($_POST['stock'] ?? 0);
        $supplier         = sanitize_text($_POST['supplier'] ?? '');
        $price            = sanitize_float($_POST['price'] ?? 0.00);
        $batch_number     = sanitize_text($_POST['batch_number'] ?? '');
        $dosage           = sanitize_text($_POST['dosage'] ?? '');
        $location         = sanitize_text($_POST['location'] ?? '');
        $min_stock        = sanitize_int($_POST['min_stock'] ?? 5);
        $description      = sanitize_text($_POST['description'] ?? '');

        // Validate backend input data
        $errors = validate_medicine_form($_POST, $is_edit);

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(" ", $errors);
        } else {
            try {
                if ($is_edit) {
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
                } else {
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
                }
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Database Error: " . $e->getMessage();
            }
        }
    }
    elseif ($_POST['action'] === 'delete_medicine') {
        $id = sanitize_int($_POST['id'] ?? 0);
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

// Session Flash Messages
$db_error = $_SESSION['flash_error'] ?? '';
$success_msg = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Fetch Inventory Items
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
    <link rel="stylesheet" href="../assets/css/search.css">
    <link rel="stylesheet" href="../assets/css/medicines.css">
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">

            <div class="page-header">
                <h2>Medicines Inventory</h2>
                <div class="search-wrapper">
                    <div class="search-form" role="search">
                        <div class="search-input-group">
                            <input type="text" id="medicineSearch" class="search-input" placeholder="Search medicines by name, category, batch..." oninput="filterAndSortMedicines()">
                            <button type="button" class="search-btn" onclick="filterAndSortMedicines()">Search</button>
                        </div>
                    </div>
                    <select id="medicineSort" class="standalone-sort" onchange="filterAndSortMedicines()">
                        <option value="default">Sort by: Default</option>
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="stock_asc">Stock (Low to High)</option>
                        <option value="stock_desc">Stock (High to Low)</option>
                        <option value="expiry_asc">Expiry (Soonest First)</option>
                        <option value="price_asc">Price (Low to High)</option>
                        <option value="price_desc">Price (High to Low)</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($db_error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($db_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>

            <div class="medicine-grid" id="medicineGrid">

                <!-- Add Medicine Tile -->
                <div class="med-card add-card" id="addMedicineTile" onclick="openAddModal()">
                    <div class="add-icon">
                        <img src="../assets/images/add_icon.svg" alt="Add" class="icon-img">
                    </div>
                    <span class="add-text">Add Medicine</span>
                </div>

                <!-- Medicine Inventory Cards -->
                <?php foreach ($medicines as $med): ?>
                    <?php 
                        $stock = (int)$med['stock'];
                        $json_data = htmlspecialchars(json_encode($med), ENT_QUOTES, 'UTF-8');
                        $categoryIcon = getCategoryIcon($med['category']);
                        $search_text = strtolower(htmlspecialchars($med['name'] . ' ' . $med['category'] . ' ' . $med['batch_number'] . ' ' . $med['supplier'], ENT_QUOTES));
                    ?>
                    <div class="med-card clickable searchable-card" 
                         onclick="openDetailsModal(this)" 
                         data-medicine='<?php echo $json_data; ?>'
                         data-id="<?php echo (int)$med['id']; ?>"
                         data-name="<?php echo htmlspecialchars(strtolower($med['name']), ENT_QUOTES); ?>"
                         data-stock="<?php echo $stock; ?>"
                         data-exp="<?php echo htmlspecialchars($med['expiry_date'], ENT_QUOTES); ?>"
                         data-price="<?php echo (float)$med['price']; ?>"
                         data-search-text="<?php echo $search_text; ?>">
                        <div class="med-collapsed">
                            <div class="med-icon">
                                <img src="../assets/images/<?php echo $categoryIcon; ?>" alt="Medicine Category" class="icon-img">
                            </div>
                            <h3 class="med-title"><?php echo htmlspecialchars($med['name']); ?></h3>
                            <span class="badge-compact"><?php echo $stock; ?> Units</span>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <div id="noResults" class="no-results-msg" style="display: none;">
                <p>No matching medicines found.</p>
            </div>

        </div>
    </main>

    <!-- Custom Category Autocomplete Suggestions -->
    <datalist id="category_list">
        <option value="Analgesic">
        <option value="Antibiotic">
        <option value="Antiseptic">
        <option value="Supplement / Vitamin">
        <option value="Other">
    </datalist>

    <!-- MODAL 1: ADD MEDICINE -->
    <div class="modal-overlay" id="addMedicineModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Medicine</h3>
                <button type="button" class="close-btn" onclick="closeModal('addMedicineModal')">
                    <img src="../assets/images/close_icon.svg" alt="Close" class="close-icon">
                </button>
            </div>

            <form action="medicines.php" method="POST" onsubmit="return validateMedicineForm(this)" novalidate>
                <input type="hidden" name="action" value="add_medicine">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Medicine Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. Paracetamol">
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" list="category_list" placeholder="Select or type custom category...">
                    </div>

                    <div class="form-group">
                        <label for="dosage">Dosage / Strength</label>
                        <input type="text" id="dosage" name="dosage" placeholder="e.g. 500mg, 10.5ml">
                    </div>

                    <div class="form-group">
                        <label for="batch_number">Batch / Lot Number</label>
                        <input type="text" id="batch_number" name="batch_number" placeholder="e.g. BATCH-992">
                    </div>

                    <div class="form-group">
                        <label for="manufacture_date">Manufacture Date *</label>
                        <input type="date" id="manufacture_date" name="manufacture_date" required max="<?= date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="expiry_date">Expiry Date *</label>
                        <input type="date" id="expiry_date" name="expiry_date" required>
                    </div>

                    <div class="form-group">
                        <label for="stock">No. of Items (Stock) *</label>
                        <input type="number" id="stock" name="stock" value="1" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="min_stock">Low Stock Alert Threshold</label>
                        <input type="number" id="min_stock" name="min_stock" value="10" min="1">
                    </div>

                    <div class="form-group">
                        <label for="price">Price *</label>
                        <input type="number" step="0.01" id="price" name="price" required placeholder="0.00" min="0.01">
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

    <!-- MODAL 2: VIEW DETAILS -->
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

    <!-- MODAL 3: EDIT MEDICINE -->
    <div class="modal-overlay" id="editMedicineModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Medicine</h3>
                <button type="button" class="close-btn" onclick="closeModal('editMedicineModal')">
                    <img src="../assets/images/close_icon.svg" alt="Close" class="close-icon">
                </button>
            </div>

            <form action="medicines.php" method="POST" onsubmit="return validateMedicineForm(this)" novalidate>
                <input type="hidden" name="action" value="edit_medicine">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_name">Medicine Name *</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_category">Category</label>
                        <input type="text" id="edit_category" name="category" list="category_list" placeholder="Select or type custom category...">
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
                        <label for="edit_manufacture_date">Manufacture Date *</label>
                        <input type="date" id="edit_manufacture_date" name="manufacture_date" required max="<?= date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="edit_expiry_date">Expiry Date *</label>
                        <input type="date" id="edit_expiry_date" name="expiry_date" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_stock">No. of Items (Stock) *</label>
                        <input type="number" id="edit_stock" name="stock" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_min_stock">Low Stock Alert Threshold</label>
                        <input type="number" id="edit_min_stock" name="min_stock" min="1">
                    </div>

                    <div class="form-group">
                        <label for="edit_price">Price *</label>
                        <input type="number" step="0.01" id="edit_price" name="price" required min="0.01">
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

    document.addEventListener('DOMContentLoaded', () => {
        const forms = document.querySelectorAll('#addMedicineModal form, #editMedicineModal form');
        forms.forEach(form => setupLiveValidation(form));
    });

    function setupLiveValidation(form) {
        const inputs = form.querySelectorAll('input[required], input[type="number"], input[type="date"]');
        inputs.forEach(input => {
            ['input', 'change', 'blur'].forEach(eventType => {
                input.addEventListener(eventType, () => validateSingleInput(input, form));
            });
        });
    }

    function validateSingleInput(input, form) {
        input.setCustomValidity('');

        const nameInput  = form.querySelector('input[name="name"]');
        const mfgInput   = form.querySelector('input[name="manufacture_date"]');
        const expInput   = form.querySelector('input[name="expiry_date"]');
        const stockInput = form.querySelector('input[name="stock"]');
        const priceInput = form.querySelector('input[name="price"]');

        if (input === nameInput && !input.value.trim()) {
            input.setCustomValidity("Please enter the medicine name.");
        } 
        else if (input === mfgInput && !input.value.trim()) {
            input.setCustomValidity("Manufacture date is required.");
        } 
        else if (input === expInput) {
            if (!input.value.trim()) {
                input.setCustomValidity("Expiry date is required.");
            } else if (mfgInput && mfgInput.value && new Date(input.value) <= new Date(mfgInput.value)) {
                input.setCustomValidity("Expiry date must be after manufacture date.");
            }
        } 
        else if (input === stockInput && (!input.value.trim() || parseInt(input.value, 10) < 1)) {
            input.setCustomValidity("Please enter a valid stock quantity (at least 1).");
        } 
        else if (input === priceInput && (!input.value.trim() || parseFloat(input.value) <= 0)) {
            input.setCustomValidity("Price is required and must be greater than 0.");
        }

        if (!input.checkValidity()) {
            input.reportValidity();
        }
    }

    function validateMedicineForm(form) {
        const inputs = form.querySelectorAll('input[required], input[type="number"], input[type="date"]');
        let isValid = true;

        inputs.forEach(input => {
            validateSingleInput(input, form);
            if (!input.checkValidity() && isValid) {
                input.reportValidity();
                isValid = false;
            }
        });

        return isValid;
    }

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
        document.getElementById('edit_category').value = currentMedicineData.category || '';
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

    function filterAndSortMedicines() {
        const query = document.getElementById('medicineSearch').value.toLowerCase().trim();
        const sortCriteria = document.getElementById('medicineSort').value;
        const grid = document.getElementById('medicineGrid');
        const cards = Array.from(document.querySelectorAll('.searchable-card'));
        const noResults = document.getElementById('noResults');
        let visibleCount = 0;

        cards.forEach(card => {
            const searchText = card.getAttribute('data-search-text');
            if (searchText.includes(query)) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        cards.sort((a, b) => {
            switch (sortCriteria) {
                case 'name_asc':
                    return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
                case 'name_desc':
                    return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
                case 'stock_asc':
                    return parseInt(a.getAttribute('data-stock'), 10) - parseInt(b.getAttribute('data-stock'), 10);
                case 'stock_desc':
                    return parseInt(b.getAttribute('data-stock'), 10) - parseInt(a.getAttribute('data-stock'), 10);
                case 'expiry_asc':
                    return new Date(a.getAttribute('data-exp')) - new Date(b.getAttribute('data-exp'));
                case 'price_asc':
                    return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
                case 'price_desc':
                    return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
                case 'default':
                default:
                    // Resets cards back to initial DB sequence (ID DESC)
                    return parseInt(b.getAttribute('data-id'), 10) - parseInt(a.getAttribute('data-id'), 10);
            }
        });

        cards.forEach(card => grid.appendChild(card));

        if (noResults) {
            noResults.style.display = (visibleCount === 0 && query !== '') ? 'block' : 'none';
        }
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('active');
        }
    }
    </script>
</body>
</html>