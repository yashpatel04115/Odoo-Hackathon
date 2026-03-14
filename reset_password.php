<?php
require_once 'config/database.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
if (empty($_SESSION['reset_user_id'])) { header('Location: login.php'); exit; }

$error = $success = '';
$user_id = $_SESSION['reset_user_id'];

if ($_POST && isset($_POST['reset_pass'])) {
    $new  = $_POST['new_password'];
    $conf = $_POST['confirm_password'];
    if (strlen($new) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new !== $conf) {
        $error = 'Passwords do not match.';
    } else {
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $user_id]);
        unset($_SESSION['reset_user_id']);
        $success = 'Password reset successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DyeStock — Reset Password</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#0d1117; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:30px 16px; background-image:radial-gradient(ellipse at 20% 50%,rgba(59,130,246,0.12) 0%,transparent 60%),radial-gradient(ellipse at 80% 20%,rgba(139,92,246,0.10) 0%,transparent 50%); }
        .top-logo { display:flex; align-items:center; gap:12px; margin-bottom:28px; }
        .top-logo .icon-box { width:44px; height:44px; background:linear-gradient(135deg,#2563eb,#3b82f6); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; box-shadow:0 4px 14px rgba(59,130,246,0.4); }
        .top-logo span { font-size:1.5rem; font-weight:800; color:#fff; letter-spacing:-0.5px; }
        .card { background:rgba(22,28,45,0.95); border:1px solid rgba(255,255,255,0.08); border-radius:20px; padding:36px 40px; width:100%; max-width:440px; box-shadow:0 24px 60px rgba(0,0,0,0.5); }
        .card h2 { font-size:1.7rem; font-weight:800; color:#fff; margin-bottom:6px; letter-spacing:-0.5px; }
        .card .sub { color:#6b7280; font-size:0.92rem; margin-bottom:28px; }
        .field-label { display:block; font-size:0.72rem; font-weight:700; color:#9ca3af; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:8px; }
        .input-wrap { position:relative; margin-bottom:20px; }
        .input-wrap i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#4b5563; font-size:0.88rem; pointer-events:none; }
        .input-wrap input { width:100%; padding:13px 14px 13px 40px; background:rgba(255,255,255,0.05); border:1.5px solid rgba(255,255,255,0.1); border-radius:10px; color:#e5e7eb; font-size:0.95rem; font-family:'Inter',sans-serif; transition:all 0.2s; }
        .input-wrap input::placeholder { color:#4b5563; }
        .input-wrap input:focus { outline:none; border-color:#3b82f6; background:rgba(59,130,246,0.08); box-shadow:0 0 0 3px rgba(59,130,246,0.15); }
        .btn-submit { width:100%; padding:14px; background:linear-gradient(135deg,#2563eb,#3b82f6); color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; font-family:'Inter',sans-serif; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s; box-shadow:0 4px 16px rgba(59,130,246,0.35); }
        .btn-submit:hover { transform:translateY(-1px); }
        .alert { padding:12px 14px; border-radius:10px; margin-bottom:20px; font-size:0.88rem; display:flex; align-items:center; gap:8px; font-weight:500; }
        .alert.error   { background:rgba(239,68,68,0.1);  color:#f87171; border:1px solid rgba(239,68,68,0.2); }
        .alert.success { background:rgba(16,185,129,0.1); color:#34d399; border:1px solid rgba(16,185,129,0.2); }
        .btn-login { width:100%; padding:14px; background:linear-gradient(135deg,#059669,#10b981); color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; font-family:'Inter',sans-serif; display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none; margin-top:8px; box-shadow:0 4px 16px rgba(16,185,129,0.35); }
    </style>
</head>
<body>
    <div class="top-logo">
        <div class="icon-box"><i class="fas fa-palette" style="color:#fff;"></i></div>
        <span>DyeStock</span>
    </div>
    <div class="card">
        <h2>Reset password</h2>
        <p class="sub">Choose a new password for your account</p>
        <?php if ($error):   ?><div class="alert error">  <i class="fas fa-exclamation-circle"></i> <?php echo $error;   ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Go to Sign In</a>
        <?php else: ?>
        <form method="POST">
            <div>
                <label class="field-label">New Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="new_password" required autofocus placeholder="Min 6 characters">
                </div>
            </div>
            <div>
                <label class="field-label">Confirm Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" required placeholder="Re-enter new password">
                </div>
            </div>
            <button type="submit" name="reset_pass" class="btn-submit">
                <i class="fas fa-save"></i> Reset Password
            </button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>