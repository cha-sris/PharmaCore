<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../auth/login.php");
    exit;
}

require_once "../config/config.php";
require_once "../includes/validation.php";

// =========================================================================
// AJAX: GET MEDICINES FOR A SUPPLIER (by supplier name)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_supplier_medicines' && isset($_GET['supplier_name'])) {
    $supplier_name = sanitize_text($_GET['supplier_name']);
    try {
        $stmt = $pdo->prepare("SELECT id, name, dosage, stock FROM medicines WHERE supplier = :supplier ORDER BY name");
        $stmt->execute([':supplier' => $supplier_name]);
        $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($medicines);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// HANDLE FORM SUBMISSIONS (ADD, EDIT, DELETE)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_supplier' || $_POST['action'] === 'edit_supplier') {
        $is_edit = ($_POST['action'] === 'edit_supplier');

        $id             = sanitize_int($_POST['id'] ?? 0);
        $name           = sanitize_text($_POST['name'] ?? '');
        $contact_person = sanitize_text($_POST['contact_person'] ?? '');
        $email          = sanitize_email($_POST['email'] ?? '');
        $phone          = sanitize_text($_POST['phone'] ?? '');
        $address        = sanitize_text($_POST['address'] ?? '');

        $errors = validate_supplier_form($_POST, $is_edit);

        if (!empty($errors)) {
            $_SESSION['flash_error'] = implode(" ", $errors);
        } else {
            try {
                if ($is_edit) {
                    $sql = "UPDATE suppliers SET 
                                name = :name, 
                                contact_person = :contact, 
                                email = :email, 
                                phone = :phone, 
                                address = :address 
                            WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':id'      => $id,
                        ':name'    => $name,
                        ':contact' => $contact_person,
                        ':email'   => $email,
                        ':phone'   => $phone,
                        ':address' => $address
                    ]);
                    $_SESSION['flash_success'] = "Supplier updated successfully!";
                } else {
                    $sql = "INSERT INTO suppliers (name, contact_person, email, phone, address) 
                            VALUES (:name, :contact, :email, :phone, :address)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':name'    => $name,
                        ':contact' => $contact_person,
                        ':email'   => $email,
                        ':phone'   => $phone,
                        ':address' => $address
                    ]);
                    $_SESSION['flash_success'] = "Supplier added successfully!";
                }
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Database Error: " . $e->getMessage();
            }
        }
    }
    elseif ($_POST['action'] === 'delete_supplier') {
        $id = sanitize_int($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $_SESSION['flash_success'] = "Supplier deleted successfully!";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Database Error: " . $e->getMessage();
            }
        }
    }

    header("Location: suppliers.php");
    exit;
}

