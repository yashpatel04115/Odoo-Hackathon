<?php
require_once 'config/database.php';

// If not coming from forgot password flow, redirect
if (empty($_SESSION['otp_user_id'])) {
    header('Location: forgot_password.php'); exit;
}

$error   = '';
$success = '';
if (!empty($_SESSION['dev_otp'])) {
    $success = "Email not configured. Your OTP is: <strong style='letter-spacing:6px;font-size:1.2rem;'>".$_SESSION['dev_otp']."</strong>";
    unset($_SESSION['dev_otp']);
}
$user_id = $_SESSION['otp_user_id'];
$email   = $_SESSION['otp_email'] ?? '';

function maskEmail($email) {
    if (!$email) return 'your email';
    [$l, $d] = explode('@', $email);
    return substr($l, 0, 2) . str_repeat('*', max(strlen($l)-2, 2)) . '@' . $d;
}

// Verify OTP
if ($_POST && isset($_POST['do_otp'])) {
    $entered = trim($_POST['otp_code']);

    if (strlen($entered) !== 6 || !ctype_digit($entered)) {
        $error = 'Please enter all 6 digits.';
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM otp_codes
            WHERE user_id = ?
              AND otp_code = ?
              AND used = 0
              AND expires_at > NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$user_id, $entered]);
        $row = $stmt->fetch();

        if ($row) {
            $pdo->prepare("UPDATE otp_codes SET used = 1 WHERE id = ?")->execute([$row['id']]);
            $_SESSION['reset_user_id'] = $user_id;
            unset($_SESSION['otp_user_id'], $_SESSION['otp_email'], $_SESSION['otp_purpose']);
            header('Location: reset_password.php'); exit;
        } else {
            $error = 'Invalid or expired OTP. Please check and try again.';
        }
    }
}

