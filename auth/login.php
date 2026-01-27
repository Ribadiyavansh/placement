<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PlacementPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card card">
            <div class="auth-header">
                <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Sign in to your account</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.875rem;">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.875rem;">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form action="auth_logic.php" method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Enter your password">
                </div>
                <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">Sign In</button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
                <p>Don't have an account? <a href="register.php" style="color: var(--primary-color); font-weight: 600;">Sign up</a></p>
                <p style="margin-top: 5px;"><a href="../admin/login.php" style="color: #6b7280; font-size: 0.8rem;">Admin Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
