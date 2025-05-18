<?php
session_start();
require 'config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
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
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['prescription_ids'] as $key => $id) {
            $notes = $_POST['pharmacist_notes'][$key];
            $status = $_POST['status'][$key];
            
            $stmt = $pdo->prepare("
                UPDATE Prescriptions 
                SET pharmacist_notes = :notes, 
                    status = :status,
                    reviewed_at = NOW()
                WHERE prescription_id = :id
            ");
            $stmt->execute([
                ':notes' => $notes,
                ':status' => $status,
                ':id' => $id
            ]);
        }
        
        $pdo->commit();
        $success = "Data updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating records: " . $e->getMessage();
    }
}

// Fetch prescriptions with user information
$stmt = $pdo->query("
    SELECT p.*, u.full_name 
    FROM Prescriptions p
    JOIN Users u ON p.user_id = u.user_id
    ORDER BY p.uploaded_at DESC
");
$prescriptions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescriptions Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
       
    .table thead {
        background-color: #218838; /* Dark green */
        color: white;
    }
    .file-link {
        text-decoration: none;
        color: #218838; /* Dark green */
    }
    .status-select {
        min-width: 120px;
    }
    .btn-success {
        background-color: #28a745; /* Bootstrap success green */
        border-color: #28a745;
    }
    .alert-success {
        background-color: #d4edda; /* Light green background */
        border-color: #c3e6cb;
        color: #155724; /* Dark green text */
    }
    .table-hover tbody tr:hover {
        background-color: #f0fff4; /* Very light green hover effect */
    }

    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Prescriptions Management</h2>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>File Name</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Uploaded</th>
                        <th>Status</th>
                        <th>Pharmacist Notes</th>
                        <th>Reviewed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prescriptions as $prescription): ?>
                        <tr>
                            <td><?= htmlspecialchars($prescription['prescription_id']) ?></td>
                            <td><?= htmlspecialchars($prescription['full_name']) ?><br>
                                <small>(ID: <?= $prescription['user_id'] ?>)</small></td>
                            <td>
                                <a href="<?= htmlspecialchars($prescription['file_path']) ?>" 
                                   class="file-link"
                                   target="_blank">
                                    <?= htmlspecialchars($prescription['file_name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($prescription['file_type']) ?></td>
                            <td><?= round($prescription['file_size'] / 1024, 1) ?> KB</td>
                            <td><?= date('M d, Y H:i', strtotime($prescription['uploaded_at'])) ?></td>
                            <td>
                                <input type="hidden" 
                                       name="prescription_ids[]" 
                                       value="<?= $prescription['prescription_id'] ?>">
                                <select class="form-select status-select" name="status[]">
                                    <option value="Pending" <?= $prescription['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Approved" <?= $prescription['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                    <option value="Rejected" <?= $prescription['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </td>
                            <td>
                                <textarea class="form-control" 
                                          name="pharmacist_notes[]"
                                          rows="2"><?= htmlspecialchars($prescription['pharmacist_notes']) ?></textarea>
                            </td>
                            <td><?= $prescription['reviewed_at'] ? date('M d, Y H:i', strtotime($prescription['reviewed_at'])) : 'N/A' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-success">Save Changes</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>