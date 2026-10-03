<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$editNote = null;

if (isset($_GET['edit']) && $_GET['edit'] !== '') {
    $noteId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM notes WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$noteId, $userId]);
    $editNote = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'store';

    if ($action === 'delete') {
        $noteId = (int) ($_POST['id'] ?? 0);
        if ($noteId > 0) {
            $stmt = $pdo->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
            $stmt->execute([$noteId, $userId]);
        }
        header('Location: dashboard.php');
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Reminder');
    $noteDate = $_POST['note_date'] ?? date('Y-m-d');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $_SESSION['message'] = 'Title and note content are required.';
        $_SESSION['message_type'] = 'danger';
        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'update') {
        $noteId = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE notes SET title = ?, category = ?, note_date = ?, content = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$title, $category, $noteDate, $content, $noteId, $userId]);
        $_SESSION['message'] = 'Note updated successfully!';
    } else {
        $stmt = $pdo->prepare('INSERT INTO notes (user_id, title, category, note_date, content) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $title, $category, $noteDate, $content]);
        $_SESSION['message'] = 'Note added successfully!';
    }

    $_SESSION['message_type'] = 'success';
    header('Location: dashboard.php');
    exit;
}

$month = (int) ($_GET['month'] ?? date('n'));
$year = (int) ($_GET['year'] ?? date('Y'));

if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

$calendarMonth = new DateTimeImmutable("{$year}-{$month}-01");
$daysInMonth = (int) $calendarMonth->format('t');
$firstDayNum = (int) $calendarMonth->format('N');
$calendarDates = [];

$stmt = $pdo->prepare('SELECT note_date FROM notes WHERE user_id = ? ORDER BY note_date ASC');
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $row) {
    $calendarDates[$row['note_date']] = true;
}

$stmt = $pdo->prepare('SELECT * FROM notes WHERE user_id = ? ORDER BY note_date DESC, created_at DESC');
$stmt->execute([$userId]);
$notes = $stmt->fetchAll();

$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['message_type'] ?? 'info';
unset($_SESSION['message'], $_SESSION['message_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notes Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar-shell">
        <div class="container py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="brand">Daily Notes</div>
                <div class="d-flex align-items-center gap-3">
                    <div class="header-pill">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></div>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container dashboard-shell">
        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>" role="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="topbar">
            <h1 class="page-header">Your Notes Board</h1>
            <a href="dashboard.php" class="btn-soft">Today</a>
        </div>

        <div class="overview-grid">
            <div class="metric-card">
                <div class="metric-label">Total notes</div>
                <div class="metric-value"><?= count($notes) ?></div>
                <div class="metric-trend">Saved in your timeline</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Events</div>
                <div class="metric-value"><?= count(array_filter($notes, fn($note) => $note['category'] === 'Event')) ?></div>
                <div class="metric-trend">Upcoming activity</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Reminders</div>
                <div class="metric-value"><?= count(array_filter($notes, fn($note) => $note['category'] === 'Reminder')) ?></div>
                <div class="metric-trend">To keep in check</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="panel">
                <div class="calendar-top">
                    <h3><?= $calendarMonth->format('F Y') ?></h3>
                    <div class="d-flex gap-2">
                        <?php
                        $prevMonth = $month - 1;
                        $prevYear = $year;
                        if ($prevMonth < 1) {
                            $prevMonth = 12;
                            $prevYear--;
                        }
                        $nextMonth = $month + 1;
                        $nextYear = $year;
                        if ($nextMonth > 12) {
                            $nextMonth = 1;
                            $nextYear++;
                        }
                        ?>
                        <a class="btn-soft" href="dashboard.php?month=<?= $prevMonth ?>&year=<?= $prevYear ?>">Prev</a>
                        <a class="btn-soft" href="dashboard.php?month=<?= $nextMonth ?>&year=<?= $nextYear ?>">Next</a>
                    </div>
                </div>

                <div class="calendar-grid">
                    <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName): ?>
                        <div class="day-name"><?= $dayName ?></div>
                    <?php endforeach; ?>

                    <?php
                    $calendarCells = $firstDayNum - 1;
                    for ($i = 0; $i < $calendarCells; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }

                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $dateString = sprintf('%s-%02d-%02d', $year, $month, $day);
                        $classes = ['calendar-day'];
                        if (isset($calendarDates[$dateString])) {
                            $classes[] = 'has-note';
                        }
                        if ($dateString === date('Y-n-j')) {
                            $classes[] = 'today';
                        }
                        echo '<div class="' . implode(' ', $classes) . '">' . $day . '</div>';
                    }
                    ?>
                </div>
            </div>

            <div class="panel">
                <h3><?= $editNote ? 'Edit Note' : 'Add New Note' ?></h3>
                <form method="POST" action="dashboard.php" class="form-wrap">
                    <input type="hidden" name="action" value="<?= $editNote ? 'update' : 'store' ?>">
                    <?php if ($editNote): ?>
                        <input type="hidden" name="id" value="<?= (int) $editNote['id'] ?>">
                    <?php endif; ?>

                    <div>
                        <label for="title">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($editNote['title'] ?? '') ?>" placeholder="Birthday planning" required>
                    </div>

                    <div>
                        <label for="category">Category</label>
                        <select class="form-select" id="category" name="category">
                            <?php
                            $categories = ['Reminder', 'Event', 'Special Occasion'];
                            $selectedCategory = $editNote['category'] ?? 'Reminder';
                            foreach ($categories as $categoryOption) {
                                $selected = $selectedCategory === $categoryOption ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($categoryOption) . '" ' . $selected . '>' . htmlspecialchars($categoryOption) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label for="note_date">Date</label>
                        <input type="date" class="form-control" id="note_date" name="note_date" value="<?= htmlspecialchars($editNote['note_date'] ?? date('Y-m-d')) ?>" required>
                    </div>

                    <div>
                        <label for="content">Details</label>
                        <textarea class="form-control" id="content" name="content" rows="5" placeholder="Write your reminder or event notes here..." required><?= htmlspecialchars($editNote['content'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill"><?= $editNote ? 'Update Note' : 'Save Note' ?></button>
                        <?php if ($editNote): ?>
                            <a href="dashboard.php" class="btn-soft">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-4">
            <h3 class="mb-3">Saved Notes</h3>

            <?php if (empty($notes)): ?>
                <div class="empty-state">No notes saved yet. Add your first reminder or event.</div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-card">
                        <div class="note-header">
                            <div>
                                <span class="note-category"><?= htmlspecialchars($note['category']) ?></span>
                                <h4 class="note-title"><?= htmlspecialchars($note['title']) ?></h4>
                                <div class="note-date"><?= date('F d, Y', strtotime($note['note_date'])) ?></div>
                            </div>
                            <div class="note-actions">
                                <a href="dashboard.php?edit=<?= (int) $note['id'] ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                                <form method="POST" action="dashboard.php" onsubmit="return confirm('Delete this note?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $note['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div class="note-body"><?= nl2br(htmlspecialchars($note['content'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
