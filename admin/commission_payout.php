<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$success = '';
$activeTab = $_GET['status'] ?? 'pending'; // Default to pending tab

// Handle payout form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $method = trim($_POST['payment_method']);
    $ref = trim($_POST['payment_ref']);
    $note = trim($_POST['admin_note']);

    $stmt = $db->prepare("UPDATE commissions 
        SET payment_status = 'paid',
            paid_at = NOW(),
            payment_method = :m,
            payment_ref = :r,
            admin_note = :note
        WHERE id = :id");
    $stmt->execute([
        ':m' => $method,
        ':r' => $ref,
        ':note' => $note,
        ':id' => $id
    ]);
    
    $success = "Commission #$id marked as paid successfully!";
    // Redirect to paid tab after successful payment
    header("Location: commission_payout.php?status=paid");
    exit;
}

// Fetch commissions based on active tab
$stmt = $db->prepare("SELECT c.*, a.full_name
    FROM commissions c
    JOIN affiliates a ON c.affiliate_id = a.id
    WHERE c.payment_status = :status
    ORDER BY c.created_at DESC");
$stmt->execute([':status' => $activeTab]);
$commissions = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Payouts - Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/commission_payout.css">
    <link rel="stylesheet" href="../css/premium_table.css">
</head>
<body>

<div class="payout-container">
    <div class="payout-card">
        <!-- Back Link -->
        <a href="../dashboard.php" class="back-link">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back to Dashboard
        </a>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Commission Payouts</h1>
            <p class="page-subtitle">Process pending commission payments to affiliates</p>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <a href="?status=pending" class="tab <?= $activeTab === 'pending' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Pending
            </a>
            <a href="?status=paid" class="tab <?= $activeTab === 'paid' ? 'active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Paid
            </a>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
        <div class="message success">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M16.25 5L7.5 13.75L3.75 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?=htmlspecialchars($success)?>
        </div>
        <?php endif; ?>

        <!-- Commissions Table -->
        <?php if (!empty($commissions)): ?>
        <div style="overflow-x: auto; margin: 0 -24px; padding: 0 24px;">
            <table style="width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 12px; overflow: hidden;">
                <thead>
                    <tr style="background: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">#</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Affiliate</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Gross</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Tax</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Net</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;"><?= $activeTab === 'paid' ? 'Paid Date' : 'Created' ?></th>
                        <?php if ($activeTab === 'pending'): ?>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Actions</th>
                        <?php else: ?>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Payment Method</th>
                        <th style="padding: 18px 16px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Reference</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commissions as $c): ?>
                    <tr style="background: var(--bg-secondary); border-bottom: 1px solid var(--border); transition: all 0.3s ease;">
                        <td data-label="#" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?=htmlspecialchars($c['id'])?></td>
                        <td data-label="Affiliate" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?=htmlspecialchars($c['full_name'])?></td>
                        <td data-label="Gross" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 600;">$<?=number_format($c['gross_commission'], 2)?></td>
                        <td data-label="Tax" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 600;">$<?=number_format($c['withholding_tax'], 2)?></td>
                        <td data-label="Net" style="padding: 20px 16px; font-size: 14px; color: var(--success); font-weight: 600;">$<?=number_format($c['net_commission'], 2)?></td>
                        <?php if ($activeTab === 'paid'): ?>
                        <td data-label="Paid Date" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?=htmlspecialchars(date('M d, Y', strtotime($c['paid_at'])))?></td>
                        <td data-label="Payment Method" style="padding: 20px 16px; font-size: 14px;">
                            <span style="display: inline-block; padding: 6px 12px; background: linear-gradient(135deg, rgba(201, 203, 216, 0.12) 0%, rgba(201, 203, 216, 0.05) 100%); border: 1px solid rgba(201, 203, 216, 0.2); border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--accent); text-transform: capitalize;"><?=htmlspecialchars($c['payment_method'] ?: 'N/A')?></span>
                        </td>
                        <td data-label="Reference" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?=htmlspecialchars($c['payment_ref'] ?: 'N/A')?></td>
                        <?php else: ?>
                        <td data-label="Created" style="padding: 20px 16px; font-size: 14px; color: var(--text-primary); font-weight: 500;"><?=htmlspecialchars(date('M d, Y', strtotime($c['created_at'])))?></td>
                        <td data-label="Actions" style="padding: 20px 16px;">
                            <div class="actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button 
                                    type="button" 
                                    class="action-link"
                                    style="padding: 6px 12px; font-size: 13px; font-weight: 600; color: var(--text-primary); background: var(--bg-card); border-radius: 6px; text-decoration: none; transition: all 0.2s ease; border: 1px solid var(--border); cursor: pointer; font-family: inherit;"
                                    data-id="<?=htmlspecialchars($c['id'])?>"
                                    data-affiliate="<?=htmlspecialchars($c['full_name'])?>"
                                    data-gross="<?=number_format($c['gross_commission'], 2)?>"
                                    data-tax="<?=number_format($c['withholding_tax'], 2)?>"
                                    data-net="<?=number_format($c['net_commission'], 2)?>"
                                    onclick="openPaymentModal(this)">
                                    View
                                </button>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon"><?= $activeTab === 'paid' ? '✅' : '💳' ?></div>
            <p class="empty-state-title"><?= $activeTab === 'paid' ? 'No Paid Commissions' : 'No Pending Commissions' ?></p>
            <p class="empty-state-text"><?= $activeTab === 'paid' ? 'No commissions have been paid yet.' : 'All commissions have been paid. Great work!' ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-content confirm-modal">
        <div class="modal-icon warning">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h2 class="modal-title" id="confirmTitle">Confirm Payment</h2>
        <p class="modal-message" id="confirmMessage"></p>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-cancel" id="confirmCancel">Cancel</button>
            <button class="modal-btn modal-btn-confirm" id="confirmProceed">Confirm Payment</button>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="paymentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Process Commission Payment</h2>
            <button type="button" class="modal-close" onclick="closePaymentModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="commission-details">
                <div class="detail-row">
                    <span class="detail-label">Commission ID:</span>
                    <span class="detail-value" id="modalCommissionId">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Affiliate:</span>
                    <span class="detail-value" id="modalAffiliate">-</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Gross Amount:</span>
                    <span class="detail-value money" id="modalGross">$0.00</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Withholding Tax:</span>
                    <span class="detail-value money" id="modalTax">$0.00</span>
                </div>
                <div class="detail-row highlight">
                    <span class="detail-label">Net Payment:</span>
                    <span class="detail-value money" id="modalNet">$0.00</span>
                </div>
            </div>
            
            <form method="post" id="paymentForm" class="payment-form">
                <input type="hidden" name="id" id="paymentCommissionId">
                
                <div class="form-group">
                    <label for="payment_method">Payment Method *</label>
                    <input 
                        type="text" 
                        name="payment_method" 
                        id="payment_method"
                        placeholder="e.g., Bank Transfer, Cash, EcoCash"
                        required>
                </div>
                
                <div class="form-group">
                    <label for="payment_ref">Payment Reference *</label>
                    <input 
                        type="text" 
                        name="payment_ref" 
                        id="payment_ref"
                        placeholder="Transaction or reference number"
                        required>
                </div>
                
                <div class="form-group">
                    <label for="admin_note">Admin Notes</label>
                    <textarea 
                        name="admin_note" 
                        id="admin_note"
                        placeholder="Optional: Add any additional notes about this payment"
                        rows="3"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closePaymentModal()">Cancel</button>
                    <button type="submit" class="modal-btn modal-btn-confirm">💰 Mark as Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add hover effect to table rows
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('tbody tr');
    
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.background = 'var(--bg-card)';
            this.style.transform = 'scale(1.01)';
            this.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
            this.style.zIndex = '1';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.background = 'var(--bg-secondary)';
            this.style.transform = 'scale(1)';
            this.style.boxShadow = 'none';
            this.style.zIndex = 'auto';
        });
    });
});

