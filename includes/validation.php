<?php
/**
 * Centralized Sanitization & Validation Helpers
 * File: includes/validation.php
 */

// =========================================================================
// 1. SANITIZATION FUNCTIONS
// =========================================================================

/**
 * Sanitize plain string input (trims, removes slashes & HTML tags)
 * Handles null & empty string checks safely without PHP 'empty("0")' bugs.
 */
if (!function_exists('sanitize_text')) {
    function sanitize_text(?string $data): string {
        if ($data === null || $data === '') return '';
        $data = trim($data);
        $data = stripslashes($data);
        return strip_tags($data);
    }
}

/**
 * Sanitize integer inputs safely
 */
if (!function_exists('sanitize_int')) {
    function sanitize_int($val, int $default = 0): int {
        return filter_var($val, FILTER_VALIDATE_INT) !== false ? (int)$val : $default;
    }
}

/**
 * Sanitize float/decimal inputs safely
 */
if (!function_exists('sanitize_float')) {
    function sanitize_float($val, float $default = 0.00): float {
        return filter_var($val, FILTER_VALIDATE_FLOAT) !== false ? (float)$val : $default;
    }
}

/**
 * Sanitize email addresses
 */
if (!function_exists('sanitize_email')) {
    function sanitize_email(?string $email): string {
        return filter_var(trim($email ?? ''), FILTER_SANITIZE_EMAIL);
    }
}


// =========================================================================
// 2. INDIVIDUAL FIELD VALIDATION RULES
// =========================================================================

/**
 * Validates Medicine Name (2-100 chars, alphanumeric, prevents symbol-only inputs)
 */
function validate_medicine_name(string $name): ?string {
    if (empty($name)) {
        return "Please enter the medicine name.";
    }
    if (!preg_match('/^(?=.*[a-zA-Z0-9])(?!.*--)(?!.*\.\.)[a-zA-Z0-9\s\.\-\(\)]{2,100}$/', $name)) {
        return "Medicine name must contain letters/numbers and cannot consist only of symbols.";
    }
    return null;
}

/**
 * Validates Dosage format (e.g., 500mg, 10.5ml)
 */
function validate_dosage(string $dosage): ?string {
    if ($dosage !== '' && !preg_match('/^\d+(\.\d+)?\s*(mg|ml)$/i', $dosage)) {
        return "Dosage must be a valid number followed by 'mg' or 'ml' (e.g., 500mg, 10.5ml).";
    }
    return null;
}

/**
 * Validates Batch / Lot Number (1-50 chars)
 */
function validate_batch_number(string $batch): ?string {
    if ($batch !== '' && !preg_match('/^(?=.*[a-zA-Z0-9])(?!.*--)(?!.*\.\.)[a-zA-Z0-9\s\.\-\/]{1,50}$/', $batch)) {
        return "Batch number must contain letters or numbers and cannot be just dots or hyphens.";
    }
    return null;
}

/**
 * Validates Manufacture Date & Expiry Date logic
 */
function validate_medicine_dates(?string $mfg_date, ?string $exp_date): array {
    $errors = [];
    $today = date('Y-m-d');

    if (empty($mfg_date)) {
        $errors[] = "Manufacture date is required.";
    } elseif ($mfg_date > $today) {
        $errors[] = "Manufacture date cannot be in the future.";
    }

    if (empty($exp_date)) {
        $errors[] = "Expiry date is required.";
    } elseif ($exp_date <= $today) {
        $errors[] = "Expiry date must be in the future (cannot be today or in the past).";
    }

    if (!empty($mfg_date) && !empty($exp_date)) {
        if (strtotime($mfg_date) >= strtotime($exp_date)) {
            $errors[] = "Manufacture date must be earlier than the expiry date.";
        }
    }

    return $errors;
}

/**
 * Validates Stock Quantity (must be > 0)
 */
function validate_stock(int $stock): ?string {
    if ($stock <= 0) {
        return "Stock units must be greater than 0.";
    }
    return null;
}

/**
 * Validates Price (must be > 0)
 */
function validate_price(float $price): ?string {
    if ($price <= 0) {
        return "Price is required and must be greater than 0.";
    }
    return null;
}

/**
 * Validates Supplier Name (2-100 chars)
 */
function validate_supplier(string $supplier): ?string {
    if ($supplier !== '' && !preg_match('/^(?=.*[a-zA-Z0-9])(?!.*--)(?!.*\.\.)[a-zA-Z0-9\s\.\-\&]{2,100}$/', $supplier)) {
        return "Supplier name must contain letters/numbers and cannot consist only of dots or symbols.";
    }
    return null;
}

