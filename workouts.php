<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$error = '';
$workout = [
    'workout_id' => '',
    'title' => '',
    'workout_date' => date('Y-m-d'),
    'notes' => '',
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();

        $action = $_POST['action'] ?? '';
        $id = filter_var(
            $_POST['workout_id'] ?? '',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($action === 'delete' && $id) {
            $statement = $pdo->prepare(
                'DELETE FROM workouts WHERE workout_id = ? AND user_id = ?'
            );
            $statement->execute([$id, $userId]);
            header('Location: workouts.php', true, 303);
            exit;
        }

        if ($action !== 'save') {
            http_response_code(400);
            exit('Invalid action.');
        }

        $title = is_string($_POST['title'] ?? null)
            ? trim($_POST['title']) : '';
        $date = is_string($_POST['workout_date'] ?? null)
            ? $_POST['workout_date'] : '';
        $notes = is_string($_POST['notes'] ?? null)
            ? trim($_POST['notes']) : '';

        $workout = [
            'workout_id' => $id ?: '',
            'title' => $title,
            'workout_date' => $date,
            'notes' => $notes,
        ];

        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($title === '' || strlen($title) > 100) {
            $error = 'Enter a title of up to 100 bytes.';
        } elseif (!$parsedDate
            || $parsedDate->format('Y-m-d') !== $date
            || $parsedDate->format('Y') < '1000') {
            $error = 'Enter a valid workout date.';
        } elseif (strlen($notes) > 10000) {
            $error = 'Keep notes under 10,000 bytes.';
        } elseif (($_POST['workout_id'] ?? '') !== '' && !$id) {
            $error = 'Invalid workout ID.';
        } else {
            if ($id) {
                $statement = $pdo->prepare(
                    'UPDATE workouts
                     SET title = ?, workout_date = ?, notes = ?
                     WHERE workout_id = ? AND user_id = ?'
                );
                $statement->execute([
                    $title, $date, $notes, $id, $userId,
                ]);
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO workouts
                     (user_id, title, workout_date, notes)
                     VALUES (?, ?, ?, ?)'
                );
                $statement->execute([$userId, $title, $date, $notes]);
            }

            header('Location: workouts.php', true, 303);
            exit;
        }
    } elseif (isset($_GET['edit'])) {
        $id = filter_var(
            $_GET['edit'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!$id) {
            http_response_code(400);
            exit('Invalid workout ID.');
        }

        $statement = $pdo->prepare(
            'SELECT * FROM workouts WHERE workout_id = ? AND user_id = ?'
        );
        $statement->execute([$id, $userId]);
        $found = $statement->fetch();

        if (!$found) {
            http_response_code(404);
            exit('Workout not found.');
        }

        $workout = $found;
    }

    $statement = $pdo->prepare(
        'SELECT * FROM workouts
         WHERE user_id = ?
         ORDER BY workout_date DESC, workout_id DESC'
    );
    $statement->execute([$userId]);
    $workouts = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Could not load or save workouts. Please try again.');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My workouts | Fitness Tracker</title>
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
        label { display: block; margin-top: 16px; }
        input, textarea {
            box-sizing: border-box;
            width: 100%;
            margin-top: 6px;
            padding: 12px;
            font: inherit;
            border: 1px solid #7d8c95;
            border-radius: 6px;
        }
        button {
            padding: 10px 18px;
            margin: 14px 0;
            background: #12634d;
            color: white;
            border: 0;
            border-radius: 6px;
            font: inherit;
            cursor: pointer;
        }
        article {
            padding: 20px;
            margin: 18px 0;
            border: 1px solid #ccd6dc;
            border-radius: 8px;
            background: white;
        }
        .error { color: #a51818; }
        .delete { background: #a51818; }
        .notes { white-space: pre-wrap; overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <nav aria-label="Main navigation">
        <a href="index.php">Exercise catalog</a>
        <a href="workouts.php">My workouts</a>
        <a href="login.php">My account</a>
    </nav>

    <h1>My workouts</h1>
    <p>Signed in as <?= escape($_SESSION['username']) ?>.</p>
    <h2><?= $workout['workout_id'] ? 'Edit workout' : 'Add workout' ?></h2>

    <?php if ($error !== ''): ?>
        <p class="error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>

    <form method="post" action="workouts.php">
        <input type="hidden" name="csrf_token"
               value="<?= escape($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="workout_id"
               value="<?= escape((string) $workout['workout_id']) ?>">

        <label for="title">Workout title</label>
        <input id="title" name="title" required maxlength="100"
               value="<?= escape($workout['title']) ?>">

        <label for="date">Workout date</label>
        <input id="date" name="workout_date" type="date" required
               min="1000-01-01" max="9999-12-31"
               value="<?= escape($workout['workout_date']) ?>">

        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="4"
                  maxlength="10000"><?= escape($workout['notes'] ?? '') ?></textarea>

        <button type="submit">Save workout</button>
        <?php if ($workout['workout_id']): ?>
            <a href="workouts.php">Cancel editing</a>
        <?php endif; ?>
    </form>

    <h2>Workout history</h2>

    <?php if (!$workouts): ?>
        <p>You haven’t recorded any workouts yet.</p>
    <?php endif; ?>

    <?php foreach ($workouts as $row): ?>
        <article>
            <h3><?= escape($row['title']) ?></h3>
            <p><?= escape($row['workout_date']) ?></p>
            <p class="notes"><?= escape($row['notes'] ?? '') ?></p>
            <a href="workouts.php?edit=<?= (int) $row['workout_id'] ?>">
                Edit workout
            </a>

            <form method="post" action="workouts.php"
                  onsubmit="return confirm('Delete this workout and its exercise logs?')">
                <input type="hidden" name="csrf_token"
                       value="<?= escape($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="workout_id"
                       value="<?= (int) $row['workout_id'] ?>">
                <button class="delete" type="submit">Delete workout</button>
            </form>
        </article>
    <?php endforeach; ?>
</body>
</html>

