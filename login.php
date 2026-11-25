<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = is_string($_POST['username'] ?? null)
        ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null)
        ? $_POST['password'] : '';

    try {
        $statement = $pdo->prepare(
            'SELECT user_id, username, password_hash
             FROM users WHERE username = ?'
        );
        $statement->execute([$username]);
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header('Location: login.php', true, 303);
            exit;
        }

        $error = 'Incorrect username or password.';
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $error = 'Login is temporarily unavailable. Try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in | Fitness Tracker</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            max-width: 480px;
            margin: 48px auto;
            padding: 0 20px;
            color: #172b35;
            background: #f5f8fa;
        }
        label { display: block; margin-top: 18px; }
        input {
            box-sizing: border-box;
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #7d8c95;
            border-radius: 6px;
            font: inherit;
        }
        button {
            margin-top: 24px;
            padding: 12px 20px;
            background: #12634d;
            color: white;
            border: 0;
            border-radius: 6px;
            font: inherit;
            cursor: pointer;
        }
        a { color: #12634d; }
        .error { color: #a51818; }
    </style>
</head>
<body>
    <a href="index.php">Fitness Tracker</a>

    <?php if (isset($_SESSION['user_id'])): ?>
        <h1>You’re logged in</h1>
        <p>Welcome, <?= escape($_SESSION['username']) ?>.</p>
        <a href="index.php">View exercise catalog</a>
    <?php else: ?>
        <h1>Log in</h1>

        <?php if ($error !== ''): ?>
            <p class="error" role="alert"><?= escape($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token"
                   value="<?= escape($_SESSION['csrf_token']) ?>">

            <label for="username">Username</label>
            <input id="username" name="username" required
                   maxlength="50" autocomplete="username"
                   value="<?= escape($username) ?>">

            <label for="password">Password</label>
            <input id="password" name="password" type="password"
                   required autocomplete="current-password">

            <button type="submit">Log in</button>
        </form>

        <p><a href="register.php">Create an account</a></p>
    <?php endif; ?>
</body>
</html>