/**
 * Validates Storage Location (1-50 chars)
 */
function validate_location(string $location): ?string {
    if ($location !== '' && !preg_match('/^(?=.*[a-zA-Z0-9])(?!.*--)(?!.*\.\.)[a-zA-Z0-9\s\.\-\/]{1,50}$/', $location)) {
        return "Storage location must contain letters/numbers and cannot consist only of dots or symbols.";
    }
    return null;
}


// =========================================================================
// 3. MASTER SUITE VALIDATION FOR MEDICINES FORM
// =========================================================================

/**
 * Validates the entire medicine form payload
 */
function validate_medicine_form(array $data, bool $is_edit = false): array {
    $errors = [];

    // ID check when editing
    if ($is_edit) {
        $id = sanitize_int($data['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = "Valid Medicine ID is required.";
        }
    }

    // 1. Medicine Name
    if ($err = validate_medicine_name(sanitize_text($data['name'] ?? ''))) {
        $errors[] = $err;
    }

    // 2. Dosage Format
    if ($err = validate_dosage(sanitize_text($data['dosage'] ?? ''))) {
        $errors[] = $err;
    }

    // 3. Batch Number
    if ($err = validate_batch_number(sanitize_text($data['batch_number'] ?? ''))) {
        $errors[] = $err;
    }

    // 4. Dates
    $mfg = sanitize_text($data['manufacture_date'] ?? '');
    $exp = sanitize_text($data['expiry_date'] ?? '');
    $date_errors = validate_medicine_dates($mfg, $exp);
    $errors = array_merge($errors, $date_errors);

    // 5. Stock
    if ($err = validate_stock(sanitize_int($data['stock'] ?? 0))) {
        $errors[] = $err;
    }

    // 6. Price
    $price_val = isset($data['price']) && $data['price'] !== '' ? sanitize_float($data['price']) : 0.00;
    if ($err = validate_price($price_val)) {
        $errors[] = $err;
    }

    // 7. Supplier
    if ($err = validate_supplier(sanitize_text($data['supplier'] ?? ''))) {
        $errors[] = $err;
    }

    // 8. Storage Location
    if ($err = validate_location(sanitize_text($data['location'] ?? ''))) {
        $errors[] = $err;
    }

    return $errors;
}


// =========================================================================
// 4. SUPPLIER VALIDATION RULES
// =========================================================================

/**
 * Validates Phone Number format (digits, spaces, +, -, parentheses)
 */
function validate_phone(string $phone): ?string {
    if (empty($phone)) {
        return "Phone number is required.";
    }
    if (!preg_match('/^[0-9\+\-\s\(\)]{7,20}$/', $phone)) {
        return "Please enter a valid phone number (7 to 20 digits/symbols).";
    }
    return null;
}

/**
 * Validates Email Address format
 */
function validate_email_address(string $email): ?string {
    if (empty($email)) {
        return "Email address is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid email address.";
    }
    return null;
}

/**
 * Master validation routine for Supplier form submissions
 */
function validate_supplier_form(array $data, bool $is_edit = false): array {
    $errors = [];

    if ($is_edit) {
        $id = sanitize_int($data['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = "Valid Supplier ID is required.";
        }
    }

    // 1. Supplier Name (Required, 2-100 chars)
    $name = sanitize_text($data['name'] ?? '');
    if (empty($name)) {
        $errors[] = "Supplier name is required.";
    } elseif ($err = validate_supplier($name)) {
        $errors[] = $err;
    }

    // 2. Contact Person (Required)
    $contact = sanitize_text($data['contact_person'] ?? '');
    if (empty($contact)) {
        $errors[] = "Contact person is required.";
    } elseif (!preg_match('/^(?=.*[a-zA-Z])(?!.*--)(?!.*\.\.)[a-zA-Z\s\.\-]{2,100}$/', $contact)) {
        $errors[] = "Contact person name must contain letters and valid spacing/punctuation.";
    }

    // 3. Email Address (Required)
    $email = sanitize_email($data['email'] ?? '');
    if ($err = validate_email_address($email)) {
        $errors[] = $err;
    }

    // 4. Phone Number (Required)
    $phone = sanitize_text($data['phone'] ?? '');
    if ($err = validate_phone($phone)) {
        $errors[] = $err;
    }

    // 5. Address (Required)
    $address = sanitize_text($data['address'] ?? '');
    if (empty($address)) {
        $errors[] = "Address is required.";
    }

    return $errors;
}