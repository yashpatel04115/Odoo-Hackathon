<?php
require_once 'config/database.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$success = $error = '';
if ($_POST) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = in_array($_POST['role'], ['manager','staff']) ? $_POST['role'] : 'staff';
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $is_first = ($role === 'staff') ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, is_first_login) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role, $is_first]);
            $success = 'Account created! You can now login.';
        } catch (Exception $e) {
            $error = 'Username or email already exists.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DyeStock — Create Account</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Inter',sans-serif;
            background:#0d1117; min-height:100vh;
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            padding:30px 16px;
            background-image: radial-gradient(ellipse at 20% 50%, rgba(59,130,246,0.12) 0%, transparent 60%),
                              radial-gradient(ellipse at 80% 20%, rgba(139,92,246,0.10) 0%, transparent 50%);
        }
        .top-logo { display:flex; align-items:center; gap:12px; margin-bottom:28px; }
        .top-logo .icon-box {
            width:44px; height:44px; background:linear-gradient(135deg,#2563eb,#3b82f6);
            border-radius:12px; display:flex; align-items:center; justify-content:center;
            font-size:1.2rem; box-shadow:0 4px 14px rgba(59,130,246,0.4);
        }
        .top-logo span { font-size:1.5rem; font-weight:800; color:#fff; letter-spacing:-0.5px; }

        .card {
            background:rgba(22,28,45,0.95); border:1px solid rgba(255,255,255,0.08);
            border-radius:20px; padding:36px 40px; width:100%; max-width:480px;
            box-shadow:0 24px 60px rgba(0,0,0,0.5); backdrop-filter:blur(12px);
        }
        .card h2 { font-size:1.7rem; font-weight:800; color:#fff; margin-bottom:6px; letter-spacing:-0.5px; }
        .card .sub { color:#6b7280; font-size:0.92rem; margin-bottom:28px; }

        .field-label { display:block; font-size:0.72rem; font-weight:700; color:#9ca3af; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:8px; }
        .input-wrap { position:relative; margin-bottom:20px; }
        .input-wrap i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#4b5563; font-size:0.88rem; pointer-events:none; }
        .input-wrap input {
            width:100%; padding:13px 14px 13px 40px;
            background:rgba(255,255,255,0.05); border:1.5px solid rgba(255,255,255,0.1);
            border-radius:10px; color:#e5e7eb; font-size:0.95rem; font-family:'Inter',sans-serif; transition:all 0.2s;
        }
        .input-wrap input::placeholder { color:#4b5563; }
        .input-wrap input:focus { outline:none; border-color:#3b82f6; background:rgba(59,130,246,0.08); box-shadow:0 0 0 3px rgba(59,130,246,0.15); }

        /* ROLE CARDS */
        .role-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:24px; }
        .role-card {
            border:1.5px solid rgba(255,255,255,0.1); border-radius:12px;
            padding:16px 14px; cursor:pointer; transition:all 0.2s;
            background:rgba(255,255,255,0.03); display:block;
        }
        .role-card:hover { border-color:rgba(59,130,246,0.4); background:rgba(59,130,246,0.05); }
        .role-card.selected { border-color:#3b82f6; background:rgba(59,130,246,0.1); }
        .role-card input[type=radio] { display:none; }
        .role-card .rc-icon { font-size:1.6rem; margin-bottom:8px; display:block; }
        .role-card .rc-title { font-weight:700; font-size:0.9rem; color:#e5e7eb; margin-bottom:4px; }
        .role-card .rc-desc  { font-size:0.75rem; color:#6b7280; line-height:1.4; }
        .role-card.selected .rc-title { color:#93c5fd; }

        .btn-submit {
            width:100%; padding:14px; background:linear-gradient(135deg,#2563eb,#3b82f6);
            color:#fff; border:none; border-radius:10px; font-size:1rem; font-weight:700;
            cursor:pointer; font-family:'Inter',sans-serif;
            display:flex; align-items:center; justify-content:center; gap:8px;
            transition:all 0.2s; box-shadow:0 4px 16px rgba(59,130,246,0.35); margin-top:4px;
        }
        .btn-submit:hover { transform:translateY(-1px); box-shadow:0 8px 24px rgba(59,130,246,0.45); }

        .alert { padding:12px 14px; border-radius:10px; margin-bottom:20px; font-size:0.88rem; display:flex; align-items:center; gap:8px; font-weight:500; }
        .alert.error   { background:rgba(239,68,68,0.1);  color:#f87171; border:1px solid rgba(239,68,68,0.2); }
        .alert.success { background:rgba(16,185,129,0.1); color:#34d399; border:1px solid rgba(16,185,129,0.2); }

        .links { text-align:center; margin-top:20px; font-size:0.88rem; color:#6b7280; }
        .links a { color:#3b82f6; text-decoration:none; font-weight:500; }
        .links a:hover { color:#60a5fa; }
    </style>
</head>
<body>

    <div class="top-logo">
        <div class="icon-box"><i class="fas fa-palette" style="color:#fff;"></i></div>
        <span>DyeStock</span>
    </div>

    <div class="card">
        <h2>Create account</h2>
        <p class="sub">Join your inventory management system</p>

        <?php if ($error):   ?><div class="alert error">  <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error);   ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><i class="fas fa-check-circle"></i>       <?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <form method="POST">
            <div>
                <label class="field-label">Username</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" required placeholder="Choose a username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
            </div>
            <div>
                <label class="field-label">Email</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" required placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
            <div>
                <label class="field-label">Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" required placeholder="Min 6 characters">
                </div>
            </div>

            <div>
                <label class="field-label">Role</label>
                <div class="role-grid">
                    <label class="role-card <?php echo (($_POST['role']??'staff')==='manager')?'selected':''; ?>" id="card-manager">
                        <input type="radio" name="role" value="manager" <?php echo (($_POST['role']??'')==='manager')?'checked':''; ?>>
                        <span class="rc-icon">👔</span>
                        <div class="rc-title">Inventory Manager</div>
                        <div class="rc-desc">Full access — products, receipts, deliveries, adjustments & reports</div>
                    </label>
                    <label class="role-card <?php echo (($_POST['role']??'staff')==='staff')?'selected':''; ?>" id="card-staff">
                        <input type="radio" name="role" value="staff" <?php echo (($_POST['role']??'staff')==='staff')?'checked':''; ?>>
                        <span class="rc-icon">🏗️</span>
                        <div class="rc-title">Warehouse Staff</div>
                        <div class="rc-desc">Limited access — receive stock, dispatch, transfers & shelving</div>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="links">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>

<script>
document.querySelectorAll('.role-card input[type=radio]').forEach(r => {
    r.addEventListener('change', () => {
        document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
        r.closest('.role-card').classList.add('selected');
    });
});
</script>
</body>
</html>