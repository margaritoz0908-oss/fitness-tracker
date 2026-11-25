<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

try {
    $exercises = $pdo->query(
        'SELECT e.name, c.name AS category
         FROM exercises AS e
         JOIN exercise_categories AS c ON c.category_id = e.category_id
         ORDER BY e.name'
    )->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Could not load the exercise catalog.');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fitness Tracker</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            max-width: 850px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f5f8fa;
            color: #172b35;
        }
        nav { display: flex; gap: 20px; flex-wrap: wrap; }
        a { color: #12634d; }
        li { padding: 12px 0; }
        button {
            padding: 10px 18px;
            margin-top: 20px;
            background: #12634d;
            color: white;
            border: 0;
            border-radius: 6px;
            font: inherit;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Fitness Tracker</h1>
    <p>Manage exercises and keep a history of your workouts.</p>

    <nav aria-label="Main navigation">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="workouts.php">My workouts</a>
            <a href="exercises.php">Manage exercises</a>
        <?php else: ?>
            <a href="login.php">Log in</a>
            <a href="register.php">Create account</a>
        <?php endif; ?>
    </nav>

    <?php if (isset($_SESSION['user_id'])): ?>
        <p>Welcome, <?= escape($_SESSION['username']) ?>.</p>
        <form method="post" action="logout.php">
            <input type="hidden" name="csrf_token"
                   value="<?= escape($_SESSION['csrf_token']) ?>">
            <button type="submit">Log out</button>
        </form>
    <?php endif; ?>

    <h2>Exercise catalog</h2>
    <?php if (!$exercises): ?>
        <p>No exercises have been added yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($exercises as $exercise): ?>
                <li>
                    <?= escape($exercise['name']) ?>
                    (<?= escape($exercise['category']) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html><?php
try {
    $password = file_get_contents('/etc/fitness-tracker-db-password');
    if ($password === false) {
        throw new RuntimeException('Database password file is unreadable.');
    }

    $pdo = new PDO(
        'mysql:host=localhost;dbname=fitness_tracker;charset=utf8mb4',
        'fitness_app',
        rtrim($password, "\r\n"),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $exercises = $pdo->query(
        'SELECT e.name AS exercise, c.name AS category
         FROM exercises AS e
         JOIN exercise_categories AS c ON c.category_id = e.category_id
         ORDER BY e.name'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (RuntimeException $error) {
    error_log($error->getMessage());
    http_response_code(500);
    exit('Could not load the exercise catalog.');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fitness Tracker</title>
</head>
<body>
    <h1>Fitness Tracker</h1>
    <h2>Exercise Catalog</h2>
    <?php if (!$exercises): ?>
        <p>No exercises have been added yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($exercises as $row): ?>
                <li><?= htmlspecialchars($row['exercise']) ?>
                    (<?= htmlspecialchars($row['category']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
