<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id) { exit("Affiliate ID missing"); }

$stmt = $db->prepare("SELECT * FROM affiliates WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();
if (!$user) exit("Affiliate not found");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Affiliate - <?=htmlspecialchars($user['affiliate_id'])?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0d0d0f;
            --bg-secondary: #16171a;
            --bg-card: #1c1d21;
            --accent: #c9cbd8;
            --accent-dark: #a8aab5;
            --accent-hover: #e0e1e8;
            --text-primary: #f5f5f7;
            --text-secondary: #8e8e93;
            --border: #2c2c2e;
            --border-light: #3a3a3c;
            --error: #ff453a;
            --success: #30d158;
            --warning: #ff9f0a;
            --info: #0a84ff;
            --shadow-sm: rgba(0, 0, 0, 0.3);
            --shadow-lg: rgba(0, 0, 0, 0.6);
        }

        html {
            overflow-x: hidden;
            width: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            width: 100%;
            position: relative;
            overflow-x: hidden;
            padding: 40px 20px;
        }

        /* Animated background effects */
        body::before {
            content: '';
            position: fixed;
            top: -30%;
            right: -15%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(201, 203, 216, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 25s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -25%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(201, 203, 216, 0.03) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 20s ease-in-out infinite reverse;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(-40px, 40px) scale(1.05);
            }
        }

        /* Main container */
        .view-container {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Back link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--bg-secondary);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 24px;
        }

        .back-link:hover {
            background: var(--bg-primary);
            border-color: var(--accent);
            color: var(--accent);
            transform: translateX(-4px);
        }

        .back-link::before {
            content: '←';
            font-size: 18px;
        }

        /* Premium card wrapper */
        .details-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px var(--shadow-lg), 0 0 1px rgba(201, 203, 216, 0.1);
            backdrop-filter: blur(20px);
            animation: slideUp 0.6s ease-out;
            position: relative;
        }

        .details-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: 0.3;
            border-radius: 20px 20px 0 0;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header section */
        .card-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 32px;
            border-bottom: 1px solid var(--border);
        }

        .affiliate-id-badge {
            display: inline-block;
            padding: 10px 24px;
            background: linear-gradient(135deg, rgba(201, 203, 216, 0.12) 0%, rgba(201, 203, 216, 0.05) 100%);
            border: 1px solid rgba(201, 203, 216, 0.2);
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 12px;
            letter-spacing: 0.5px;
            text-shadow: 0 0 30px rgba(201, 203, 216, 0.15);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .info-item {
            background: var(--bg-secondary);
            padding: 24px;
            border-radius: 12px;
            border: 1px solid var(--border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .info-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: var(--accent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .info-item:hover {
            border-color: var(--border-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .info-item:hover::before {
            opacity: 1;
        }

        .info-label {
            font-size: 11px;
            color: var(--text-secondary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.2px;
            word-break: break-word;
        }

        /* Status badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: capitalize;
            border: 1px solid;
        }

        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.5;
                transform: scale(1.2);
            }
        }

        .status-badge.active {
            background: rgba(48, 209, 88, 0.12);
            color: var(--success);
            border-color: rgba(48, 209, 88, 0.3);
        }

        .status-badge.active::before {
            background: var(--success);
        }

        .status-badge.suspended {
            background: rgba(255, 159, 10, 0.12);
            color: var(--warning);
            border-color: rgba(255, 159, 10, 0.3);
        }

        .status-badge.suspended::before {
            background: var(--warning);
        }

        .status-badge.deleted {
            background: rgba(255, 69, 58, 0.12);
            color: var(--error);
            border-color: rgba(255, 69, 58, 0.3);
        }

        .status-badge.deleted::before {
            background: var(--error);
        }

        /* Document section */
        .document-section {
            background: var(--bg-secondary);
            padding: 24px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-top: 24px;
        }

        .document-title {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }

        .document-link {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 14px 24px;
            background: linear-gradient(135deg, rgba(201, 203, 216, 0.08) 0%, rgba(201, 203, 216, 0.03) 100%);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            color: var(--accent);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .document-link:hover {
            border-color: var(--accent);
            background: linear-gradient(135deg, rgba(201, 203, 216, 0.12) 0%, rgba(201, 203, 216, 0.05) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(201, 203, 216, 0.15);
        }

        .document-link::before {
            content: '📄';
            font-size: 20px;
        }

        .no-document {
            color: var(--text-secondary);
            font-size: 14px;
            font-style: italic;
        }

        /* Action buttons */
        .actions-section {
            margin-top: 32px;
            padding-top: 32px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            padding: 14px 28px;
            background: var(--accent);
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--bg-primary);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(201, 203, 216, 0.15);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            background: var(--accent-hover);
            box-shadow: 0 8px 24px rgba(201, 203, 216, 0.25);
        }

        /* Icon styles */
        .icon {
            width: 16px;
            height: 16px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 24px 16px;
            }

            .details-card {
                padding: 32px 24px;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .page-title {
                font-size: 24px;
            }

            .affiliate-id-badge {
                font-size: 16px;
                padding: 8px 20px;
            }

            .actions-section {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 20px 12px;
            }

            .details-card {
                padding: 24px 20px;
                border-radius: 16px;
            }

            .card-header {
                margin-bottom: 28px;
                padding-bottom: 24px;
            }

            .page-title {
                font-size: 20px;
            }

            .back-link {
                padding: 8px 16px;
                font-size: 13px;
            }

            body::before,
            body::after {
                width: 400px;
                height: 400px;
            }
        }
    </style>
</head>
<body>

<div class="view-container">
    <a href="affiliates.php" class="back-link">Back to Affiliates</a>

    <div class="details-card">
        <div class="card-header">
            <div class="affiliate-id-badge"><?=htmlspecialchars($user['affiliate_id'])?></div>
            <h1 class="page-title">Affiliate Details</h1>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Full Name
                </div>
                <div class="info-value"><?=htmlspecialchars($user['full_name'])?></div>
            </div>

            <div class="info-item">
                <div class="info-label">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    Phone Number
                </div>
                <div class="info-value"><?=htmlspecialchars($user['phone_number'])?></div>
            </div>

            <div class="info-item">
                <div class="info-label">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Email Address
                </div>
                <div class="info-value"><?=htmlspecialchars($user['email'])?></div>
            </div>

            <div class="info-item">
                <div class="info-label">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    City
                </div>
                <div class="info-value"><?=htmlspecialchars($user['city'])?></div>
            </div>

            <div class="info-item">
                <div class="info-label">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Account Status
                </div>
                <div class="info-value">
                    <span class="status-badge <?=strtolower($user['status'])?>">
                        <?=htmlspecialchars($user['status'])?>
                    </span>
                </div>
            </div>
        </div>

<?php if($user['tax_clearance_proof']): ?>
        <div class="document-section">
            <div class="document-title">Tax Clearance Documentation</div>
            <a href="/<?=htmlspecialchars($user['tax_clearance_proof'])?>" target="_blank" class="document-link">
                View Tax Clearance Proof
            </a>
        </div>
        <?php else: ?>
        <div class="document-section">
            <div class="document-title">Tax Clearance Documentation</div>
            <p class="no-document">No tax clearance proof uploaded</p>
        </div>
<?php endif; ?>

        <div class="actions-section">
            <a href="affiliates.php" class="btn">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to List
            </a>
        </div>
    </div>
</div>

</body>
</html>
