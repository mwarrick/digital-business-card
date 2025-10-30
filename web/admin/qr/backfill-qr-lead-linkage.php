<?php
require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

AdminAuth::requireAdmin();

$db = Database::getInstance();
$messages = [];

function columnExists($db, $table, $column) {
    try {
        $res = $db->querySingle("SHOW COLUMNS FROM `$table` LIKE ?", [$column]);
        return !empty($res);
    } catch (Exception $e) {
        return false;
    }
}

function tableExists($db, $table) {
    try {
        $res = $db->querySingle("SHOW TABLES LIKE ?", [$table]);
        return !empty($res);
    } catch (Exception $e) {
        return false;
    }
}

$hasQrLeads = tableExists($db, 'qr_leads');
$hasLeadsQrId = columnExists($db, 'leads', 'qr_id');
$hasLeadsIdCustomQr = columnExists($db, 'leads', 'id_custom_qr_code');

// Compute pending counts
$pendingQrId = 0;
$pendingIdCustom = 0;
if ($hasQrLeads) {
    if ($hasLeadsQrId) {
        $row = $db->querySingle("SELECT COUNT(*) AS c FROM qr_leads ql JOIN leads l ON l.id = ql.lead_id WHERE l.qr_id IS NULL");
        $pendingQrId = (int)($row['c'] ?? 0);
    }
    if ($hasLeadsIdCustomQr) {
        $row = $db->querySingle("SELECT COUNT(*) AS c FROM qr_leads ql JOIN leads l ON l.id = ql.lead_id WHERE l.id_custom_qr_code IS NULL");
        $pendingIdCustom = (int)($row['c'] ?? 0);
    }
}

// Handle actions
if (isset($_POST['action']) && $hasQrLeads) {
    if ($_POST['action'] === 'backfill_qr_id' && $hasLeadsQrId) {
        try {
            $sql = "UPDATE leads l JOIN qr_leads ql ON ql.lead_id = l.id SET l.qr_id = ql.qr_id WHERE l.qr_id IS NULL";
            $db->execute($sql);
            $messages[] = 'Backfill complete for leads.qr_id.';
        } catch (Exception $e) {
            $messages[] = 'Error backfilling leads.qr_id: ' . htmlspecialchars($e->getMessage());
        }
    } elseif ($_POST['action'] === 'backfill_id_custom_qr' && $hasLeadsIdCustomQr) {
        try {
            $sql = "UPDATE leads l JOIN qr_leads ql ON ql.lead_id = l.id SET l.id_custom_qr_code = ql.qr_id WHERE l.id_custom_qr_code IS NULL";
            $db->execute($sql);
            $messages[] = 'Backfill complete for leads.id_custom_qr_code.';
        } catch (Exception $e) {
            $messages[] = 'Error backfilling leads.id_custom_qr_code: ' . htmlspecialchars($e->getMessage());
        }
    }
    // Recompute counts after operation
    if ($hasLeadsQrId) {
        $row = $db->querySingle("SELECT COUNT(*) AS c FROM qr_leads ql JOIN leads l ON l.id = ql.lead_id WHERE l.qr_id IS NULL");
        $pendingQrId = (int)($row['c'] ?? 0);
    }
    if ($hasLeadsIdCustomQr) {
        $row = $db->querySingle("SELECT COUNT(*) AS c FROM qr_leads ql JOIN leads l ON l.id = ql.lead_id WHERE l.id_custom_qr_code IS NULL");
        $pendingIdCustom = (int)($row['c'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backfill QR Lead Linkage</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
    <style>
        .container { max-width: 900px; margin: 30px auto; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        h1 { margin: 0 0 16px 0; }
        .card { background: #f8f9fa; border-radius: 10px; padding: 16px; margin: 16px 0; border-left: 4px solid #007bff; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; }
        .btn { display: inline-block; background: #007bff; color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; }
        .btn.secondary { background: #6c757d; }
        .muted { color: #666; }
        .msg { background: #e8f5e9; color: #2e7d32; padding: 10px 14px; border-radius: 6px; margin-bottom: 10px; border-left: 4px solid #28a745; }
        .warn { background: #fff3cd; color: #856404; padding: 10px 14px; border-radius: 6px; margin-bottom: 10px; border-left: 4px solid #ffc107; }
        .error { background: #fdecea; color: #a71d2a; padding: 10px 14px; border-radius: 6px; margin-bottom: 10px; border-left: 4px solid #dc3545; }
    </style>
    <script>
        function confirmSubmit(formId, text) {
            if (confirm(text)) { document.getElementById(formId).submit(); }
        }
    </script>
    </head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <h1>Backfill QR Lead Linkage</h1>
    <p class="muted">Populate missing linkage in <code>leads</code> from <code>qr_leads</code> so user leads pages resolve QR titles/types.</p>

    <?php foreach ($messages as $m): ?>
        <div class="msg"><?= $m ?></div>
    <?php endforeach; ?>

    <?php if (!$hasQrLeads): ?>
        <div class="warn">Mapping table <code>qr_leads</code> not found. Nothing to backfill.</div>
    <?php endif; ?>

    <div class="card">
        <h3>leads.qr_id</h3>
        <p>Pending rows to backfill: <strong><?= $pendingQrId ?></strong></p>
        <?php if ($hasLeadsQrId && $hasQrLeads): ?>
            <form id="f-qrid" method="post">
                <input type="hidden" name="action" value="backfill_qr_id">
                <div class="actions">
                    <button type="button" class="btn" onclick="confirmSubmit('f-qrid','Backfill leads.qr_id from qr_leads?')">Backfill qr_id</button>
                </div>
            </form>
        <?php else: ?>
            <p class="muted">leads.qr_id not present or mapping table missing.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>leads.id_custom_qr_code</h3>
        <p>Pending rows to backfill: <strong><?= $pendingIdCustom ?></strong></p>
        <?php if ($hasLeadsIdCustomQr && $hasQrLeads): ?>
            <form id="f-idcqr" method="post">
                <input type="hidden" name="action" value="backfill_id_custom_qr">
                <div class="actions">
                    <button type="button" class="btn" onclick="confirmSubmit('f-idcqr','Backfill leads.id_custom_qr_code from qr_leads?')">Backfill id_custom_qr_code</button>
                </div>
            </form>
        <?php else: ?>
            <p class="muted">leads.id_custom_qr_code not present or mapping table missing.</p>
        <?php endif; ?>
    </div>

    <p class="muted">This operation is idempotent and only fills NULL values.</p>
</div>
</body>
</html>


