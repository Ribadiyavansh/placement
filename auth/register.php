<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - PlacementPro</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card card">
            <div class="auth-header">
                <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Create Account</h1>
                <p>Join PlacementPro today</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-size: 0.875rem;">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="auth_logic.php" method="POST">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control" required placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" required placeholder="student@example.com">
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required placeholder="Create a password">
                </div>
                <button type="submit" name="register" class="btn btn-primary" style="width: 100%;">Sign Up</button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
                <p>Already have an account? <a href="login.php" style="color: var(--primary-color); font-weight: 600;">Sign in</a></p>
            </div>
        </div>
    </div>
</body>
</html>
