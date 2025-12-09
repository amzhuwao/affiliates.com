<?php
// admin/program_settings.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$msg = '';
$errors = [];

$programs = [
    'GS' => 'GetSolar',
    'TV' => 'TechVouch'
];

// Load existing values
$settings = [];
$stmt = $db->prepare("SELECT program, whatsapp_number FROM program_settings WHERE program IN ('GS','TV')");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['program']] = $row['whatsapp_number'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($programs as $code => $label) {
        $field = 'wa_' . $code;
        $value = trim($_POST[$field] ?? '');

        // Basic sanitization: allow digits, plus, hyphen and spaces
        $value = preg_replace('/[^0-9+\-\s]/', '', $value);

        // check if record exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM program_settings WHERE program = :program");
        $stmt->execute([':program' => $code]);
        $exists = (int)$stmt->fetchColumn() > 0;

        if ($exists) {
            $u = $db->prepare("UPDATE program_settings SET whatsapp_number = :wa WHERE program = :program");
            $u->execute([':wa' => $value, ':program' => $code]);
        } else {
            $i = $db->prepare("INSERT INTO program_settings (program, whatsapp_number) VALUES (:program, :wa)");
            $i->execute([':program' => $code, ':wa' => $value]);
        }
    }

    $msg = 'Program WhatsApp numbers updated.';
    // reload
    $stmt = $db->prepare("SELECT program, whatsapp_number FROM program_settings WHERE program IN ('GS','TV')");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['program']] = $row['whatsapp_number'];
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Settings - Admin</title>
    <link rel="icon" type="image/png" href="../branding/anako favicon.png">
    <link rel="stylesheet" href="../css/affiliates.css">
    <style>
        .settings-card {
            max-width: 700px;
            margin: 24px auto;
            padding: 24px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .form-row {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }

        .form-row label {
            width: 160px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .form-row input {
            flex: 1;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .btn-row {
            margin-top: 12px;
            display: flex;
            gap: 12px;
        }
    </style>
</head>

<body>

    <div class="affiliates-container">
        <div class="settings-card">
            <!-- Logo and Back Link -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <img src="../branding/ANAKO LOGO.png" alt="ANAKO Logo" style="height: 40px; width: auto;">
                <a href="../dashboard.php" class="back-link">&larr; Back</a>
            </div>
            <h2 style="margin-top:8px;">Program Settings</h2>
            <p style="color:var(--text-secondary);">Update WhatsApp numbers used for program referral links.</p>

            <?php if ($msg): ?>
                <div class="message success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <form method="post">
                <?php
                foreach ($programs as $code => $label) {
                    $attrId = 'wa_' . $code;
                    $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
                    $valueEsc = htmlspecialchars($settings[$code] ?? '', ENT_QUOTES, 'UTF-8');
                    echo '<div class="form-row">';
                    echo '<label for="' . $attrId . '">' . $labelEsc . '</label>';
                    echo '<input id="' . $attrId . '" name="' . $attrId . '" value="' . $valueEsc . '" placeholder="e.g. 263771234567" />';
                    echo '</div>';
                }
                ?>

                <div class="btn-row">
                    <button class="btn" type="submit">Save</button>
                    <a class="btn btn-secondary" href="../dashboard.php">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>