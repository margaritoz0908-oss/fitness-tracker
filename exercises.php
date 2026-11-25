<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$exercise = [
    'exercise_id' => '',
    'category_id' => '',
    'name' => '',
    'description' => '',
];

$search = is_string($_GET['search'] ?? null)
    ? trim($_GET['search']) : '';

try {
    $categories = $pdo->query(
        'SELECT category_id, name
         FROM exercise_categories ORDER BY name'
    )->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();

        $action = $_POST['action'] ?? '';
        $id = filter_var(
            $_POST['exercise_id'] ?? '',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($action === 'delete' && $id) {
            try {
                $statement = $pdo->prepare(
                    'DELETE FROM exercises WHERE exercise_id = ?'
                );
                $statement->execute([$id]);
                header('Location: exercises.php', true, 303);
                exit;
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) === 1451) {
                    $error = 'This exercise is used in workout history and cannot be deleted.';
                } else {
                    throw $exception;
                }
            }
        } elseif ($action === 'save') {
            $name = is_string($_POST['name'] ?? null)
                ? trim($_POST['name']) : '';
            $description = is_string($_POST['description'] ?? null)
                ? trim($_POST['description']) : '';
            $categoryId = filter_var(
                $_POST['category_id'] ?? '',
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            $exercise = [
                'exercise_id' => $id ?: '',
                'category_id' => $categoryId ?: '',
                'name' => $name,
                'description' => $description,
            ];

            $categoryIds = array_map(
                'intval',
                array_column($categories, 'category_id')
            );

            if ($name === '' || strlen($name) > 100) {
                $error = 'Enter an exercise name of up to 100 bytes.';
            } elseif (!$categoryId
                || !in_array($categoryId, $categoryIds, true)) {
                $error = 'Choose a valid category.';
            } elseif (strlen($description) > 10000) {
                $error = 'Keep the description under 10,000 bytes.';
            } elseif (($_POST['exercise_id'] ?? '') !== '' && !$id) {
                $error = 'Invalid exercise ID.';
            } else {
                try {
                    if ($id) {
                        $statement = $pdo->prepare(
                            'UPDATE exercises
                             SET category_id = ?, name = ?, description = ?
                             WHERE exercise_id = ?'
                        );
                        $statement->execute([
                            $categoryId, $name, $description, $id,
                        ]);
                    } else {
                        $statement = $pdo->prepare(
                            'INSERT INTO exercises
                             (category_id, name, description)
                             VALUES (?, ?, ?)'
                        );
                        $statement->execute([
                            $categoryId, $name, $description,
                        ]);
                    }

                    header('Location: exercises.php', true, 303);
                    exit;
                } catch (PDOException $exception) {
                    if (($exception->errorInfo[1] ?? null) === 1062) {
                        $error = 'An exercise with that name already exists.';
                    } else {
                        throw $exception;
                    }
                }
            }
        } else {
            http_response_code(400);
            exit('Invalid action.');
        }
    } elseif (isset($_GET['edit'])) {
        $id = filter_var(
            $_GET['edit'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if (!$id) {
            http_response_code(400);
            exit('Invalid exercise ID.');
        }

        $statement = $pdo->prepare(
            'SELECT * FROM exercises WHERE exercise_id = ?'
        );
        $statement->execute([$id]);
        $found = $statement->fetch();

        if (!$found) {
            http_response_code(404);
            exit('Exercise not found.');
        }

        $exercise = $found;
    }

    $statement = $pdo->prepare(
        'SELECT e.*, c.name AS category
         FROM exercises AS e
         JOIN exercise_categories AS c ON c.category_id = e.category_id
         WHERE e.name LIKE ? OR c.name LIKE ?
         ORDER BY e.name'
    );
    $statement->execute(['%' . $search . '%', '%' . $search . '%']);
    $exercises = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Could not load or save exercises. Please try again.');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exercises | Fitness Tracker</title>
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
        input, select, textarea {
            box-sizing: border-box;
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #7d8c95;
            border-radius: 6px;
            font: inherit;
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
        .description {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body>
    <nav aria-label="Main navigation">
        <a href="index.php">Exercise catalog</a>
        <a href="exercises.php">Manage exercises</a>
        <a href="workouts.php">My workouts</a>
        <a href="login.php">My account</a>
    </nav>

    <h1>Manage exercises</h1>
    <p>The exercise catalog is shared. Signed-in users can manage it.</p>

    <h2><?= $exercise['exercise_id'] ? 'Edit exercise' : 'Add exercise' ?></h2>

    <?php if ($error !== ''): ?>
        <p class="error" role="alert"><?= escape($error) ?></p>
    <?php endif; ?>

    <form method="post" action="exercises.php">
        <input type="hidden" name="csrf_token"
               value="<?= escape($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="exercise_id"
               value="<?= escape((string) $exercise['exercise_id']) ?>">

        <label for="name">Exercise name</label>
        <input id="name" name="name" required maxlength="100"
               value="<?= escape($exercise['name']) ?>">

        <label for="category">Category</label>
        <select id="category" name="category_id" required>
            <option value="">Choose a category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['category_id'] ?>"
                    <?= (string) $exercise['category_id']
                        === (string) $category['category_id']
                        ? 'selected' : '' ?>>
                    <?= escape($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"
                  maxlength="10000"><?= escape($exercise['description'] ?? '') ?></textarea>

        <button type="submit">Save exercise</button>
        <?php if ($exercise['exercise_id']): ?>
            <a href="exercises.php">Cancel editing</a>
        <?php endif; ?>
    </form>

    <h2>Exercise catalog</h2>

    <form method="get" action="exercises.php">
        <label for="search">Search by exercise or category</label>
        <input id="search" name="search"
               value="<?= escape($search) ?>">
        <button type="submit">Search</button>
        <a href="exercises.php">Clear search</a>
    </form>

    <?php if (!$exercises): ?>
        <p>No exercises found.</p>
    <?php endif; ?>

    <?php foreach ($exercises as $row): ?>
        <article>
            <h3><?= escape($row['name']) ?></h3>
            <p><?= escape($row['category']) ?></p>
            <p class="description"><?= escape($row['description'] ?? '') ?></p>

            <a href="exercises.php?edit=<?= (int) $row['exercise_id'] ?>">
                Edit exercise
            </a>

            <form method="post" action="exercises.php"
                  onsubmit="return confirm('Delete this exercise?')">
                <input type="hidden" name="csrf_token"
                       value="<?= escape($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="exercise_id"
                       value="<?= (int) $row['exercise_id'] ?>">
                <button class="delete" type="submit">Delete exercise</button>
            </form>
        </article>
    <?php endforeach; ?>
</body>
</html>

