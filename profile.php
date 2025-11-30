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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($full_name === '') $errors[] = 'Full name required.';
    // handle optional password change
    $passwordSql = '';
    $params = [':full_name'=>$full_name, ':email'=>$email, ':city'=>$city, ':id'=>$_SESSION['user_id']];
    if ($password !== '') {
        $passwordSql = ", password = :password";
        $params[':password'] = password_hash($password, PASSWORD_BCRYPT);
    }

    // handle file upload
    $uploadPath = $user['tax_clearance_proof'];
    if (isset($_FILES['tax_clearance_proof']) && $_FILES['tax_clearance_proof']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['application/pdf','image/jpeg','image/png','image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['tax_clearance_proof']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            $errors[] = 'Tax clearance proof must be PDF or JPG/PNG.';
        } else {
            $ext = pathinfo($_FILES['tax_clearance_proof']['name'], PATHINFO_EXTENSION);
            $fname = uniqid('clear_') . '.' . $ext;
            $destDir = __DIR__ . '/../uploads/clearance_docs/';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $dest = $destDir . $fname;
            if (move_uploaded_file($_FILES['tax_clearance_proof']['tmp_name'], $dest)) {
                $uploadPath = 'uploads/clearance_docs/' . $fname;
            } else {
                $errors[] = 'Failed to save uploaded file.';
            }
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE affiliates SET full_name = :full_name, email = :email, city = :city, tax_clearance_proof = :tax_clearance_proof {$passwordSql} WHERE id = :id";
        $params[':tax_clearance_proof'] = $uploadPath;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $success = 'Profile updated successfully.';
        // refresh
        $stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        $_SESSION['full_name'] = $user['full_name'];
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
        
        .form-input {
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
        
        .form-input::placeholder {
            color: var(--text-secondary);
            opacity: 0.5;
        }
        
        .form-input:hover {
            border-color: var(--border-light);
        }
        
        .form-input:focus {
            border-color: var(--accent);
            background: var(--bg-card);
            box-shadow: 0 0 0 4px rgba(201, 203, 216, 0.08);
            transform: translateY(-1px);
        }
        
        .form-input[type="file"] {
            padding: 12px 18px;
            cursor: pointer;
        }
        
        .form-input[type="file"]::file-selector-button {
            padding: 8px 16px;
            background: var(--accent);
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--bg-primary);
            cursor: pointer;
            margin-right: 12px;
            transition: all 0.3s ease;
        }
        
        .form-input[type="file"]::file-selector-button:hover {
            background: var(--accent-hover);
        }
        
        .form-hint {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 6px;
            font-weight: 500;
        }
        
        .current-file {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 13px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 8px;
        }
        
        .current-file:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
        }
        
        .btn-submit {
            padding: 14px 32px;
            background: var(--accent);
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            color: var(--bg-primary);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(201, 203, 216, 0.15);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            background: var(--accent-hover);
            box-shadow: 0 8px 24px rgba(201, 203, 216, 0.25);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
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
            background: rgba(48, 209, 88, 0.08);
            border: 1px solid rgba(48, 209, 88, 0.25);
            color: var(--success);
        }
        
        .alert-error {
            background: rgba(255, 69, 58, 0.08);
            border: 1px solid rgba(255, 69, 58, 0.25);
            color: var(--error);
        }
        
        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            margin-bottom: 24px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 24px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-bottom: 32px;
        }
        
        .profile-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--bg-primary);
            font-size: 32px;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(201, 203, 216, 0.2);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }
        
        .profile-meta {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .danger-zone {
            background: rgba(255, 69, 58, 0.05);
            border: 1px solid rgba(255, 69, 58, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-top: 32px;
        }
        
        .danger-zone-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--error);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .danger-zone-desc {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 16px;
            line-height: 1.6;
        }
        
        .btn-danger {
            padding: 12px 24px;
            background: rgba(255, 69, 58, 0.1);
            border: 1.5px solid rgba(255, 69, 58, 0.3);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--error);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-danger:hover {
            background: rgba(255, 69, 58, 0.15);
            border-color: var(--error);
            transform: translateY(-2px);
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
            <h1 class="mobile-title">My Profile</h1>
        </div>

        <!-- Sidebar Navigation -->
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

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="content-header">
                <div class="header-text">
                    <h1 class="page-title">My Profile</h1>
                    <p class="page-subtitle">Manage your account information and settings</p>
                </div>
            </div>

            <!-- Profile Header Card -->
            <div class="profile-header">
                <div class="profile-avatar-large">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="profile-meta">
                        <span style="display: inline-flex; align-items: center; gap: 6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </span>
                        <span style="margin: 0 12px; color: var(--border);">•</span>
                        <span style="display: inline-flex; align-items: center; gap: 6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php echo htmlspecialchars($user['city']); ?>
                        </span>
                    </p>
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

            <!-- Profile Form -->
            <div class="form-card">
                <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 24px;">Personal Information</h3>
                
                <form method="post" enctype="multipart/form-data" id="profileForm">
                    <div class="form-group">
                        <label class="form-label" for="full_name">
                            Full Name
                            <span style="color: var(--error);">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="full_name" 
                            name="full_name" 
                            class="form-input" 
                            placeholder="Enter your full name"
                            value="<?php echo htmlspecialchars($user['full_name']); ?>"
                            required>
                        <p class="form-hint">Your full name as it appears on official documents</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">
                            Email Address
                            <span style="color: var(--error);">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="your.email@example.com"
                            value="<?php echo htmlspecialchars($user['email']); ?>"
                            required>
                        <p class="form-hint">We'll use this email for important notifications</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="city">
                            City
                        </label>
                        <input 
                            type="text" 
                            id="city" 
                            name="city" 
                            class="form-input" 
                            placeholder="Enter your city"
                            value="<?php echo htmlspecialchars($user['city']); ?>">
                        <p class="form-hint">Your current city of residence</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tax_clearance_proof">
                            Tax Clearance Proof
                        </label>
                        <input 
                            type="file" 
                            id="tax_clearance_proof" 
                            name="tax_clearance_proof" 
                            class="form-input"
                            accept=".pdf,.jpg,.jpeg,.png">
                        <p class="form-hint">Upload PDF, JPG, or PNG (Max 5MB)</p>
                        
                        <?php if($user['tax_clearance_proof']): ?>
                        <a href="/<?php echo htmlspecialchars($user['tax_clearance_proof']); ?>" target="_blank" class="current-file">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            View Current Document
                        </a>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            New Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Leave blank to keep current password">
                        <p class="form-hint">Only fill this if you want to change your password</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Save Changes
                        </button>
                    </div>
</form>
            </div>

            <!-- Danger Zone -->
            <div class="danger-zone">
                <h3 class="danger-zone-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    Danger Zone
                </h3>
                <p class="danger-zone-desc">
                    Once you delete your account, there is no going back. This action will mark your account as deleted and you won't be able to access it anymore. However, an admin can restore your account if needed.
                </p>
                <a href="delete_account.php" class="btn-danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Delete My Account
                </a>
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
            const form = document.getElementById('profileForm');
            const inputs = form.querySelectorAll('.form-input');
            
            inputs.forEach(input => {
                if (input.type !== 'file') {
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
                }
            });
        });
    </script>
</body>
</html>