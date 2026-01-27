<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlacementPro - Campus Placement Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            color: white;
            padding: 6rem 0;
            text-align: center;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.25rem;
            color: #e0e7ff;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        .feature-card {
            text-align: center;
            padding: 2rem;
            border-radius: var(--radius-lg);
            background: white;
            box-shadow: var(--shadow-md);
            transition: transform 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <nav style="background: white; padding: 1rem 0; box-shadow: var(--shadow-sm);">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-graduation-cap"></i> PlacementPro
            </div>
            <div>
                <a href="auth/login.php" class="btn btn-outline" style="margin-right: 0.5rem;">Login</a>
                <a href="auth/register.php" class="btn btn-primary">Student Register</a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container">
            <h1>Launch Your Career Today</h1>
            <p>The ultimate platform for students to find their dream jobs and for colleges to manage campus placements efficiently.</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <a href="auth/register.php" class="btn" style="background: white; color: var(--primary-color);">Get Started</a>
            </div>
        </div>
    </header>

    <section style="padding: 4rem 0;">
        <div class="container">
            <h2 style="text-align: center; font-size: 2rem; font-weight: 700; margin-bottom: 3rem;">Why PlacementPro?</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <!-- Feature 1 -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-briefcase"></i></div>
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Top Opportunities</h3>
                    <p style="color: var(--text-secondary);">Access exclusive job openings from top companies directly through your dashboard.</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-paper-plane"></i></div>
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Easy Application</h3>
                    <p style="color: var(--text-secondary);">Apply to multiple companies with a single profile and track your status real-time.</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Streamlined Process</h3>
                    <p style="color: var(--text-secondary);">Admins can easily manage job postings, student applications, and placement statistics.</p>
                </div>
            </div>
        </div>
    </section>

    <footer style="background: #1e293b; color: white; padding: 2rem 0; text-align: center;">
        <div class="container">
            <p>&copy; <?= date('Y') ?> PlacementPro. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
