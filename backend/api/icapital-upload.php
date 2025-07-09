<?php
include 'cors.php'; // ✅ Enforces session validation
// include 'secure.php';
include 'connect_db.php';
// include 'post.php'; // for post services 

// Function to validate file upload
function validateFileUpload($file)
{
    $errors = [];

    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed';
        return $errors;
    }

    // Check file size (3MB limit)
    $maxSize = 3 * 1024 * 1024; // 3MB in bytes
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds 3MB limit';
    }

    // Check file type
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'image/gif',
        'text/plain'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'File type not allowed. Allowed types: PDF, DOC, DOCX, JPG, PNG, GIF, TXT';
    }

    return $errors;
}

// Function to generate unique filename
function generateUniqueFilename($originalName, $extension)
{
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8));
    return $timestamp . '_' . $randomString . '.' . $extension;
}

// Function to get file extension
function getFileExtension($filename)
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if investor_id is provided
    if (!isset($_POST['investor_id']) || empty($_POST['investor_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Investor ID is required']);
        exit;
    }

    $investor_id = intval($_POST['investor_id']);

    // Verify investor exists
    $stmt = $conn->prepare("SELECT id FROM investors WHERE id = ?");
    $stmt->bind_param("i", $investor_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Investor not found']);
        exit;
    }
    $stmt->close();

    // Check if file was uploaded
    if (!isset($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['document'];

    // Validate file
    $errors = validateFileUpload($file);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'File validation failed', 'errors' => $errors]);
        exit;
    }

    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/investors/' . $investor_id . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $originalFilename = $file['name'];
    $extension = getFileExtension($originalFilename);
    $storedFilename = generateUniqueFilename($originalFilename, $extension);
    $filePath = $uploadDir . $storedFilename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Get file info
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Store file information in database
        $stmt = $conn->prepare("INSERT INTO documents (investor_id, original_filename, stored_filename, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?, ?)");
        $relativePath = 'uploads/investors/' . $investor_id . '/' . $storedFilename;
        $stmt->bind_param("isssss", $investor_id, $originalFilename, $storedFilename, $relativePath, $file['size'], $fileType);

        if ($stmt->execute()) {
            $document_id = $conn->insert_id;
            echo json_encode([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'document_id' => $document_id,
                'data' => [
                    'id' => $document_id,
                    'investor_id' => $investor_id,
                    'original_filename' => $originalFilename,
                    'stored_filename' => $storedFilename,
                    'file_path' => $relativePath,
                    'file_size' => $file['size'],
                    'file_type' => $fileType
                ]
            ]);
        } else {
            // Remove uploaded file if database insert fails
            unlink($filePath);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to save file information to database']);
        }

        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Retrieve documents for a specific investor
    if (!isset($_GET['investor_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Investor ID is required']);
        exit;
    }

    $investor_id = intval($_GET['investor_id']);

    $stmt = $conn->prepare("SELECT id, investor_id, original_filename, stored_filename, file_path, file_size, file_type, upload_date FROM documents WHERE investor_id = ? ORDER BY upload_date DESC");
    $stmt->bind_param("i", $investor_id);
    $stmt->execute();
    $stmt->store_result();

    // Bind result variables
    $stmt->bind_result($id, $investor_id, $original_filename, $stored_filename, $file_path, $file_size, $file_type, $upload_date);

    $documents = [];
    while ($stmt->fetch()) {
        $documents[] = [
            'id' => $id,
            'investor_id' => $investor_id,
            'original_filename' => $original_filename,
            'stored_filename' => $stored_filename,
            'file_path' => $file_path,
            'file_size' => $file_size,
            'file_type' => $file_type,
            'upload_date' => $upload_date
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $documents
    ]);

    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}

$conn->close();
?>