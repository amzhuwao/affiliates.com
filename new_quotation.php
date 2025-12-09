<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// public/new_quotation.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireAdmin();

// Fetch user info (admin)
$stmtUser = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmtUser->execute([':id' => $_SESSION['user_id']]);
$user = $stmtUser->fetch();

// Fetch all active affiliates for the dropdown
$stmtAffiliates = $db->prepare("SELECT id, affiliate_id, full_name FROM affiliates WHERE role = 'affiliate' AND status = 'active' ORDER BY full_name ASC");
$stmtAffiliates->execute();
$affiliates = $stmtAffiliates->fetchAll();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $affiliate_id = $_POST['affiliate_id'] ?? null;
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $estimated_value = $_POST['estimated_value'] ?? null;

    if (!$affiliate_id) $errors[] = 'Affiliate selection required.';
    if ($customer_name === '') $errors[] = 'Customer name required.';
    if ($customer_phone === '') $errors[] = 'Customer phone required.';

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO quotations
            (affiliate_id, customer_name, customer_phone, description, estimated_value, status, created_at)
            VALUES (:aid, :cname, :cphone, :desc, :est, 'pending', NOW())");
        $stmt->execute([
            ':aid' => $affiliate_id,
            ':cname' => $customer_name,
            ':cphone' => $customer_phone,
            ':desc' => $description,
            ':est' => $estimated_value
        ]);
        $success = "Quotation created successfully and assigned to affiliate.";
        // Clear form after successful submission
        $affiliate_id = '';
        $customer_name = '';
        $customer_phone = '';
        $description = '';
        $estimated_value = '';
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Quotation - Admin Portal</title>
    <link rel="icon" type="image/png" href="branding/anako favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css?v=2.0">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            overflow-x: hidden;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            letter-spacing: 0.2px;
        }

        .form-input,
        .form-textarea,
        .form-input select,
        select.form-input {
            width: 100%;
            padding: 14px 18px;
            background: var(--bg-secondary);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            color: var(--text-primary);
            font-family: inherit;
            font-weight: 500;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: var(--text-secondary);
            opacity: 0.5;
        }

        .form-input:hover,
        .form-textarea:hover {
            border-color: var(--border-light);
        }

        .form-input:focus,
        .form-textarea:focus {
            border-color: var(--accent);
            background: var(--bg-card);
            box-shadow: 0 0 0 4px rgba(2, 167, 90, 0.1);
            transform: translateY(-1px);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        .form-hint {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 6px;
            font-weight: 500;
        }

        .btn-submit {
            padding: 14px 32px;
            background: var(--accent);
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(2, 167, 90, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            background: var(--accent-hover);
            box-shadow: 0 8px 24px rgba(2, 167, 90, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .btn-secondary {
            padding: 14px 24px;
            background: var(--bg-secondary);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: var(--bg-secondary);
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.5s ease-out;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: rgba(2, 167, 90, 0.1);
            border: 1px solid rgba(2, 167, 90, 0.25);
            color: var(--success);
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.25);
            color: var(--error);
        }

        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* Responsive adjustments for small screens */
        @media (max-width: 480px) {
            .form-card {
                padding: 20px 16px;
                border-radius: 12px;
            }

            .form-input,
            .form-textarea {
                padding: 12px 14px;
                font-size: 16px;
                /* Prevents iOS zoom on focus */
            }

            .form-actions {
                flex-direction: column;
                gap: 12px;
            }

            .btn-submit,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Mobile Header -->
        <div class="mobile-header">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <h1 class="mobile-title">Create Quotation</h1>
        </div>

        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <img src="branding/ANAKO LOGO.png" alt="ANAKO" class="brand-logo">
            </div>
            <div class="sidebar-header">
                <h2 class="sidebar-title">Admin Portal</h2>
                <p class="sidebar-subtitle">Management Dashboard</p>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="admin/affiliates.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Affiliate Management</span>
                </a>

                <a href="admin/quotations.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        <path d="M9 12h6m-6 4h6"></path>
                    </svg>
                    <span>Quotations</span>
                </a>

                <a href="new_quotation.php" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <span>New Quotation</span>
                </a>

                <a href="admin/commisions.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span>Commissions</span>
                </a>

                <a href="admin/commission_payout.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    <span>Commission Payouts</span>
                </a>

                <a href="admin/export_csv.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Export Data</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <p class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                        <p class="user-role">Administrator</p>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <svg class="logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="content-header">
                <div class="header-text">
                    <h1 class="page-title">Create New Quotation</h1>
                    <p class="page-subtitle">Create and assign a quotation to an affiliate</p>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; flex-shrink: 0;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; flex-shrink: 0;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endforeach; ?>

            <!-- Form Card -->
            <div class="form-card">
                <form method="post" id="quotationForm">
                    <div class="form-group">
                        <label class="form-label" for="affiliate_id">
                            Select Affiliate
                            <span style="color: var(--error);">*</span>
                        </label>
                        <select
                            id="affiliate_id"
                            name="affiliate_id"
                            class="form-input"
                            required>
                            <option value="">-- Choose an affiliate --</option>
                            <?php foreach ($affiliates as $aff): ?>
                                <option value="<?php echo htmlspecialchars($aff['id']); ?>" <?php echo (isset($affiliate_id) && $affiliate_id == $aff['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($aff['affiliate_id'] . ' - ' . $aff['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="form-hint">Select the affiliate to assign this quotation to</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="customer_name">
                            Customer Name
                            <span style="color: var(--error);">*</span>
                        </label>
                        <input
                            type="text"
                            id="customer_name"
                            name="customer_name"
                            class="form-input"
                            placeholder="Enter customer's full name"
                            value="<?php echo isset($customer_name) ? htmlspecialchars($customer_name) : ''; ?>"
                            required>
                        <p class="form-hint">The full name of the customer for this quotation</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="customer_phone">
                            Customer Phone
                            <span style="color: var(--error);">*</span>
                        </label>
                        <input
                            type="tel"
                            id="customer_phone"
                            name="customer_phone"
                            class="form-input"
                            placeholder="e.g., +63 912 345 6789"
                            value="<?php echo isset($customer_phone) ? htmlspecialchars($customer_phone) : ''; ?>"
                            required>
                        <p class="form-hint">Customer's contact number for follow-up</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">
                            Description
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-textarea"
                            placeholder="Provide details about the quotation request..."><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        <p class="form-hint">Optional: Add any relevant details or special requirements</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="estimated_value">
                            Estimated Value ($)
                        </label>
                        <input
                            type="number"
                            id="estimated_value"
                            name="estimated_value"
                            class="form-input"
                            placeholder="0.00"
                            step="0.01"
                            min="0"
                            value="<?php echo isset($estimated_value) ? htmlspecialchars($estimated_value) : ''; ?>">
                        <p class="form-hint">Optional: Your estimated value for this quotation</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Create Quotation
                        </button>
                        <a href="admin/quotations.php" class="btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                                <path d="M19 12H5M12 19l-7-7 7-7"></path>
                            </svg>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Info Box -->
            <div style="margin-top: 32px; padding: 20px 24px; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 12px; border-left: 3px solid var(--accent);">
                <div style="display: flex; gap: 16px; align-items: start;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; color: var(--accent); flex-shrink: 0; margin-top: 2px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <div>
                        <p style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 6px;">What happens next?</p>
                        <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 8px;">
                            Once you create this quotation, it will be assigned to the selected affiliate. The affiliate will see it in their quotations list.
                        </p>
                        <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">
                            You can manage and update the quotation status from the 
                            <a href="admin/quotations.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Quotations Management</a> page.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');

            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    menuToggle.classList.toggle('active');
                });

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                            sidebar.classList.remove('active');
                            menuToggle.classList.remove('active');
                        }
                    }
                });
            }

            // Form validation feedback
            const form = document.getElementById('quotationForm');
            const inputs = form.querySelectorAll('.form-input, .form-textarea');

            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && !this.value.trim()) {
                        this.style.borderColor = 'var(--error)';
                    } else {
                        this.style.borderColor = 'var(--border)';
                    }
                });

                input.addEventListener('input', function() {
                    if (this.style.borderColor === 'var(--error)' || this.style.borderColor === 'rgb(255, 69, 58)') {
                        if (this.value.trim()) {
                            this.style.borderColor = 'var(--border)';
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>