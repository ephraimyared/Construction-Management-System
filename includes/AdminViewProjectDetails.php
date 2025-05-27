<?php
session_start();
include '../db_connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['project_id'])) {
    echo "Invalid request.";
    exit();
}

$project_id = intval($_GET['project_id']);

$project_query = $connection->prepare("SELECT p.*, u.FirstName, u.LastName FROM projects p JOIN users u ON p.manager_id = u.UserID WHERE p.project_id = ?");
$project_query->bind_param("i", $project_id);
$project_query->execute();
$project_result = $project_query->get_result();
$project = $project_result->fetch_assoc();

$comments_query = $connection->prepare("SELECT c.comment_text, c.comment_date, u.FirstName, u.LastName FROM project_comments c JOIN users u ON c.user_id = u.UserID WHERE c.project_id = ? ORDER BY c.comment_date DESC");
$comments_query->bind_param("i", $project_id);
$comments_query->execute();
$comments_result = $comments_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Project Details</h2>
    <div class="mb-3"><strong>Project Name:</strong> <?= htmlspecialchars($project['project_name']) ?></div>
    <div class="mb-3"><strong>Manager:</strong> <?= htmlspecialchars($project['FirstName'] . ' ' . $project['LastName']) ?></div>
    <div class="mb-3"><strong>Status:</strong> <?= htmlspecialchars($project['status']) ?></div>
    <div class="mb-3"><strong>Start Date:</strong> <?= $project['start_date'] ?></div>
    <div class="mb-3"><strong>End Date:</strong> <?= $project['end_date'] ?></div>
    <div class="mb-3"><strong>Budget:</strong> <?= $project['budget'] ?></div>
    <div class="mb-3"><strong>Description:</strong><br><?= nl2br(htmlspecialchars($project['description'])) ?></div>
    <?php if (!empty($project['attachment'])): ?>
        <div class="mb-3"><strong>Attachment:</strong> <a href="<?= htmlspecialchars($project['attachment']) ?>" target="_blank">Download</a></div>
    <?php endif; ?>

    <hr>
    <h4>Comment History</h4>
    <?php while ($comment = $comments_result->fetch_assoc()): ?>
        <div class="border rounded p-2 mb-2">
            <strong><?= htmlspecialchars($comment['FirstName'] . ' ' . $comment['LastName']) ?>:</strong>
            <em><?= $comment['comment_date'] ?></em>
            <p><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
        </div>
    <?php endwhile; ?>

    <hr>
    <form action="SaveProjectCommentAndDecision.php" method="POST">
        <input type="hidden" name="project_id" value="<?= $project_id ?>">
        <div class="mb-3">
            <label for="comment" class="form-label">Add Comment</label>
            <textarea name="comment_text" id="comment" class="form-control" rows="4" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Decision</label><br>
            <button name="decision" value="Approved" class="btn btn-success">Approve</button>
            <button name="decision" value="Rejected" class="btn btn-danger">Reject</button>
        </div>
    </form>
    <a href="ApproveProjects.php" class="btn btn-secondary">Back</a>
</div>
</body>
</html>

<?php $connection->close(); ?>
