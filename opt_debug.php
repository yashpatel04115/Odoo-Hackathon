<?php
require_once 'config/database.php';

echo "<h2>SESSION DATA</h2><pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>OTP_CODES TABLE (last 5 rows)</h2>";
try {
    $rows = $pdo->query("SELECT * FROM otp_codes ORDER BY id DESC LIMIT 5")->fetchAll();
    echo "<pre>"; print_r($rows); echo "</pre>";
} catch (Exception $e) {
    echo "<b style='color:red'>ERROR: " . $e->getMessage() . "</b><br>";
    echo "The otp_codes table may not exist!";
}

echo "<h2>USERS TABLE (id, username, email, role)</h2>";
$users = $pdo->query("SELECT id, username, email, role FROM users")->fetchAll();
echo "<pre>"; print_r($users); echo "</pre>";

echo "<h2>TEST OTP INSERT</h2>";
try {
    $test_user_id = 1;
    $test_otp = '123456';
    $expires = date('Y-m-d H:i:s', time() + 600);
    $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ?")->execute([$test_user_id]);
    $pdo->prepare("INSERT INTO otp_codes (user_id, otp_code, expires_at) VALUES (?, ?, ?)")->execute([$test_user_id, $test_otp, $expires]);
    echo "<b style='color:green'>✅ OTP inserted successfully!</b><br>";
    
    // Now test select
    $stmt = $pdo->prepare("SELECT * FROM otp_codes WHERE user_id=? AND otp_code=? AND used=0 AND expires_at>NOW()");
    $stmt->execute([$test_user_id, $test_otp]);
    $found = $stmt->fetch();
    if ($found) {
        echo "<b style='color:green'>✅ OTP SELECT works! Row found: ID=" . $found['id'] . "</b><br>";
    } else {
        echo "<b style='color:red'>❌ OTP SELECT failed — row not found even after insert!</b><br>";
    }
} catch (Exception $e) {
    echo "<b style='color:red'>❌ INSERT ERROR: " . $e->getMessage() . "</b>";
}

echo "<br><br><a href='forgot_password.php'>Go to Forgot Password</a> | <a href='login.php'>Go to Login</a>";
?>