<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Invalid username or password.';
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Notes Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="auth-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="auth-card">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="brand mb-3">Daily Notes</div>
                                <h1 class="glow-title">Welcome back</h1>
                                <p class="glow-subtext">Track reminders, events, and special moments in one place.</p>
                            </div>

                            <?php if ($error !== ''): ?>
                                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>

                            <form method="POST" action="index.php" class="mt-4">
                                <div class="mb-3">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control form-control-lg" id="username" name="username" placeholder="Enter username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Enter password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 btn-lg">Login</button>
                            </form>

                            <div class="text-center mt-4">
                                <span class="text-muted">New here?</span>
                                <a href="register.php" class="text-decoration-none fw-semibold text-success">Create account</a>
                            </div>

                            <div class="mt-4 small text-center text-muted">
                                Demo access: <strong class="text-white">admin</strong> / <strong class="text-white">admin123</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
