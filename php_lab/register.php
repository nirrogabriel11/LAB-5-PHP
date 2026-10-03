<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $username === '' || $password === '') {
        $message = 'Please fill in all fields.';
        $messageType = 'danger';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'danger';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'danger';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $message = 'This username is already taken.';
            $messageType = 'danger';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (full_name, username, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$fullName, $username, $passwordHash]);

            $message = 'Account created successfully. You can now log in.';
            $messageType = 'success';
            $_POST = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="auth-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="auth-card">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="brand mb-3">Daily Notes</div>
                                <h1 class="glow-title">Create account</h1>
                                <p class="glow-subtext">Start planning events and reminders that matter to you.</p>
                            </div>

                            <?php if ($message !== ''): ?>
                                <div class="alert alert-<?= htmlspecialchars($messageType) ?>" role="alert"><?= htmlspecialchars($message) ?></div>
                            <?php endif; ?>

                            <form method="POST" action="register.php" class="mt-4">
                                <div class="mb-3">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" class="form-control form-control-lg" id="full_name" name="full_name" placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control form-control-lg" id="username" name="username" placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Create password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password">Confirm Password</label>
                                    <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 btn-lg">Register</button>
                            </form>

                            <div class="text-center mt-4">
                                <a href="index.php" class="text-decoration-none fw-semibold text-success">Back to login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
