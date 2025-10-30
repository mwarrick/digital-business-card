<?php
/**
 * Admin Tool: Resend Verification Email
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/EmailTemplates.php';
require_once __DIR__ . '/../../api/includes/GmailClient.php';

AdminAuth::requireAuth();

$db = Database::getInstance();
$message = '';
$error = '';
$prefillEmail = '';
if (isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    $prefillEmail = $_GET['email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $user = $db->querySingle("SELECT id, email, is_active FROM users WHERE email = ?", [$email]);
            if (!$user) {
                $error = 'No user found with that email.';
            } else if ($user['is_active']) {
                $error = 'User is already active.';
            } else {
                // Create new verification code, 24h expiry
                $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $verificationId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $db->execute(
                    "INSERT INTO verification_codes (id, user_id, code, type, expires_at)
                     VALUES (?, ?, ?, 'register', DATE_ADD(NOW(), INTERVAL 1 DAY))",
                    [$verificationId, $user['id'], $code]
                );

                $emailData = EmailTemplates::registrationVerification($code, $email);
                GmailClient::sendEmail($email, $emailData['subject'], $emailData['html'], $emailData['text']);

                $message = 'Verification email resent to ' . htmlspecialchars($email) . ' (expires in 24 hours).';
            }
        } catch (Exception $e) {
            $error = 'Failed to resend: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification Email - Admin</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="container">
        <h1>Resend Verification Email</h1>
        <?php if ($message): ?>
            <div class="alert success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" style="max-width:480px; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
            <label for="email">User Email</label>
            <input type="email" id="email" name="email" placeholder="user@example.com" value="<?php echo htmlspecialchars($prefillEmail); ?>" required style="width:100%; padding:10px; border:1px solid #e1e1e1; border-radius:6px; margin:8px 0 16px;">
            <button type="submit" class="btn">Resend Verification</button>
        </form>
    </div>
</body>
</html>


