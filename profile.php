<?php
// public/profile.php
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/functions.php';
requireLogin();

$errors = [];
$success = '';

$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

$isAdmin = ($_SESSION['role'] === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($full_name === '') $errors[] = 'Full name required.';
    if ($phone_number === '') $errors[] = 'Phone number required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

    // Optional password update
    $passwordSql = '';
    $params = [
        ':full_name' => $full_name,
        ':email' => $email,
        ':phone_number' => $phone_number,
        ':city' => $city,
        ':id' => $_SESSION['user_id']
    ];
    if ($password !== '') {
        $passwordSql = ", password = :password";
        $params[':password'] = password_hash($password, PASSWORD_BCRYPT);
    }

    // File upload
    $uploadPath = $user['tax_clearance_proof'];
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

            if (move_uploaded_file($_FILES['tax_clearance_proof']['tmp_name'], $dest)) {
                $uploadPath = 'uploads/clearance_docs/' . $fname;
            } else {
                $errors[] = 'Failed to save uploaded file.';
            }
        }
    }

    // Update profile
    if (empty($errors)) {
        $sql = "UPDATE affiliates SET 
                full_name = :full_name,
                email = :email,
                phone_number = :phone_number,
                city = :city,
                tax_clearance_proof = :tax_clearance_proof
                {$passwordSql}
                WHERE id = :id";
        $params[':tax_clearance_proof'] = $uploadPath;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $success = 'Profile updated successfully!';

        // Refresh session data
        $_SESSION['full_name'] = $full_name;

        // Refresh user data
        $stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Affiliates Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Profile Page Custom Styles */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-back:hover {
            background: var(--bg-secondary);
            border-color: var(--accent);
            transform: translateX(-4px);
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 20px;
        }

        /* Alert Styles */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 20px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid;
            animation: slideDown 0.4s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: rgba(48, 209, 88, 0.1);
            border-color: rgba(48, 209, 88, 0.3);
            color: var(--success);
        }

        .alert-error {
            background: rgba(255, 69, 58, 0.1);
            border-color: rgba(255, 69, 58, 0.3);
            color: var(--error);
        }

        .alert-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .alert-message {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Profile Grid */
        .profile-grid {
            display: grid;
            gap: 32px;
        }

        .profile-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }

        .profile-card:hover {
            border-color: var(--border-light);
            box-shadow: 0 8px 32px var(--shadow-sm);
        }

        .card-header-section {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(201, 203, 216, 0.15) 0%, rgba(201, 203, 216, 0.05) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .card-icon svg {
            width: 24px;
            height: 24px;
            color: var(--accent);
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }

        .card-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
            line-height: 1.5;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            letter-spacing: 0.1px;
        }

        .required {
            color: var(--error);
            margin-left: 4px;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            background: var(--bg-secondary);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text-primary);
            font-family: inherit;
            font-weight: 500;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: var(--accent);
            background: var(--bg-primary);
            box-shadow: 0 0 0 4px rgba(201, 203, 216, 0.08);
        }

        .form-input::placeholder {
            color: var(--text-secondary);
            opacity: 0.6;
        }

        .form-hint {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 8px;
            font-weight: 500;
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-input {
            cursor: pointer;
        }

        .file-input::-webkit-file-upload-button {
            padding: 10px 20px;
            background: var(--accent);
            border: none;
            border-radius: 8px;
            color: var(--bg-primary);
            font-weight: 600;
            cursor: pointer;
            margin-right: 12px;
            transition: all 0.3s ease;
        }

        .file-input::-webkit-file-upload-button:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        .current-file-info {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding: 12px 16px;
            background: rgba(48, 209, 88, 0.1);
            border: 1px solid rgba(48, 209, 88, 0.2);
            border-radius: 8px;
            font-size: 13px;
            color: var(--success);
            font-weight: 600;
        }

        .file-link {
            color: var(--success);
            text-decoration: underline;
            transition: opacity 0.3s ease;
        }

        .file-link:hover {
            opacity: 0.8;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 12px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 32px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            color: var(--bg-primary);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(201, 203, 216, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(201, 203, 216, 0.3);
        }

        .btn-primary svg {
            width: 18px;
            height: 18px;
        }

        /* Danger Zone Styles */
        .danger-card {
            border-color: rgba(255, 69, 58, 0.2);
            background: linear-gradient(135deg, rgba(255, 69, 58, 0.03) 0%, var(--bg-card) 100%);
        }

        .danger-card:hover {
            border-color: rgba(255, 69, 58, 0.3);
        }

        .danger-icon {
            background: linear-gradient(135deg, rgba(255, 69, 58, 0.15) 0%, rgba(255, 69, 58, 0.05) 100%);
        }

        .danger-icon svg {
            color: var(--error);
        }

        .danger-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .danger-info {
            flex: 1;
        }

        .danger-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .danger-description {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
            font-weight: 500;
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(255, 69, 58, 0.1);
            border: 1.5px solid rgba(255, 69, 58, 0.3);
            border-radius: 10px;
            color: var(--error);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
        }

        .btn-danger:hover {
            background: rgba(255, 69, 58, 0.15);
            border-color: rgba(255, 69, 58, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(255, 69, 58, 0.2);
        }

        .btn-danger svg {
            width: 18px;
            height: 18px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .content-header {
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .profile-card {
                padding: 24px 20px;
            }

            .card-header-section {
                flex-direction: column;
                gap: 12px;
            }

            .danger-content {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-danger {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .alert {
                padding: 16px;
                gap: 12px;
            }

            .btn-primary {
                width: 100%;
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
        <h1 class="mobile-title"><?php echo $isAdmin ? 'Admin Panel' : 'Affiliate Portal'; ?></h1>
    </div>

    <!-- Sidebar Navigation -->
    <?php if ($isAdmin): ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2 class="sidebar-logo">Admin Portal</h2>
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
    <?php else: ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2 class="sidebar-logo">Affiliate Portal</h2>
            <p class="sidebar-subtitle">Your Dashboard</p>
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
            
            <a href="quotations.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    <path d="M9 12h6m-6 4h6"></path>
                </svg>
                <span>My Quotations</span>
            </a>

            <a href="new_quotation.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="12" y1="18" x2="12" y2="12"></line>
                    <line x1="9" y1="15" x2="15" y2="15"></line>
                </svg>
                <span>New Quotation</span>
            </a>

            <a href="commisions.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span>My Commissions</span>
            </a>

            <a href="profile.php" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>My Profile</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <p class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                    <p class="user-role">Affiliate</p>
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
    <?php endif; ?>

    <main class="main-content">
        <div class="content-header">
            <div class="header-text">
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">Manage your personal information and account settings</p>
            </div>
            <a href="dashboard.php" class="btn-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Dashboard
            </a>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div class="alert-content">
                    <p class="alert-title">Success!</p>
                    <p class="alert-message"><?= htmlspecialchars($success); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <div class="alert-content">
                        <p class="alert-title">Error</p>
                        <p class="alert-message"><?= htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Account Information Card -->
            <div class="profile-card">
                <div class="card-header-section">
                    <div class="card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div>
                        <h2 class="card-title">Account Information</h2>
                        <p class="card-subtitle">Update your personal details and contact information</p>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" id="profileForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                Full Name
                                <span class="required">*</span>
                            </label>
                            <input type="text" name="full_name" class="form-input"
                                   value="<?= htmlspecialchars($user['full_name']); ?>" required
                                   placeholder="Enter your full name">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Email Address
                                <span class="required">*</span>
                            </label>
                            <input type="email" name="email" class="form-input"
                                   value="<?= htmlspecialchars($user['email']); ?>" required
                                   placeholder="your.email@example.com">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Phone Number
                                <span class="required">*</span>
                            </label>
                            <input type="text" name="phone_number" class="form-input"
                                   value="<?= htmlspecialchars($user['phone_number']); ?>" required
                                   placeholder="+1 (555) 000-0000">
                        </div>

                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-input"
                                   value="<?= htmlspecialchars($user['city']); ?>"
                                   placeholder="Your city">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 6px;">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                <polyline points="13 2 13 9 20 9"></polyline>
                            </svg>
                            Tax Clearance Proof
                        </label>
                        <div class="file-input-wrapper">
                            <input type="file" name="tax_clearance_proof" class="form-input file-input"
                                   accept=".pdf,.jpg,.jpeg,.png" id="fileInput">
                            <?php if ($user['tax_clearance_proof']): ?>
                                <div class="current-file-info">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <span>Current document:</span>
                                    <a href="/<?= htmlspecialchars($user['tax_clearance_proof']); ?>" 
                                       target="_blank" class="file-link">
                                        View Document
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="form-hint">Upload PDF, JPG, or PNG (Max 5MB)</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 6px;">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            New Password
                        </label>
                        <input type="password" name="password" class="form-input"
                               placeholder="Leave blank to keep current password">
                        <p class="form-hint">Only fill this if you want to change your password</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Danger Zone Card -->
            <div class="profile-card danger-card">
                <div class="card-header-section">
                    <div class="card-icon danger-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div>
                        <h2 class="card-title">Danger Zone</h2>
                        <p class="card-subtitle">Irreversible and destructive actions</p>
                    </div>
                </div>

                <div class="danger-content">
                    <div class="danger-info">
                        <h3 class="danger-title">Delete Account</h3>
                        <p class="danger-description">
                            Once you delete your account, your access will be disabled. 
                            An administrator can restore your account later if needed.
                        </p>
                    </div>
                    <a href="delete_account.php" class="btn-danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                        Delete My Account
                    </a>
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
    });
</script>
</body>
</html>
