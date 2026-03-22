<?php 
require_once 'config.php'; 

// Kiểm tra nếu đã đăng nhập thì chuyển hướng luôn
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. Truy vấn user theo username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Kiểm tra: Có user VÀ Mật khẩu khớp
    if ($user && password_verify($password, $user['password'])) {
        
        // Đăng nhập thành công -> Lưu Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; // Lưu quyền hạn (admin/user)
        
        // Chuyển hướng dựa trên quyền
        if ($user['role'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();

    } else {
        // Đăng nhập thất bại
        $error = "Sai tên đăng nhập hoặc mật khẩu!";
    }
}
?>

<?php include 'header.php'; ?>

<div class="auth-container">
    <form method="POST" class="auth-form">
        <h2>Đăng Nhập QHTN</h2>
        
        <?php if(isset($_GET['status']) && $_GET['status']=='success'): ?>
            <div class="alert success">
                <i class="fa-solid fa-check-circle"></i> Đăng ký thành công! Hãy đăng nhập.
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>Tên đăng nhập</label>
            <input type="text" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label>Mật khẩu</label>
            <input type="password" name="password" required>
        </div>
        
        <button type="submit" name="login" class="btn-pink">Đăng Nhập</button>
        
        <p class="switch-auth">
            Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
        </p>
    </form>
</div>

<style>
    /* CSS Cục bộ cho trang Login */
    .auth-container { 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        padding: 60px 20px; 
        min-height: 60vh; 
        background: #fdfdfd;
    }
    
    .auth-form { 
        background: white; 
        padding: 40px; 
        border-radius: 15px; 
        box-shadow: 0 10px 30px rgba(233, 30, 99, 0.1); 
        width: 100%; 
        max-width: 400px; 
        border: 1px solid rgba(255, 71, 87, 0.2); 
    }
    
    .auth-form h2 { 
        text-align: center; 
        color: var(--accent-pink, #ff4757); 
        margin-bottom: 25px; 
        font-weight: 700;
        text-transform: uppercase;
    }
    
    .form-group { margin-bottom: 20px; }
    
    .form-group label { 
        display: block; 
        margin-bottom: 8px; 
        font-weight: 600; 
        color: #555;
    }
    
    .form-group input { 
        width: 100%; 
        padding: 12px; 
        border: 1px solid #ddd; 
        border-radius: 8px; 
        font-size: 14px;
        transition: 0.3s;
        outline: none;
    }
    
    .form-group input:focus { 
        border-color: var(--accent-pink, #ff4757); 
        box-shadow: 0 0 0 3px rgba(255, 71, 87, 0.1);
    }
    
    .btn-pink { 
        width: 100%; 
        background: var(--accent-pink, #ff4757); 
        color: white; 
        padding: 12px; 
        border: none; 
        border-radius: 8px; 
        cursor: pointer; 
        font-weight: 700; 
        font-size: 16px; 
        transition: 0.3s;
        margin-top: 10px;
    }
    
    .btn-pink:hover { 
        background: var(--primary-pink, #ff6b81); 
        transform: translateY(-2px);
    }
    
    /* Style thông báo lỗi/thành công */
    .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .alert.error { background: #ffe6e6; color: #d63031; border: 1px solid #ffcccc; }
    .alert.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    
    .switch-auth { text-align: center; margin-top: 20px; font-size: 14px; color: #666; }
    .switch-auth a { color: var(--accent-pink, #ff4757); font-weight: 600; text-decoration: none; }
    .switch-auth a:hover { text-decoration: underline; }
</style>