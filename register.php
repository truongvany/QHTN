<?php 
require_once 'config.php'; 

// Nếu đã đăng nhập thì đá về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$msg_type = ''; // success/error

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validate cơ bản
    if (strlen($password) < 6) {
        $message = "Mật khẩu phải có ít nhất 6 ký tự!";
        $msg_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "Mật khẩu nhập lại không khớp!";
        $msg_type = 'error';
    } else {
        // 2. Kiểm tra user tồn tại
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $message = "Tên đăng nhập hoặc Email đã tồn tại!";
            $msg_type = 'error';
        } else {
            // 3. Đăng ký thành công
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; // Mặc định là user thường

            $sql = "INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            
            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                // Chuyển hướng sang login kèm thông báo
                header("Location: login.php?status=success");
                exit();
            } else {
                $message = "Có lỗi hệ thống, vui lòng thử lại sau.";
                $msg_type = 'error';
            }
        }
    }
}
?>

<?php include 'header.php'; ?>

<div class="auth-container">
    <form method="POST" class="auth-form">
        <h2>Đăng Ký Thành Viên</h2>
        
        <?php if($message): ?>
            <div class="alert error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>Tên đăng nhập</label>
            <input type="text" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" placeholder="Ví dụ: thaonguyen123">
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" placeholder="Ví dụ: email@gmail.com">
        </div>
        
        <div class="form-group">
            <label>Mật khẩu (Tối thiểu 6 ký tự)</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Nhập lại mật khẩu</label>
            <input type="password" name="confirm_password" required>
        </div>
        
        <button type="submit" name="register" class="btn-pink">Đăng Ký Ngay</button>
        
        <p class="switch-auth">
            Đã có tài khoản? <a href="login.php">Đăng nhập</a>
        </p>
    </form>
</div>

<style>
    /* CSS Đồng bộ với Login */
    .auth-container { 
        display: flex; justify-content: center; padding: 60px 20px; 
        background: #fdfdfd; min-height: 70vh; 
    }
    .auth-form { 
        background: white; padding: 40px; border-radius: 15px; 
        box-shadow: 0 10px 30px rgba(233, 30, 99, 0.1); width: 100%; max-width: 400px; 
        border: 1px solid rgba(255, 71, 87, 0.2); 
    }
    .auth-form h2 { 
        text-align: center; color: var(--accent-pink, #ff4757); 
        margin-bottom: 25px; font-weight: 700; text-transform: uppercase; 
    }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 14px; }
    .form-group input { 
        width: 100%; padding: 12px; border: 1px solid #ddd; 
        border-radius: 8px; font-size: 14px; outline: none; transition: 0.3s; 
    }
    .form-group input:focus { border-color: var(--accent-pink, #ff4757); box-shadow: 0 0 0 3px rgba(255, 71, 87, 0.1); }
    
    .btn-pink { 
        width: 100%; background: var(--accent-pink, #ff4757); color: white; 
        padding: 12px; border: none; border-radius: 8px; cursor: pointer; 
        font-weight: 700; font-size: 16px; margin-top: 10px; transition: 0.3s; 
    }
    .btn-pink:hover { background: var(--primary-pink, #ff6b81); transform: translateY(-2px); }
    
    .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .alert.error { background: #ffe6e6; color: #d63031; border: 1px solid #ffcccc; }
    
    .switch-auth { text-align: center; margin-top: 20px; font-size: 14px; color: #666; }
    .switch-auth a { color: var(--accent-pink, #ff4757); font-weight: 600; text-decoration: none; }
    .switch-auth a:hover { text-decoration: underline; }
</style>