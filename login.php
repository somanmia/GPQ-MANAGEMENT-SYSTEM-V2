<?php
session_start();
ob_start();

// ১. ডাটাবেস কানেকশন
require_once('config/db_connect.php');

$error_msg = "";

// ২. লগইন লজিক
if (isset($_POST['admin_login'])) {
    $buyer_id = trim($_POST['username']);
    $pass     = trim($_POST['password']);

    // Prepared Statement ব্যবহার করে সিকিউর SQL কোয়েরি
    $stmt = $conn->prepare("SELECT id, buyer_id, password FROM buyers WHERE buyer_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("s", $buyer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user_data = $result->fetch_assoc();
            
            // পাসওয়ার্ড ভেরিফিকেশন (password_hash বা প্লেন টেক্সট উভয় সাপোর্ট করবে)
            if (password_verify($pass, $user_data['password']) || $pass === $user_data['password']) {
                
                // সেশনে বায়ার আইডি ও ইনফো সেভ করা
                $_SESSION['buyer_db_id'] = $user_data['id'];
                $_SESSION['buyer_id']    = $user_data['buyer_id'];

                // লগইন সফল হলে ড্যাশবোর্ডে রিডাইরেক্ট
                header("Location: admin/home.php"); 
                exit;
            } else {
                $error_msg = "ভুল বায়ার আইডি অথবা পাসওয়ার্ড!";
            }
        } else {
            $error_msg = "ভুল বায়ার আইডি অথবা পাসওয়ার্ড!";
        }
        $stmt->close();
    } else {
        $error_msg = "ডাটাবেজ সমস্যা! অনুগ্রহ করে পরে চেষ্টা করুন।";
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Login | Sample Tracking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --admin-theme: #6366f1; --admin-hover: #4f46e5; }
        body { background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .login-card { background: white; border-radius: 15px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border: 1px solid #e1e4e8; }
        .admin-icon { font-size: 55px; color: var(--admin-theme); margin-bottom: 15px; }
        .form-control { border-radius: 10px; padding: 12px; border: 1px solid #dee2e6; font-size: 0.95rem; }
        .btn-admin { background: var(--admin-theme); color: white; border-radius: 10px; padding: 12px; font-weight: 600; width: 100%; transition: 0.3s ease; border: none; text-transform: uppercase; letter-spacing: 1px; }
        .btn-admin:hover { background: var(--admin-hover); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3); }
        .error-box { background: #fff5f5; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; border: 1px solid #feb2b2; }
    </style>
</head>
<body>

<div class="login-card text-center">
    <div class="admin-icon">
        <i class="fas fa-user-shield"></i>
    </div>
    <h4 class="fw-bold">Login</h4>
    <p class="text-muted small mb-4">ENTER YOUR CREDENTIALS TO CONTINUE.</p>
    
    <?php if(!empty($error_msg)): ?>
        <div class="error-box text-start">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>
    
    <form action="" method="POST">
        <div class="mb-3 text-start">
            <label class="form-label small fw-bold text-secondary">BUYER ID:</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                <input type="text" name="username" class="form-control border-start-0" placeholder="Ex: 2026" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
        </div>
        
        <div class="mb-4 text-start">
            <label class="form-label small fw-bold text-secondary">PASSWORD:</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
            </div>
        </div>
        
        <button type="submit" name="admin_login" class="btn-admin">
            Sign In <i class="fas fa-sign-in-alt ms-2"></i>
        </button>
    </form>
</div>

</body>
</html>