// Payment Modal Functions
function openPaymentModal(button) {
    const id = button.getAttribute('data-id');
    const affiliate = button.getAttribute('data-affiliate');
    const gross = button.getAttribute('data-gross');
    const tax = button.getAttribute('data-tax');
    const net = button.getAttribute('data-net');
    
    // Populate modal with commission data
    document.getElementById('modalCommissionId').textContent = id;
    document.getElementById('modalAffiliate').textContent = affiliate;
    document.getElementById('modalGross').textContent = '$' + gross;
    document.getElementById('modalTax').textContent = '$' + tax;
    document.getElementById('modalNet').textContent = '$' + net;
    document.getElementById('paymentCommissionId').value = id;
    
    // Reset form
    document.getElementById('paymentForm').reset();
    document.getElementById('paymentCommissionId').value = id; // Set again after reset
    
    // Show modal
    document.getElementById('paymentModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal on overlay click
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('confirmModal').classList.contains('active')) {
            confirmModal.cancel();
        } else if (document.getElementById('paymentModal').classList.contains('active')) {
            closePaymentModal();
        }
    }
});

// Premium Confirmation Modal
const confirmModal = {
    overlay: document.getElementById('confirmModal'),
    title: document.getElementById('confirmTitle'),
    message: document.getElementById('confirmMessage'),
    confirmBtn: document.getElementById('confirmProceed'),
    cancelBtn: document.getElementById('confirmCancel'),
    currentCallback: null,

    show: function(options) {
        const {
            title = 'Confirm Action',
            message = 'Are you sure you want to proceed?',
            confirmText = 'Confirm',
            onConfirm = () => {}
        } = options;

        this.title.textContent = title;
        this.message.innerHTML = message;
        this.confirmBtn.textContent = confirmText;
        this.currentCallback = onConfirm;

        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    hide: function() {
        this.overlay.classList.remove('active');
        document.body.style.overflow = '';
        this.currentCallback = null;
    },

    confirm: function() {
        if (this.currentCallback) {
            this.currentCallback();
        }
        this.hide();
    },

    cancel: function() {
        this.hide();
    }
};

// Event listeners for confirmation modal
confirmModal.confirmBtn.addEventListener('click', () => confirmModal.confirm());
confirmModal.cancelBtn.addEventListener('click', () => confirmModal.cancel());

// Close on overlay click
confirmModal.overlay.addEventListener('click', (e) => {
    if (e.target === confirmModal.overlay) {
        confirmModal.cancel();
    }
});

// Form submission with custom confirmation
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const affiliate = document.getElementById('modalAffiliate').textContent;
    const amount = document.getElementById('modalNet').textContent;
    const method = document.getElementById('payment_method').value;
    const ref = document.getElementById('payment_ref').value;
    
    const messageHtml = `
        <div style="text-align: left; margin-top: 16px; padding: 16px; background: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border);">
            <div style="margin-bottom: 12px;"><strong style="color: var(--text-secondary);">Affiliate:</strong> <span style="color: var(--text-primary);">${affiliate}</span></div>
            <div style="margin-bottom: 12px;"><strong style="color: var(--text-secondary);">Amount:</strong> <span style="color: var(--success); font-weight: 600;">${amount}</span></div>
            <div style="margin-bottom: 12px;"><strong style="color: var(--text-secondary);">Method:</strong> <span style="color: var(--text-primary);">${method}</span></div>
            <div><strong style="color: var(--text-secondary);">Reference:</strong> <span style="color: var(--text-primary);">${ref}</span></div>
        </div>
        <p style="margin-top: 16px; color: var(--text-secondary); font-size: 14px;">This will mark the commission as paid and cannot be easily undone.</p>
    `;
    
    const form = this;
    
    confirmModal.show({
        title: 'Confirm Commission Payment',
        message: messageHtml,
        confirmText: '💰 Mark as Paid',
        onConfirm: () => {
            form.submit();
        }
    });
});
</script>

</body>
</html>
