<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

$error = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = is_string($_POST['username'] ?? null)
        ? trim($_POST['username']) : '';
    $email = is_string($_POST['email'] ?? null)
        ? trim($_POST['email']) : '';
    $password = is_string($_POST['password'] ?? null)
        ? $_POST['password'] : '';

    if (!preg_match('/\A[A-Za-z0-9_]{3,50}\z/', $username)) {
        $error = 'Use 3–50 letters, numbers, or underscores for your username.';
    } elseif (strlen($email) > 254
        || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 12 || strlen($password) > 72) {
        $error = 'Use a password between 12 and 72 bytes long.';
    } elseif (str_contains($password, "\0")) {
        $error = 'The password contains an unsupported character.';
    } else {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash)
                 VALUES (?, ?, ?)'
            );
            $statement->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
            ]);

            header('Location: register.php?created=1', true, 303);
            exit;
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                $error = 'That username or email is already registered.';
            } else {
                error_log($exception->getMessage());
                $error = 'Registration is temporarily unavailable. Try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account | Fitness Tracker</title>
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
    <h1>Create your account</h1>

    <?php if (($_GET['created'] ?? '') === '1'): ?>
        <p role="status">Account created successfully.</p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token"
               value="<?= escape($_SESSION['csrf_token']) ?>">

        <label for="username">Username</label>
        <input id="username" name="username" required
               minlength="3" maxlength="50"
               pattern="[A-Za-z0-9_]{3,50}"
               autocomplete="username"
               value="<?= escape($username) ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required
               maxlength="254" autocomplete="email"
               value="<?= escape($email) ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password"
               required minlength="12" maxlength="72"
               autocomplete="new-password">
        <p>Use at least 12 characters.</p>

        <button type="submit">Create account</button>
    </form>
</body>
</html>

