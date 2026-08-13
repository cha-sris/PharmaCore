<!-- Replace your suppliers.php with this complete updated file -->
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- ADD OR EDIT SUPPLIER ---
    if ($_POST['action'] === 'add_supplier' || $_POST['action'] === 'edit_supplier') {
        $is_edit = ($_POST['action'] === 'edit_supplier');

        // Sanitize incoming inputs
        $id             = sanitize_int($_POST['id'] ?? 0);
        $name           = sanitize_text($_POST['name'] ?? '');
        $contact_person = sanitize_text($_POST['contact_person'] ?? '');
        $email          = sanitize_email($_POST['email'] ?? '');
        $phone          = sanitize_text($_POST['phone'] ?? '');
        $address        = sanitize_text($_POST['address'] ?? '');

        // Validate using validation module
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

    // --- DELETE SUPPLIER ---
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

// Flash Messages
$db_error = $_SESSION['flash_error'] ?? '';
$success_msg = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Fetch Suppliers List
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
    <link rel="stylesheet" href="../assets/css/suppliers.css">
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

            <?php if (!empty($db_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($db_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>

            <!-- SUPPLIERS TABLE -->
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>SUPPLIER NAME</th>
                            <th>CONTACT PERSON</th>
                            <th>PHONE</th>
                            <th>EMAIL</th>
                            <th>ADDRESS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($suppliers)): ?>
                            <tr>
                                <td colspan="6" class="empty-cell">
                                    No suppliers registered yet. Click "+ Add Supplier" to get started.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suppliers as $index => $sup): ?>
                                <?php $json_data = htmlspecialchars(json_encode($sup), ENT_QUOTES, 'UTF-8'); ?>
                                <tr class="clickable-row" onclick="openDetailsFromRow(this)" data-supplier='<?php echo $json_data; ?>'>
                                    <td><?php echo $index + 1; ?></td>
                                    <td class="font-bold"><?php echo htmlspecialchars($sup['name']); ?></td>
                                    <td><?php echo htmlspecialchars($sup['contact_person'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($sup['phone'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($sup['email'] ?: '—'); ?></td>
                                    <td><?php echo htmlspecialchars($sup['address'] ?: '—'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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

            <form action="suppliers.php" method="POST" class="modal-form" onsubmit="return validateSupplierForm(this)" novalidate>
                <input type="hidden" name="action" value="add_supplier">

                <div class="form-grid">
                    <div class="form-group span-2">
                        <label for="name">Supplier Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. MedicoPharma Ltd." oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="contact_person">Contact Person *</label>
                        <input type="text" id="contact_person" name="contact_person" required placeholder="e.g. John Doe" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="text" id="phone" name="phone" required placeholder="e.g. +977 9800000000" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group span-2">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="e.g. supplier@domain.com" oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group span-2">
                        <label for="address">Address / Location *</label>
                        <textarea id="address" name="address" rows="3" required placeholder="Enter office/warehouse address..." oninput="this.setCustomValidity('')"></textarea>
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

            <form action="suppliers.php" method="POST" class="modal-form" onsubmit="return validateSupplierForm(this)" novalidate>
                <input type="hidden" name="action" value="edit_supplier">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-grid">
                    <div class="form-group span-2">
                        <label for="edit_name">Supplier Name *</label>
                        <input type="text" id="edit_name" name="name" required oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_contact_person">Contact Person *</label>
                        <input type="text" id="edit_contact_person" name="contact_person" required oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group">
                        <label for="edit_phone">Phone Number *</label>
                        <input type="text" id="edit_phone" name="phone" required oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group span-2">
                        <label for="edit_email">Email Address *</label>
                        <input type="email" id="edit_email" name="email" required oninput="this.setCustomValidity('')">
                    </div>

                    <div class="form-group span-2">
                        <label for="edit_address">Address / Location *</label>
                        <textarea id="edit_address" name="address" rows="3" required oninput="this.setCustomValidity('')"></textarea>
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

    function openAddModal() {
        document.getElementById('addSupplierModal').classList.add('active');
    }

    function openDetailsFromRow(row) {
        currentSupplierData = JSON.parse(row.getAttribute('data-supplier'));

        document.getElementById('det_name').textContent = currentSupplierData.name || 'N/A';
        document.getElementById('det_contact').textContent = currentSupplierData.contact_person || 'N/A';
        document.getElementById('det_phone').textContent = currentSupplierData.phone || 'N/A';
        document.getElementById('det_email').textContent = currentSupplierData.email || 'N/A';
        document.getElementById('det_address').textContent = currentSupplierData.address || 'No address provided.';
        
        document.getElementById('det_delete_id').value = currentSupplierData.id;

        document.getElementById('viewDetailsModal').classList.add('active');
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

        document.getElementById('editSupplierModal').classList.add('active');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function validateSupplierForm(form) {
        const nameInput    = form.querySelector('input[name="name"]');
        const contactInput = form.querySelector('input[name="contact_person"]');
        const phoneInput   = form.querySelector('input[name="phone"]');
        const emailInput   = form.querySelector('input[name="email"]');
        const addressInput = form.querySelector('textarea[name="address"]');

        [nameInput, contactInput, phoneInput, emailInput, addressInput].forEach(input => {
            if (input) input.setCustomValidity('');
        });

        // 1. Supplier Name
        if (!nameInput || !nameInput.value.trim()) {
            nameInput.setCustomValidity("Please enter the supplier name.");
            nameInput.reportValidity();
            return false;
        }
        if (!/^(?=.*[a-zA-Z0-9])(?!.*--)(?!.*\.\.)[a-zA-Z0-9\s\.\-\&]{2,100}$/.test(nameInput.value.trim())) {
            nameInput.setCustomValidity("Supplier name must contain letters/numbers and cannot consist only of symbols.");
            nameInput.reportValidity();
            return false;
        }

        // 2. Contact Person
        if (!contactInput || !contactInput.value.trim()) {
            contactInput.setCustomValidity("Please enter the contact person's name.");
            contactInput.reportValidity();
            return false;
        }
        if (!/^(?=.*[a-zA-Z])(?!.*--)(?!.*\.\.)[a-zA-Z\s\.\-]{2,100}$/.test(contactInput.value.trim())) {
            contactInput.setCustomValidity("Contact person name must contain valid letters.");
            contactInput.reportValidity();
            return false;
        }

        // 3. Phone Number
        if (!phoneInput || !phoneInput.value.trim()) {
            phoneInput.setCustomValidity("Please enter the phone number.");
            phoneInput.reportValidity();
            return false;
        }
        const phonePattern = /^[0-9\+\-\s\(\)]{7,20}$/;
        if (!phonePattern.test(phoneInput.value.trim())) {
            phoneInput.setCustomValidity("Please enter a valid phone number format.");
            phoneInput.reportValidity();
            return false;
        }

        // 4. Email Address
        if (!emailInput || !emailInput.value.trim()) {
            emailInput.setCustomValidity("Please enter the email address.");
            emailInput.reportValidity();
            return false;
        }
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailInput.value.trim())) {
            emailInput.setCustomValidity("Please enter a valid email address.");
            emailInput.reportValidity();
            return false;
        }

        // 5. Address
        if (!addressInput || !addressInput.value.trim()) {
            addressInput.setCustomValidity("Please enter the address/location.");
            addressInput.reportValidity();
            return false;
        }

        return true;
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('active');
        }
    }
    </script>

</body>
</html>