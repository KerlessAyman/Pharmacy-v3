<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tubia_pharmacy');

// Create database connection
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

// Handle file upload
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $message = 'Please log in to upload prescriptions.';
        $messageType = 'error';
    } else {
        // Check if file was uploaded
        if (isset($_FILES['prescription']) && $_FILES['prescription']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['prescription'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            if (!in_array($file['type'], $allowedTypes)) {
                $message = 'Invalid file type. Please upload only images (JPEG, PNG, GIF) or PDF files.';
                $messageType = 'error';
            }
            // Validate file size (5MB max)
            elseif ($file['size'] > 5 * 1024 * 1024) {
                $message = 'File size should not exceed 5MB.';
                $messageType = 'error';
            } else {
                // Create uploads directory if it doesn't exist
                $uploadDir = 'uploads/prescriptions/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Generate unique filename
                $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = uniqid('prescription_') . '.' . $fileExt;
                $filePath = $uploadDir . $fileName;
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    try {
                        // Save to database
                        $stmt = $conn->prepare("INSERT INTO prescriptions 
                            (user_id, file_name, file_path, file_type, file_size, status) 
                            VALUES (?, ?, ?, ?, ?, 'pending')");
                        $stmt->execute([
                            $_SESSION['user_id'],
                            $file['name'],
                            $filePath,
                            $file['type'],
                            $file['size']
                        ]);
                        
                        $message = 'Your prescription has been uploaded successfully! Our pharmacists will review it and contact you shortly.';
                        $messageType = 'success';
                    } catch (PDOException $e) {
                        // Delete the uploaded file if database insert fails
                        unlink($filePath);
                        $message = 'Error saving prescription details. Please try again.';
                        $messageType = 'error';
                        error_log("Database error: " . $e->getMessage());
                    }
                } else {
                    $message = 'Error uploading file. Please try again.';
                    $messageType = 'error';
                }
            }
        } else {
            $errorCode = $_FILES['prescription']['error'] ?? -1;
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File is too large (server limit exceeded)',
                UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit exceeded)',
                UPLOAD_ERR_PARTIAL => 'File upload was incomplete',
                UPLOAD_ERR_NO_FILE => 'No file was selected',
                UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            
            $message = $uploadErrors[$errorCode] ?? 'Please select a file to upload.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Prescription - Web Pharmacy</title>
  <link rel="stylesheet" href="ISA1.css">
</head>

<body>
  <div class="upload-container">
    <div class="upload-box">
      <h1>Upload Your Prescription</h1>
      <p>Upload a clear image or PDF of your prescription for our pharmacists to review</p>

      <form id="uploadForm" method="POST" enctype="multipart/form-data">
        <div class="file-input-container">
          <label for="fileInput" class="file-input-label" id="fileInputLabel">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
              style="margin-bottom: 10px;">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="17 8 12 3 7 8"></polyline>
              <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            <div>Click to browse or drag & drop files here</div>
            <div class="file-name" id="fileName"></div>
          </label>
          <input type="file" id="fileInput" name="prescription" accept="image/*,application/pdf" required>
        </div>
        <button type="submit" id="uploadButton">Upload Prescription</button>
      </form>

      <?php if ($message): ?>
      <div id="message" class="<?php echo htmlspecialchars($messageType); ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
      <?php else: ?>
      <div id="message" class="hidden"></div>
      <?php endif; ?>

      <div class="links">
        <a href="cart.php">Back to Cart</a> |
        <a href="orders.php">View Orders</a>
      </div>
    </div>
  </div>

  <script>
    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    const fileInputLabel = document.getElementById('fileInputLabel');
    const fileName = document.getElementById('fileName');
    const uploadButton = document.getElementById('uploadButton');
    const message = document.getElementById('message');

    // File input change handler
    fileInput.addEventListener('change', function () {
      if (this.files.length > 0) {
        fileName.textContent = this.files[0].name;
        fileInputLabel.classList.add('has-file');
      } else {
        fileName.textContent = '';
        fileInputLabel.classList.remove('has-file');
      }
    });

    // Drag and drop functionality
    fileInputLabel.addEventListener('dragover', function (e) {
      e.preventDefault();
      this.classList.add('drag-over');
    });

    fileInputLabel.addEventListener('dragleave', function () {
      this.classList.remove('drag-over');
    });

    fileInputLabel.addEventListener('drop', function (e) {
      e.preventDefault();
      this.classList.remove('drag-over');

      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        fileName.textContent = fileInput.files[0].name;
        fileInputLabel.classList.add('has-file');
      }
    });

    // Form submission
    uploadForm.addEventListener('submit', function (event) {
      // Client-side validation
      if (fileInput.files.length === 0) {
        showMessage('Please select a file before uploading.', 'error');
        event.preventDefault();
        return;
      }

      // Validate file type
      const file = fileInput.files[0];
      const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
      if (!validTypes.includes(file.type)) {
        showMessage('Please upload only images (JPEG, PNG, GIF) or PDF files.', 'error');
        event.preventDefault();
        return;
      }

      // Validate file size (5MB max)
      if (file.size > 5 * 1024 * 1024) {
        showMessage('File size should not exceed 5MB.', 'error');
        event.preventDefault();
        return;
      }

      // Disable button during upload
      uploadButton.disabled = true;
      uploadButton.textContent = 'Uploading...';
    });

    function showMessage(text, type) {
      message.textContent = text;
      message.className = type;
      message.style.display = 'block';

      // Hide message after 5 seconds
      setTimeout(() => {
        message.style.display = 'none';
      }, 5000);
    }
  </script>
</body>
</html>