<?php
require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

AdminAuth::requireAdmin();
$db = Database::getInstance();

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$diagnostics = [
    'hasQrLeads' => null,
    'hasLeadsQrId' => null,
    'hasLeadsIdCustomQr' => null,
    'userFound' => null,
    'qrCount' => 0,
];
$messages = [];
$created = [];

function columnExists($db, $table, $column) {
    try { $res = $db->querySingle("SHOW COLUMNS FROM `$table` LIKE ?", [$column]); return !empty($res); } catch (Exception $e) { return false; }
}
function tableExists($db, $table) {
    try { $res = $db->querySingle("SHOW TABLES LIKE ?", [$table]); return !empty($res); } catch (Exception $e) { return false; }
}

$hasQrLeads = tableExists($db, 'qr_leads');
$hasLeadsQrId = columnExists($db, 'leads', 'qr_id');
$hasLeadsIdCustomQr = columnExists($db, 'leads', 'id_custom_qr_code');
$diagnostics['hasQrLeads'] = $hasQrLeads;
$diagnostics['hasLeadsQrId'] = $hasLeadsQrId;
$diagnostics['hasLeadsIdCustomQr'] = $hasLeadsIdCustomQr;

if ($email !== '') {
    $user = $db->querySingle("SELECT id, email FROM users WHERE email = ?", [$email]);
    if (!$user) {
        $messages[] = 'User not found: ' . htmlspecialchars($email);
        $diagnostics['userFound'] = false;
    } else {
        $userId = $user['id'];
        $qrs = $db->query("SELECT id, title, type FROM custom_qr_codes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$userId]);
        $diagnostics['userFound'] = true;
        $diagnostics['qrCount'] = is_array($qrs) ? count($qrs) : 0;
        if (empty($qrs)) {
            $messages[] = 'No custom QR codes found for ' . htmlspecialchars($email);
        } else if (!$hasQrLeads && !$hasLeadsQrId && !$hasLeadsIdCustomQr) {
            $messages[] = 'No linkage path available (qr_leads table and leads.qr_id/id_custom_qr_code columns missing).';
        } else {
            $samples = [
                ['first_name' => 'Alice', 'last_name' => 'Anderson', 'organization_name' => 'Anderson Co', 'job_title' => 'Marketing Manager'],
                ['first_name' => 'Bob', 'last_name' => 'Baker', 'organization_name' => 'Baker LLC', 'job_title' => 'Sales Lead'],
                ['first_name' => 'Carol', 'last_name' => 'Chen', 'organization_name' => 'Chen Group', 'job_title' => 'CTO'],
                ['first_name' => 'Dan', 'last_name' => 'Diaz', 'organization_name' => 'Diaz Media', 'job_title' => 'Producer'],
                ['first_name' => 'Eve', 'last_name' => 'Ellis', 'organization_name' => 'Ellis Ventures', 'job_title' => 'Founder'],
            ];
            $idx = 0;
            foreach ($qrs as $qr) {
                $sample = $samples[$idx % count($samples)];
                $idx++;

                // Insert lead
                $db->execute(
                    "INSERT INTO leads (id_user, first_name, last_name, organization_name, job_title, email_primary, created_at) VALUES (?,?,?,?,?,?,NOW())",
                    [$userId, $sample['first_name'], $sample['last_name'], $sample['organization_name'], $sample['job_title'], strtolower($sample['first_name']) . '@example.com']
                );
                $leadId = $db->lastInsertId();

                // Link via qr_leads
                if ($hasQrLeads) {
                    $db->execute("INSERT INTO qr_leads (qr_id, lead_id, created_at) VALUES (?,?,NOW())", [$qr['id'], $leadId]);
                }

                // Populate direct columns if present
                if ($hasLeadsQrId) {
                    $db->execute("UPDATE leads SET qr_id = ? WHERE id = ?", [$qr['id'], $leadId]);
                }
                if ($hasLeadsIdCustomQr) {
                    $db->execute("UPDATE leads SET id_custom_qr_code = ? WHERE id = ?", [$qr['id'], $leadId]);
                }

                $created[] = [
                    'lead_id' => $leadId,
                    'qr_id' => $qr['id'],
                    'qr_title' => $qr['title'],
                    'qr_type' => $qr['type'],
                ];
            }
            $messages[] = 'Created ' . count($created) . ' sample leads.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Sample QR Leads</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
    <style>
        .container { max-width: 900px; margin: 30px auto; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .row { margin: 12px 0; }
        .input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 100%; max-width: 420px; }
        .btn { display: inline-block; background: #007bff; color: #fff; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; }
        .msg { background: #e8f5e9; color: #2e7d32; padding: 10px 14px; border-radius: 6px; margin-bottom: 10px; border-left: 4px solid #28a745; }
        .error { background: #fdecea; color: #a71d2a; padding: 10px 14px; border-radius: 6px; margin-bottom: 10px; border-left: 4px solid #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #eee; padding: 8px 10px; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <h1>Generate Sample QR Leads</h1>
    <form method="get" class="row">
        <label>Email:&nbsp;</label>
        <input class="input" type="email" name="email" value="<?= htmlspecialchars($email ?: 'mark@sharemycard.app') ?>" placeholder="user@example.com" required>
        &nbsp;<button class="btn" type="submit">Generate</button>
    </form>

    <?php foreach ($messages as $m): ?>
        <div class="msg"><?= $m ?></div>
    <?php endforeach; ?>

    <div class="row">
        <strong>Diagnostics</strong>
        <div class="row" style="margin-top:8px;">
            <code>qr_leads:</code> <?= $diagnostics['hasQrLeads'] ? 'yes' : 'no' ?> &nbsp;|
            <code>leads.qr_id:</code> <?= $diagnostics['hasLeadsQrId'] ? 'yes' : 'no' ?> &nbsp;|
            <code>leads.id_custom_qr_code:</code> <?= $diagnostics['hasLeadsIdCustomQr'] ? 'yes' : 'no' ?> &nbsp;|
            <code>userFound:</code> <?= $diagnostics['userFound'] === null ? 'n/a' : ($diagnostics['userFound'] ? 'yes' : 'no') ?> &nbsp;|
            <code>custom_qr_codes count:</code> <?= (int)$diagnostics['qrCount'] ?>
        </div>
    </div>

    <?php if (!empty($created)): ?>
        <table>
            <thead>
                <tr>
                    <th>Lead ID</th>
                    <th>QR ID</th>
                    <th>QR Title</th>
                    <th>QR Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($created as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$row['lead_id']) ?></td>
                        <td><?= htmlspecialchars($row['qr_id']) ?></td>
                        <td><?= htmlspecialchars($row['qr_title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['qr_type'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>


