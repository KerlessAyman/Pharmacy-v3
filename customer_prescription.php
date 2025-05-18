<?php
session_start();
require 'config.php';

// Authentication check - for customers only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

try {
    $conn = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch only the current customer's prescriptions
$stmt = $conn->prepare("
    SELECT p.*, u.full_name 
    FROM Prescriptions p
    JOIN Users u ON p.user_id = u.user_id
    WHERE p.user_id = ?
    ORDER BY p.uploaded_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$prescriptions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 0.35em 0.65em;
            border-radius: 1rem;
            font-size: 0.875em;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .prescription-card {
            border-left: 4px solid;
            margin-bottom: 1.5rem;
        }
        .prescription-pending { border-color: #ffc107; }
        .prescription-approved { border-color: #28a745; }
        .prescription-rejected { border-color: #dc3545; }
        .file-link {
            color: #1a5276;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>My Prescriptions</h1>
            <a href="ISA1.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Upload New
            </a>
        </div>

        <?php if(empty($prescriptions)): ?>
            <div class="alert alert-info">No prescriptions found. Upload your first prescription!</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($prescriptions as $prescription): ?>
                    <div class="col-md-6">
                        <div class="card prescription-card prescription-<?= strtolower($prescription['status']) ?> mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title">
                                            <?= htmlspecialchars($prescription['file_name']) ?>
                                        </h5>
                                        <span class="status-badge status-<?= strtolower($prescription['status']) ?>">
                                            <?= htmlspecialchars($prescription['status']) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('M d, Y', strtotime($prescription['uploaded_at'])) ?>
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <a href="<?= htmlspecialchars($prescription['file_path']) ?>" 
                                       class="file-link"
                                       target="_blank">
                                        <i class="fas fa-file-pdf me-2"></i>View Prescription
                                    </a>
                                </div>

                                <?php if($prescription['status'] !== 'Pending'): ?>
                                    <div class="alert alert-<?= $prescription['status'] === 'Approved' ? 'success' : 'danger' ?>">
                                        <h6>Pharmacist Response:</h6>
                                        <p class="mb-0">
                                            <?php if(!empty($prescription['pharmacist_notes'])): ?>
                                                <?= nl2br(htmlspecialchars($prescription['pharmacist_notes'])) ?>
                                            <?php else: ?>
                                                <em>No additional notes provided</em>
                                            <?php endif; ?>
                                        </p>
                                        <small class="text-muted mt-2 d-block">
                                            Reviewed on: <?= date('M d, Y H:i', strtotime($prescription['reviewed_at'])) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="row text-muted small mt-3">
                                    <div class="col-6">
                                        <i class="fas fa-file-alt me-2"></i>
                                        <?= strtoupper(htmlspecialchars($prescription['file_type'])) ?>
                                    </div>
                                    <div class="col-6 text-end">
                                        <?= round($prescription['file_size'] / 1024, 1) ?> KB
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>