// =========================================================================
// FETCH ALL SUPPLIERS FOR DISPLAY
// =========================================================================
$db_error = $_SESSION['flash_error'] ?? '';
$success_msg = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$suppliers = [];
try {
    $sql = "SELECT * FROM suppliers ORDER BY id DESC";
    $stmt = $pdo->query($sql);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_error = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - PharmaCore</title>
    <link rel="shortcut icon" href="../assets/images/pharmacore_icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/search.css">
    <link rel="stylesheet" href="../assets/css/suppliers.css">
    <!-- All styles now live in suppliers.css -->
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">

            <div class="page-header">
                <h2>Suppliers Management</h2>
                <button type="button" class="btn-primary-action" onclick="openAddModal()">
                    + Add Supplier
                </button>
            </div>

            <!-- Search & Sort Controls -->
            <div class="search-wrapper" style="margin-bottom: 1.5rem;">
                <div class="search-form" role="search">
                    <div class="search-input-group">
                        <input type="text" id="supplierSearch" class="search-input" placeholder="Search suppliers by name, contact, phone..." oninput="filterAndSortSuppliers()">
                        <button type="button" class="search-btn" onclick="filterAndSortSuppliers()">Search</button>
                    </div>
                </div>
                <select id="supplierSort" class="standalone-sort" onchange="filterAndSortSuppliers()">
                    <option value="default">Sort by: Default</option>
                    <option value="name_asc">Name (A-Z)</option>
                    <option value="name_desc">Name (Z-A)</option>
                    <option value="contact_asc">Contact (A-Z)</option>
                    <option value="contact_desc">Contact (Z-A)</option>
                </select>
            </div>

            <?php if (!empty($db_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($db_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>

            <!-- SUPPLIERS TABLE WITH EXPANDABLE ROWS -->
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th> <!-- expand toggle -->
                            <th>#</th>
                            <th>SUPPLIER NAME</th>
                            <th>CONTACT PERSON</th>
                            <th>PHONE</th>
                            <th>EMAIL</th>
                            <th>ADDRESS</th>
                        </tr>
                    </thead>
                    <tbody id="supplierTableBody">
                        <?php if (empty($suppliers)): ?>
                            <tr>
                                <td colspan="7" class="empty-cell">
                                    No suppliers registered yet. Click "+ Add Supplier" to get started.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suppliers as $index => $sup): ?>
                                <?php 
                                    $json_data = htmlspecialchars(json_encode($sup), ENT_QUOTES, 'UTF-8');
                                    $search_text = strtolower(
                                        $sup['name'] . ' ' . 
                                        $sup['contact_person'] . ' ' . 
                                        $sup['phone'] . ' ' . 
                                        $sup['email'] . ' ' . 
                                        $sup['address']
                                    );
                                ?>
                                <!-- Main row -->
                                <tr class="clickable-row searchable-row" 
                                    data-id="<?php echo (int)$sup['id']; ?>"
                                    data-name="<?php echo htmlspecialchars(strtolower($sup['name']), ENT_QUOTES); ?>"
                                    data-contact="<?php echo htmlspecialchars(strtolower($sup['contact_person']), ENT_QUOTES); ?>"
                                    data-search-text="<?php echo $search_text; ?>">
                                    <td>
                                        <button class="expand-btn" onclick="toggleMedicines(this, '<?php echo addslashes($sup['name']); ?>')" title="Show/hide medicines">▼</button>
                                    </td>
                                    <td><?php echo $index + 1; ?></td>
                                    <td class="font-bold" onclick="openDetailsFromRow(this.closest('tr'), <?php echo $json_data; ?>)"><?php echo htmlspecialchars($sup['name']); ?></td>
                                    <td onclick="openDetailsFromRow(this.closest('tr'), <?php echo $json_data; ?>)"><?php echo htmlspecialchars($sup['contact_person'] ?: '—'); ?></td>
                                    <td onclick="openDetailsFromRow(this.closest('tr'), <?php echo $json_data; ?>)"><?php echo htmlspecialchars($sup['phone'] ?: '—'); ?></td>
                                    <td onclick="openDetailsFromRow(this.closest('tr'), <?php echo $json_data; ?>)"><?php echo htmlspecialchars($sup['email'] ?: '—'); ?></td>
                                    <td onclick="openDetailsFromRow(this.closest('tr'), <?php echo $json_data; ?>)"><?php echo htmlspecialchars($sup['address'] ?: '—'); ?></td>
                                </tr>
                                <!-- Detail row (hidden by default) -->
                                <tr class="detail-row" id="detail-<?php echo $sup['id']; ?>">
                                    <td colspan="7">
                                        <div class="detail-content" id="detail-content-<?php echo $sup['id']; ?>">
                                            <!-- Medicines will be loaded here -->
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div id="noSupplierResults" style="display: none; padding: 20px; text-align: center; color: var(--text-muted);">
                    No matching suppliers found.
                </div>
            </div>

        </div>
    </main>

    <!-- ================================================================ -->
    <!-- MODAL 1: ADD SUPPLIER                                            -->
    <!-- ================================================================ -->
    <div class="modal-overlay" id="addSupplierModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Add New Supplier</h3>
                <button type="button" class="close-btn" onclick="closeModal('addSupplierModal')">&times;</button>
            </div>

            <form action="suppliers.php" method="POST" class="modal-form" id="addSupplierForm" novalidate>
                <input type="hidden" name="action" value="add_supplier">

                <div class="form-grid">
                    <div class="form-group span-2">
                        <label for="name">Supplier Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. MedicoPharma Ltd.">
                        <span class="field-error" id="name-error"></span>
                    </div>

                    <div class="form-group">
                        <label for="contact_person">Contact Person *</label>
                        <input type="text" id="contact_person" name="contact_person" required placeholder="e.g. John Doe">
                        <span class="field-error" id="contact_person-error"></span>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="text" id="phone" name="phone" required placeholder="e.g. +977 9800000000">
                        <span class="field-error" id="phone-error"></span>
                    </div>

                    <div class="form-group span-2">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="e.g. supplier@domain.com">
                        <span class="field-error" id="email-error"></span>
                    </div>

                    <div class="form-group span-2">
                        <label for="address">Address / Location *</label>
                        <textarea id="address" name="address" rows="3" required placeholder="Enter office/warehouse address..."></textarea>
                        <span class="field-error" id="address-error"></span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('addSupplierModal')">Cancel</button>
                    <button type="submit" class="btn-primary-action">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- MODAL 2: VIEW SUPPLIER DETAILS                                   -->
    <!-- ================================================================ -->
    <div class="modal-overlay" id="viewDetailsModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3 id="det_name">Supplier Details</h3>
                <button type="button" class="close-btn" onclick="closeModal('viewDetailsModal')">&times;</button>
            </div>

            <div class="details-grid">
                <div class="detail-card">
                    <span class="detail-label">Contact Person:</span>
                    <div class="detail-value" id="det_contact">N/A</div>
                </div>
                <div class="detail-card">
                    <span class="detail-label">Phone:</span>
                    <div class="detail-value" id="det_phone">N/A</div>
                </div>
                <div class="detail-card span-2">
                    <span class="detail-label">Email:</span>
                    <div class="detail-value" id="det_email">N/A</div>
                </div>
                <div class="detail-card span-2">
                    <span class="detail-label">Address:</span>
                    <div class="detail-value" id="det_address">No address provided.</div>
                </div>
            </div>

            <!-- Medicines supplied by this supplier -->
            <div class="med-list" id="supplierMedicines">
                <h4>Medicines supplied by this supplier</h4>
                <div id="medicinesListContainer">
                    <span class="loading">Loading medicines...</span>
                </div>
            </div>

            <div class="modal-footer">
                <form method="POST" action="suppliers.php" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                    <input type="hidden" name="action" value="delete_supplier">
                    <input type="hidden" name="id" id="det_delete_id">
                    <button type="submit" class="btn-danger-action">Delete</button>
                </form>

                <button type="button" class="btn-edit-action" onclick="triggerEditModal()">Edit</button>
                <button type="button" class="btn-action" onclick="closeModal('viewDetailsModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- MODAL 3: EDIT SUPPLIER                                           -->
    <!-- ================================================================ -->
    <div class="modal-overlay" id="editSupplierModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Edit Supplier</h3>
                <button type="button" class="close-btn" onclick="closeModal('editSupplierModal')">&times;</button>
            </div>

            <form action="suppliers.php" method="POST" class="modal-form" id="editSupplierForm" novalidate>
                <input type="hidden" name="action" value="edit_supplier">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-grid">
                    <div class="form-group span-2">
                        <label for="edit_name">Supplier Name *</label>
                        <input type="text" id="edit_name" name="name" required>
                        <span class="field-error" id="edit_name-error"></span>
                    </div>

                    <div class="form-group">
                        <label for="edit_contact_person">Contact Person *</label>
                        <input type="text" id="edit_contact_person" name="contact_person" required>
                        <span class="field-error" id="edit_contact_person-error"></span>
                    </div>

                    <div class="form-group">
                        <label for="edit_phone">Phone Number *</label>
                        <input type="text" id="edit_phone" name="phone" required>
                        <span class="field-error" id="edit_phone-error"></span>
                    </div>

                    <div class="form-group span-2">
                        <label for="edit_email">Email Address *</label>
                        <input type="email" id="edit_email" name="email" required>
                        <span class="field-error" id="edit_email-error"></span>
                    </div>

                    <div class="form-group span-2">
                        <label for="edit_address">Address / Location *</label>
                        <textarea id="edit_address" name="address" rows="3" required></textarea>
                        <span class="field-error" id="edit_address-error"></span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('editSupplierModal')">Cancel</button>
                    <button type="submit" class="btn-primary-action">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let currentSupplierData = null;

    // =========================================================================
    // INLINE VALIDATION (mirrors backend validation.php)
    // =========================================================================

    function validateSupplierField(field) {
        const id = field.id;
        const value = field.value.trim();
        const errorId = id + '-error';
        const errorEl = document.getElementById(errorId);
        if (!errorEl) return true;

        let errorMsg = '';
        const fieldName = id.replace(/^edit_/, '');

        switch (fieldName) {
            case 'name':
                if (value === '') {
                    errorMsg = 'Please enter the supplier name.';
                } else if (!/^(?=.*[a-zA-Z0-9])(?!.*--)(?!.*\.\.)[a-zA-Z0-9\s\.\-\&]{2,100}$/.test(value)) {
                    errorMsg = 'Supplier name must contain letters/numbers and cannot consist only of symbols.';
                }
                break;

            case 'contact_person':
                if (value === '') {
                    errorMsg = 'Please enter the contact person\'s name.';
                } else if (!/^(?=.*[a-zA-Z])(?!.*--)(?!.*\.\.)[a-zA-Z\s\.\-]{2,100}$/.test(value)) {
                    errorMsg = 'Contact person name must contain valid letters and spacing/punctuation.';
                }
                break;

            case 'phone':
                if (value === '') {
                    errorMsg = 'Please enter the phone number.';
                } else if (!/^[0-9\+\-\s\(\)]{7,20}$/.test(value)) {
                    errorMsg = 'Please enter a valid phone number (7 to 20 digits/symbols).';
                }
                break;

            case 'email':
                if (value === '') {
                    errorMsg = 'Please enter the email address.';
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    errorMsg = 'Please enter a valid email address.';
                }
                break;

            case 'address':
                if (value === '') {
                    errorMsg = 'Please enter the address/location.';
                }
                break;

            default:
                break;
        }

        errorEl.textContent = errorMsg;
        if (errorMsg) {
            field.classList.add('error');
        } else {
            field.classList.remove('error');
        }
        return errorMsg === '';
    }

    function validateAllSupplierFields(form) {
        const inputs = form.querySelectorAll('input, textarea');
        let allValid = true;
        inputs.forEach(input => {
            if (!validateSupplierField(input)) {
                allValid = false;
            }
        });
        return allValid;
    }

    function clearSupplierErrors(form) {
        const errorSpans = form.querySelectorAll('.field-error');
        errorSpans.forEach(el => el.textContent = '');
        const errorInputs = form.querySelectorAll('.error');
        errorInputs.forEach(el => el.classList.remove('error'));
    }

    function setupSupplierValidation(form) {
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            ['input', 'change', 'blur'].forEach(eventType => {
                input.addEventListener(eventType, function() {
                    validateSupplierField(this);
                });
            });
        });

        form.addEventListener('submit', function(e) {
            if (!validateAllSupplierFields(this)) {
                e.preventDefault();
                const firstError = this.querySelector('.field-error:not(:empty)');
                if (firstError) {
                    const fieldId = firstError.id.replace('-error', '');
                    const field = document.getElementById(fieldId);
                    if (field) field.focus();
                }
            }
        });
    }

    // Initialize validation when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const addForm = document.getElementById('addSupplierForm');
        const editForm = document.getElementById('editSupplierForm');
        if (addForm) setupSupplierValidation(addForm);
        if (editForm) setupSupplierValidation(editForm);
    });

    // =========================================================================
    // EXPANDABLE ROW – TOGGLE MEDICINES
    // =========================================================================
    function toggleMedicines(btn, supplierName) {
        const row = btn.closest('tr');
        const supplierId = row.getAttribute('data-id');
        const detailRow = document.getElementById('detail-' + supplierId);
        const detailContent = document.getElementById('detail-content-' + supplierId);

        if (detailContent.classList.contains('open')) {
            detailContent.classList.remove('open');
            btn.classList.remove('expanded');
            btn.textContent = '▼';
            return;
        }

        btn.classList.add('expanded');
        btn.textContent = '▲';
        detailContent.classList.add('open');

        if (detailContent.getAttribute('data-loaded') === 'true') {
            return;
        }

        detailContent.innerHTML = '<span class="loading">Loading medicines...</span>';

        const encodedName = encodeURIComponent(supplierName);
        fetch(`suppliers.php?action=get_supplier_medicines&supplier_name=${encodedName}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    detailContent.innerHTML = `<span class="empty-med">Error: ${data.error}</span>`;
                    return;
                }
                if (data.length === 0) {
                    detailContent.innerHTML = '<span class="empty-med">No medicines registered for this supplier.</span>';
                } else {
                    let html = '<div class="med-list-inline">';
                    data.forEach(med => {
                        html += `<a href="../modules/medicines.php?view=${med.id}" class="med-item-link">
                            <span class="med-item">${med.name} (${med.dosage || 'N/A'}) – ${med.stock} units</span>
                        </a>`;
                    });
                    html += '</div>';
                    detailContent.innerHTML = html;
                    detailContent.setAttribute('data-loaded', 'true');
                }
            })
            .catch(error => {
                detailContent.innerHTML = `<span class="empty-med">Failed to load medicines.</span>`;
                console.error('Error fetching medicines:', error);
            });
    }

    // =========================================================================
    // SEARCH & SORT
    // =========================================================================
    function filterAndSortSuppliers() {
        const query = document.getElementById('supplierSearch').value.toLowerCase().trim();
        const sortCriteria = document.getElementById('supplierSort').value;
        const tbody = document.getElementById('supplierTableBody');
        const rows = Array.from(tbody.querySelectorAll('tr.clickable-row'));
        const noResults = document.getElementById('noSupplierResults');
        let visibleCount = 0;

        rows.forEach(row => {
            const searchText = row.getAttribute('data-search-text');
            if (searchText.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
                const detailRow = document.getElementById('detail-' + row.getAttribute('data-id'));
                if (detailRow) {
                    const content = detailRow.querySelector('.detail-content');
                    if (content) content.classList.remove('open');
                }
            }
        });

        rows.sort((a, b) => {
            const nameA = a.getAttribute('data-name') || '';
            const nameB = b.getAttribute('data-name') || '';
            const contactA = a.getAttribute('data-contact') || '';
            const contactB = b.getAttribute('data-contact') || '';

            switch (sortCriteria) {
                case 'name_asc':
                    return nameA.localeCompare(nameB);
                case 'name_desc':
                    return nameB.localeCompare(nameA);
                case 'contact_asc':
                    return contactA.localeCompare(contactB);
                case 'contact_desc':
                    return contactB.localeCompare(contactA);
                case 'default':
                default:
                    return parseInt(b.getAttribute('data-id'), 10) - parseInt(a.getAttribute('data-id'), 10);
            }
        });

        rows.forEach(row => {
            tbody.appendChild(row);
            const detailRow = document.getElementById('detail-' + row.getAttribute('data-id'));
            if (detailRow) tbody.appendChild(detailRow);
        });

        if (noResults) {
            noResults.style.display = (visibleCount === 0 && query !== '') ? 'block' : 'none';
        }
    }

    // =========================================================================
    // MODAL FUNCTIONS
    // =========================================================================
    function openAddModal() {
        document.getElementById('addSupplierModal').classList.add('active');
        const form = document.getElementById('addSupplierForm');
        if (form) clearSupplierErrors(form);
    }

    function openDetailsFromRow(row, supplierData) {
        if (!supplierData) {
            supplierData = JSON.parse(row.getAttribute('data-supplier') || '{}');
        }
        currentSupplierData = supplierData;

        document.getElementById('det_name').textContent = currentSupplierData.name || 'N/A';
        document.getElementById('det_contact').textContent = currentSupplierData.contact_person || 'N/A';
        document.getElementById('det_phone').textContent = currentSupplierData.phone || 'N/A';
        document.getElementById('det_email').textContent = currentSupplierData.email || 'N/A';
        document.getElementById('det_address').textContent = currentSupplierData.address || 'No address provided.';
        document.getElementById('det_delete_id').value = currentSupplierData.id;

        document.getElementById('viewDetailsModal').classList.add('active');

        const container = document.getElementById('medicinesListContainer');
        container.innerHTML = '<span class="loading">Loading medicines...</span>';

        const supplierName = encodeURIComponent(currentSupplierData.name);
        fetch(`suppliers.php?action=get_supplier_medicines&supplier_name=${supplierName}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = `<span class="empty-med">Error: ${data.error}</span>`;
                    return;
                }
                if (data.length === 0) {
                    container.innerHTML = '<span class="empty-med">No medicines registered for this supplier.</span>';
                } else {
                    let html = '<ul>';
                    data.forEach(med => {
                        html += `<li>
                            <a href="../modules/medicines.php?view=${med.id}" class="med-item-link">
                                ${med.name} (${med.dosage || 'N/A'}) – Stock: ${med.stock} units
                            </a>
                        </li>`;
                    });
                    html += '</ul>';
                    container.innerHTML = html;
                }
            })
            .catch(error => {
                container.innerHTML = `<span class="empty-med">Failed to load medicines.</span>`;
                console.error('Error fetching medicines:', error);
            });
    }

    function triggerEditModal() {
        if (!currentSupplierData) return;

        closeModal('viewDetailsModal');

        document.getElementById('edit_id').value = currentSupplierData.id;
        document.getElementById('edit_name').value = currentSupplierData.name || '';
        document.getElementById('edit_contact_person').value = currentSupplierData.contact_person || '';
        document.getElementById('edit_phone').value = currentSupplierData.phone || '';
        document.getElementById('edit_email').value = currentSupplierData.email || '';
        document.getElementById('edit_address').value = currentSupplierData.address || '';

        const form = document.getElementById('editSupplierForm');
        if (form) clearSupplierErrors(form);
        document.getElementById('editSupplierModal').classList.add('active');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            const form = modal.querySelector('form');
            if (form) clearSupplierErrors(form);
        }
    }

    // =========================================================================
    // CLOSE MODAL ON OVERLAY CLICK
    // =========================================================================
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('active');
            const form = event.target.querySelector('form');
            if (form) clearSupplierErrors(form);
        }
    }
    </script>

</body>
</html>