// Resend OTP
if ($_POST && isset($_POST['resend_otp'])) {
    require_once 'config/mail.php';
    $u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $u->execute([$user_id]);
    $user = $u->fetch();

    $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + 600);

    $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ?")->execute([$user_id]);
    $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, expires_at) VALUES (?, ?, ?)")
        ->execute([$user_id, $otp, $expires]);

    $sent = !empty($user['email']) ? sendOTPEmail($user['email'], $user['username'], $otp, 'forgot_password') : false;
    $success = $sent
        ? "New OTP sent to " . maskEmail($email)
        : "Email not configured. Your OTP is: <strong style='letter-spacing:6px;font-size:1.2rem;'>$otp</strong>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DyeStock — Verify OTP</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif; background:#0d1117; min-height:100vh;
            display:flex; flex-direction:column; align-items:center; justify-content:center; padding:30px 16px;
            background-image: radial-gradient(ellipse at 20% 50%, rgba(59,130,246,0.12) 0%, transparent 60%),
                              radial-gradient(ellipse at 80% 20%, rgba(139,92,246,0.10) 0%, transparent 50%);
        }
        .top-logo { display:flex; align-items:center; gap:12px; margin-bottom:28px; }
        .top-logo .icon-box { width:44px; height:44px; background:linear-gradient(135deg,#2563eb,#3b82f6); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; box-shadow:0 4px 14px rgba(59,130,246,0.4); }
        .top-logo span { font-size:1.5rem; font-weight:800; color:#fff; letter-spacing:-0.5px; }
        .card { background:rgba(22,28,45,0.95); border:1px solid rgba(255,255,255,0.08); border-radius:20px; padding:36px 40px; width:100%; max-width:440px; box-shadow:0 24px 60px rgba(0,0,0,0.5); }
        .card h2 { font-size:1.7rem; font-weight:800; color:#fff; margin-bottom:6px; letter-spacing:-0.5px; }
        .card .sub { color:#6b7280; font-size:0.92rem; margin-bottom:24px; }
        .email-badge { background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.2); border-radius:8px; padding:10px 14px; text-align:center; margin-bottom:22px; font-size:0.88rem; color:#93c5fd; }
        .alert { padding:12px 14px; border-radius:10px; margin-bottom:20px; font-size:0.88rem; display:flex; align-items:flex-start; gap:8px; font-weight:500; }
        .alert.error   { background:rgba(239,68,68,0.1);  color:#f87171; border:1px solid rgba(239,68,68,0.2); }
        .alert.success { background:rgba(16,185,129,0.1); color:#34d399; border:1px solid rgba(16,185,129,0.2); }

        /* OTP boxes */
        .otp-wrap { display:flex; gap:10px; justify-content:center; margin:20px 0; }
        .otp-wrap input {
            width:54px; height:62px; text-align:center; font-size:1.6rem; font-weight:800;
            background:rgba(255,255,255,0.05); border:1.5px solid rgba(255,255,255,0.12);
            border-radius:10px; color:#e5e7eb; padding:0; font-family:'Inter',sans-serif; transition:all 0.2s;
        }
        .otp-wrap input:focus { outline:none; border-color:#3b82f6; background:rgba(59,130,246,0.1); box-shadow:0 0 0 3px rgba(59,130,246,0.15); }
        .otp-wrap input.filled { border-color:#3b82f6; background:rgba(59,130,246,0.12); color:#93c5fd; }
        .otp-timer { text-align:center; color:#6b7280; font-size:0.85rem; margin-bottom:18px; }
        .otp-timer b { color:#ef4444; }
        .btn-verify { width:100%; padding:14px; background:linear-gradient(135deg,#2563eb,#3b82f6); color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; font-family:'Inter',sans-serif; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s; box-shadow:0 4px 16px rgba(59,130,246,0.35); }
        .btn-verify:hover:not(:disabled) { transform:translateY(-1px); }
        .btn-verify:disabled { opacity:0.5; cursor:not-allowed; }
        .links { text-align:center; margin-top:18px; font-size:0.85rem; color:#6b7280; }
        .links a { color:#3b82f6; text-decoration:none; font-weight:500; }
        .resend-btn { background:none; border:none; color:#3b82f6; font-size:0.85rem; font-weight:600; cursor:pointer; font-family:'Inter',sans-serif; padding:0; }
        .resend-btn:hover { color:#60a5fa; }
    </style>
</head>
<body>
    <div class="top-logo">
        <div class="icon-box"><i class="fas fa-palette" style="color:#fff;"></i></div>
        <span>DyeStock</span>
    </div>

    <div class="card">
        <h2>Verify OTP</h2>
        <p class="sub">Enter the 6-digit code sent to your email</p>

        <div class="email-badge">
            <i class="fas fa-envelope"></i>
            OTP sent to <strong><?php echo maskEmail($email); ?></strong>
        </div>

        <?php if ($error):   ?><div class="alert error">  <i class="fas fa-exclamation-circle"></i><span><?php echo $error;   ?></span></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><i class="fas fa-check-circle"></i><span><?php echo $success; ?></span></div><?php endif; ?>

        <form method="POST" id="otpForm">
            <div class="otp-wrap">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" autocomplete="off">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" autocomplete="off">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" autocomplete="off">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" autocomplete="off">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" autocomplete="off">
                <input type="text" maxlength="1" class="otp-box" inputmode="numeric" autocomplete="off">
            </div>
            <input type="hidden" name="otp_code" id="otpVal">
            <div class="otp-timer">Expires in <b id="timer">10:00</b></div>
            <button type="submit" name="do_otp" id="verifyBtn" class="btn-verify" disabled>
                <i class="fas fa-check-circle"></i> Verify OTP
            </button>
        </form>

        <div class="links" style="margin-top:16px;">
            Didn't receive it?
            <form method="POST" style="display:inline;">
                <button type="submit" name="resend_otp" class="resend-btn">Resend OTP</button>
            </form>
        </div>
        <div class="links" style="margin-top:10px;">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Sign In</a>
        </div>
    </div>

<script>
const boxes = document.querySelectorAll('.otp-box');
const valFld = document.getElementById('otpVal');
const btn    = document.getElementById('verifyBtn');

boxes.forEach((b, i) => {
    b.addEventListener('input', () => {
        b.value = b.value.replace(/\D/g, '');
        b.classList.toggle('filled', b.value !== '');
        if (b.value && i < 5) boxes[i+1].focus();
        sync();
    });
    b.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !b.value && i > 0) {
            boxes[i-1].value = '';
            boxes[i-1].classList.remove('filled');
            boxes[i-1].focus();
            sync();
        }
    });
    b.addEventListener('paste', e => {
        e.preventDefault();
        const txt = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        txt.split('').forEach((c,j) => { if(boxes[j]){ boxes[j].value=c; boxes[j].classList.add('filled'); } });
        sync();
    });
});

function sync() {
    const v = Array.from(boxes).map(b => b.value).join('');
    valFld.value = v;
    btn.disabled = v.length < 6;
    btn.style.background = v.length === 6 ? 'linear-gradient(135deg,#059669,#10b981)' : '';
    btn.style.boxShadow  = v.length === 6 ? '0 4px 16px rgba(16,185,129,0.35)' : '';
}

let s = 600;
const el = document.getElementById('timer');
const tick = setInterval(() => {
    if (--s <= 0) { clearInterval(tick); el.textContent = 'Expired'; btn.disabled = true; return; }
    el.textContent = String(Math.floor(s/60)).padStart(2,'0') + ':' + String(s%60).padStart(2,'0');
    if (s <= 60) el.style.color = '#ef4444';
}, 1000);

boxes[0].focus();
</script>
</body>
</html>