<?php
// public/register.php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$success = '';

if (isPost()) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $city = trim($_POST['city'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $tax_clearance = isset($_POST['tax_clearance']) ? 1 : 0;
    $authentication_code = trim($_POST['authentication_code'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'none';
    $ecocash_number = trim($_POST['ecocash_number'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $account_name = trim($_POST['account_name'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');
    $program = trim($_POST['program'] ?? '');

    // Basic validation
    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($phone === '') $errors[] = 'Phone is required.';
    if ($password === '') $errors[] = 'Password is required.';
    if ($password !== $password_confirm) $errors[] = 'Passwords do not match.';

    // Check unique phone
    $stmt = $db->prepare("SELECT id FROM affiliates WHERE phone_number = :phone LIMIT 1");
    $stmt->execute([':phone' => $phone]);
    if ($stmt->fetch()) $errors[] = 'Phone number already registered.';

    // Handle file upload (tax clearance proof)
    $uploadPath = null;
    if (isset($_FILES['tax_clearance_proof']) && $_FILES['tax_clearance_proof']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['tax_clearance_proof']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            $errors[] = 'Tax clearance proof must be PDF or JPG/PNG.';
        } else {
            $ext = pathinfo($_FILES['tax_clearance_proof']['name'], PATHINFO_EXTENSION);
            $fname = uniqid('clear_') . '.' . $ext;
            $destDir = __DIR__ . '/../uploads/clearance_docs/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            $dest = $destDir . $fname;
            if (!move_uploaded_file($_FILES['tax_clearance_proof']['tmp_name'], $dest)) {
                $errors[] = 'Failed to save uploaded file.';
            } else {
                $uploadPath = 'uploads/clearance_docs/' . $fname;
            }
        }
    }

    if (empty($errors)) {
        $affiliateId = generateAffiliateId($db);
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO affiliates
            (affiliate_id, full_name, phone_number, email, password, city, national_id, tax_clearance,
             authentication_code, tax_clearance_proof, payment_method, ecocash_number, bank_name,
             account_name, account_number, program)
            VALUES
            (:affiliate_id, :full_name, :phone, :email, :password, :city, :national_id, :tax_clearance,
             :authentication_code, :tax_clearance_proof, :payment_method, :ecocash_number, :bank_name,
             :account_name, :account_number, :program)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':affiliate_id' => $affiliateId,
            ':full_name' => $full_name,
            ':phone' => $phone,
            ':email' => $email,
            ':password' => $passwordHash,
            ':city' => $city,
            ':national_id' => $national_id,
            ':tax_clearance' => $tax_clearance,
            ':authentication_code' => $authentication_code,
            ':tax_clearance_proof' => $uploadPath,
            ':payment_method' => $payment_method,
            ':ecocash_number' => $ecocash_number,
            ':bank_name' => $bank_name,
            ':account_name' => $account_name,
            ':account_number' => $account_number,
            ':program' => $program
        ]);

        $success = "Registered successfully. Your Affiliate ID: $affiliateId";
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Affiliates Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/register.css">
</head>

<body>
    <div class="container">
        <div class="register-card">
            <!-- Header section with title and subtitle -->
            <div class="logo">
                <h1>Join Our Network</h1>
                <p>Create your affiliate account in just a few steps</p>
            </div>

            <!-- Progress indicator showing current step -->
            <div class="progress-bar">
                <div class="progress-step active" data-step="1">
                    <div class="progress-circle">1</div>
                    <span class="progress-label">Personal Info</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="2">
                    <div class="progress-circle">2</div>
                    <span class="progress-label">Verification</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="3">
                    <div class="progress-circle">3</div>
                    <span class="progress-label">Payment</span>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $e): ?>
                        <?php echo htmlspecialchars($e); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" id="registerForm">
                <!-- Step 1: Personal and Contact Details -->
                <div class="form-step active" data-step="1">
                    <h2 class="step-title">Personal & Contact Details</h2>
                    <p class="step-description">Let's start with your basic information</p>

                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                placeholder="Enter your full name"
                                required
                                autocomplete="name"
                                value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <div class="input-wrapper">
                            <input
                                type="tel"
                                id="phone_number"
                                name="phone_number"
                                placeholder="e.g., +263771234567"
                                required
                                autocomplete="tel"
                                pattern="^\+?[0-9]+"
                                value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                            <div class="input-hint">This will be your unique identifier</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="your.email@example.com"
                                autocomplete="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <div class="input-hint">Optional - for account recovery</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Create a password"
                                    required
                                    autocomplete="new-password">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">Confirm Password</label>
                            <div class="input-wrapper">
                                <input
                                    type="password"
                                    id="password_confirm"
                                    name="password_confirm"
                                    placeholder="Repeat password"
                                    required
                                    autocomplete="new-password">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="city">City/Town</label>
                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="city"
                                name="city"
                                placeholder="Enter your city or town"
                                required
                                value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="program">Program</label>
                        <div class="input-wrapper">
                            <select id="program" name="program" required class="select-input">
                                <option value="GS" <?php echo (($_POST['program'] ?? 'GS') === 'GS') ? 'selected' : ''; ?>>GetSolar</option>
                                <option value="TV" <?php echo (($_POST['program'] ?? '') === 'TV') ? 'selected' : ''; ?>>TechVouch</option>
                            </select>
                            <div class="input-hint">Choose the program you want to join</div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-next" onclick="nextStep()">Continue</button>
                </div>

                <!-- Step 2: Compliance and Verification -->
                <div class="form-step" data-step="2">
                    <h2 class="step-title">Compliance & Verification</h2>
                    <p class="step-description">Help us verify your identity and legal status</p>

                    <div class="form-group">
                        <label for="national_id">National ID / Passport Number</label>
                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="national_id"
                                name="national_id"
                                placeholder="Enter your ID or passport number"
                                required
                                value="<?php echo htmlspecialchars($_POST['national_id'] ?? ''); ?>">
                            <div class="input-hint">Required for verification purposes</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tax Clearance Status</label>
                        <div class="checkbox-wrapper">
                            <label class="checkbox-label">
                                <input
                                    type="checkbox"
                                    id="tax_clearance"
                                    name="tax_clearance"
                                    value="1"
                                    <?php echo isset($_POST['tax_clearance']) ? 'checked' : ''; ?>>
                                <span class="checkbox-custom"></span>
                                <span class="checkbox-text">I have tax clearance</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="auth_code_group" style="display: none;">
                        <label for="authentication_code">Authentication Code</label>
                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="authentication_code"
                                name="authentication_code"
                                placeholder="Enter your ZIMRA clearance code"
                                value="<?php echo htmlspecialchars($_POST['authentication_code'] ?? ''); ?>">
                            <div class="input-hint">Required only if you have tax clearance</div>
                        </div>
                    </div>

                    <div class="form-group" id="proof_clearance_group" style="display: none;">
                        <label for="tax_clearance_proof">Proof of Clearance</label>
                        <div class="file-upload-wrapper">
                            <input
                                type="file"
                                id="tax_clearance_proof"
                                name="tax_clearance_proof"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="file-input">
                            <label for="tax_clearance_proof" class="file-label">
                                <span class="file-icon">📄</span>
                                <span class="file-text">Choose file (PDF/JPEG)</span>
                                <span class="file-name"></span>
                            </label>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn btn-back" onclick="prevStep()">Back</button>
                        <button type="button" class="btn btn-next" onclick="nextStep()">Continue</button>
                    </div>
                </div>

                <!-- Step 3: Payment Details -->
                <div class="form-step" data-step="3">
                    <h2 class="step-title">Payment Details</h2>
                    <p class="step-description">Tell us how you'd like to receive commissions</p>

                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <div class="input-wrapper">
                            <select
                                id="payment_method"
                                name="payment_method"
                                class="select-input"
                                required>
                                <option value="none" <?php echo (($_POST['payment_method'] ?? '') === 'none') ? 'selected' : ''; ?>>Select payment method</option>
                                <option value="ecocash" <?php echo (($_POST['payment_method'] ?? '') === 'ecocash') ? 'selected' : ''; ?>>EcoCash</option>
                                <option value="bank" <?php echo (($_POST['payment_method'] ?? '') === 'bank') ? 'selected' : ''; ?>>Bank Transfer</option>
                            </select>
                        </div>
                    </div>

                    <!-- EcoCash fields (shown when EcoCash is selected) -->
                    <div class="payment-fields" id="ecocash_fields" style="display: none;">
                        <div class="form-group">
                            <label for="ecocash_number">EcoCash Number</label>
                            <div class="input-wrapper">
                                <input
                                    type="tel"
                                    id="ecocash_number"
                                    name="ecocash_number"
                                    placeholder="e.g., +263771234567"
                                    value="<?php echo htmlspecialchars($_POST['ecocash_number'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Bank fields (shown when Bank Transfer is selected) -->
                    <div class="payment-fields" id="bank_fields" style="display: none;">
                        <div class="form-group">
                            <label for="bank_name">Bank Name</label>
                            <div class="input-wrapper">
                                <input
                                    type="text"
                                    id="bank_name"
                                    name="bank_name"
                                    placeholder="Enter your bank name"
                                    value="<?php echo htmlspecialchars($_POST['bank_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="account_name">Account Name</label>
                            <div class="input-wrapper">
                                <input
                                    type="text"
                                    id="account_name"
                                    name="account_name"
                                    placeholder="Name on the account"
                                    value="<?php echo htmlspecialchars($_POST['account_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="account_number">Account Number</label>
                            <div class="input-wrapper">
                                <input
                                    type="text"
                                    id="account_number"
                                    name="account_number"
                                    placeholder="Your bank account number"
                                    value="<?php echo htmlspecialchars($_POST['account_number'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Agreement</label>
                        <div class="checkbox-wrapper">
                            <label class="checkbox-label" id="terms_label">
                                <input
                                    type="checkbox"
                                    id="terms_agreement"
                                    required>
                                <span class="checkbox-custom"></span>
                                <span class="checkbox-text">I agree to the terms and conditions</span>
                            </label>
                            <div class="checkbox-error" id="terms_error">
                                You must agree to the terms and conditions to continue
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn btn-back" onclick="prevStep()">Back</button>
                        <button type="submit" class="btn btn-submit">Complete Registration</button>
                    </div>
                </div>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;

        // Initialize form state when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updatePaymentFields();
            updateAuthCodeField(); // Check initial state of tax clearance checkbox
            setupFileUpload();

            // If form was submitted with tax clearance checked, show the fields
            const taxCheckbox = document.getElementById('tax_clearance');
            if (taxCheckbox && taxCheckbox.checked) {
                updateAuthCodeField();
            }
        });

        // Handle navigation between form steps
        function nextStep() {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    // Hide current step
                    document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.remove('active');
                    document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('active');
                    document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('completed');

                    currentStep++;

                    // Show next step
                    document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.add('active');
                    document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('active');

                    // Smooth scroll to top of form
                    document.querySelector('.register-card').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                // Hide current step
                document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.remove('active');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('active');

                currentStep--;

                // Show previous step
                document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.add('active');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('completed');
                document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('active');

                document.querySelector('.register-card').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Validate fields in current step before allowing progression
        function validateStep(step) {
            const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
            const inputs = stepElement.querySelectorAll('input[required], select[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('invalid');
                    isValid = false;
                } else {
                    input.classList.remove('invalid');
                }

                // Special validation for password matching
                if (step === 1 && input.id === 'password_confirm') {
                    const password = document.getElementById('password').value;
                    if (input.value !== password) {
                        input.classList.add('invalid');
                        isValid = false;
                    }
                }
            });

            if (!isValid) {
                // Show a brief message to user
                const firstInvalid = stepElement.querySelector('.invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }

            return isValid;
        }

        // Show/hide payment fields based on selected method
        const paymentMethodSelect = document.getElementById('payment_method');
        paymentMethodSelect.addEventListener('change', updatePaymentFields);

        function updatePaymentFields() {
            const method = paymentMethodSelect.value;
            const ecocashFields = document.getElementById('ecocash_fields');
            const bankFields = document.getElementById('bank_fields');

            // Hide all payment fields first
            ecocashFields.style.display = 'none';
            bankFields.style.display = 'none';

            // Show relevant fields based on selection
            if (method === 'ecocash') {
                ecocashFields.style.display = 'block';
                document.getElementById('ecocash_number').setAttribute('required', 'required');
                document.getElementById('bank_name').removeAttribute('required');
                document.getElementById('account_name').removeAttribute('required');
                document.getElementById('account_number').removeAttribute('required');
            } else if (method === 'bank') {
                bankFields.style.display = 'block';
                document.getElementById('ecocash_number').removeAttribute('required');
                document.getElementById('bank_name').setAttribute('required', 'required');
                document.getElementById('account_name').setAttribute('required', 'required');
                document.getElementById('account_number').setAttribute('required', 'required');
            } else {
                // No payment method selected
                document.getElementById('ecocash_number').removeAttribute('required');
                document.getElementById('bank_name').removeAttribute('required');
                document.getElementById('account_name').removeAttribute('required');
                document.getElementById('account_number').removeAttribute('required');
            }
        }

        // Update authentication code and proof fields based on tax clearance status
        const taxClearanceCheckbox = document.getElementById('tax_clearance');
        taxClearanceCheckbox.addEventListener('change', updateAuthCodeField);

        function updateAuthCodeField() {
            const authCodeGroup = document.getElementById('auth_code_group');
            const proofClearanceGroup = document.getElementById('proof_clearance_group');
            const authCodeInput = document.getElementById('authentication_code');

            if (taxClearanceCheckbox.checked) {
                // Show fields when tax clearance is checked
                authCodeGroup.style.display = 'block';
                proofClearanceGroup.style.display = 'block';
                authCodeInput.setAttribute('required', 'required');
            } else {
                // Hide fields when tax clearance is not checked
                authCodeGroup.style.display = 'none';
                proofClearanceGroup.style.display = 'none';
                authCodeInput.removeAttribute('required');
            }
        }

        // Custom file upload styling and feedback
        function setupFileUpload() {
            const fileInput = document.getElementById('tax_clearance_proof');
            const fileName = document.querySelector('.file-name');

            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    fileName.textContent = e.target.files[0].name;
                    fileName.style.display = 'inline';
                } else {
                    fileName.textContent = '';
                    fileName.style.display = 'none';
                }
            });
        }

        // Real-time validation for password matching
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('password_confirm');

        passwordConfirmInput.addEventListener('input', function() {
            if (passwordInput.value !== passwordConfirmInput.value) {
                passwordConfirmInput.classList.add('invalid');
            } else {
                passwordConfirmInput.classList.remove('invalid');
            }
        });

        // Remove invalid class on input focus
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('invalid');
            });
        });

        // Form submission validation
        const registerForm = document.getElementById('registerForm');
        registerForm.addEventListener('submit', function(e) {
            const termsCheckbox = document.getElementById('terms_agreement');
            const termsError = document.getElementById('terms_error');
            const termsLabel = document.getElementById('terms_label');

            // Check if we're on step 3 (where terms agreement is)
            if (currentStep === 3 && !termsCheckbox.checked) {
                e.preventDefault();

                // Show error message
                termsError.classList.add('show');
                termsLabel.classList.add('error');

                // Add shake animation to checkbox
                termsLabel.style.animation = 'none';
                setTimeout(() => {
                    termsLabel.style.animation = 'shake 0.4s ease-in-out';
                }, 10);

                // Scroll to the error
                termsError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                return false;
            }
        });

        // Remove error when checkbox is checked
        const termsCheckbox = document.getElementById('terms_agreement');
        const termsError = document.getElementById('terms_error');
        const termsLabel = document.getElementById('terms_label');

        termsCheckbox.addEventListener('change', function() {
            if (this.checked) {
                termsError.classList.remove('show');
                termsLabel.classList.remove('error');
            }
        });
    </script>
</body>

</html>