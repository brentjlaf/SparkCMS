<?php
// File: login.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/includes/data.php';

$settingsFile = __DIR__ . '/data/settings.json';
$settings = [];
if (file_exists($settingsFile)) {
    $settings = read_json_file($settingsFile);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$username = sanitize_text($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$user = find_user($username);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        header('Location: admin.php');
        exit;
    }
    $error = 'Invalid credentials';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">  <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - <?php echo htmlspecialchars($settings['site_name'] ?? 'SparkCMS'); ?></title>
    <link rel="stylesheet" href="spark-cms.css">
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <label>Username <input type="text" name="username" required></label>
            <label>Password <input type="password" name="password" required></label>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</body>
</html>
