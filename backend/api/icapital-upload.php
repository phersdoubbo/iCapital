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

// Function to process multiple files
function processMultipleFiles($files, $investor_id, $conn)
{
    $results = [];
    $successCount = 0;
    $errorCount = 0;

    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/investors/' . $investor_id . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Process each file
    foreach ($files as $index => $file) {
        error_log("DEBUG: Processing file $index: " . $file['name']);

        $fileResult = [
            'original_filename' => $file['name'],
            'status' => 'error',
            'message' => ''
        ];

        // Validate file
        $errors = validateFileUpload($file);
        if (!empty($errors)) {
            $fileResult['message'] = implode(', ', $errors);
            error_log("DEBUG: File $index validation failed: " . $fileResult['message']);
            $results[] = $fileResult;
            $errorCount++;
            continue;
        }

        // Generate unique filename
        $originalFilename = $file['name'];
        $extension = getFileExtension($originalFilename);
        $storedFilename = generateUniqueFilename($originalFilename, $extension);
        $filePath = $uploadDir . $storedFilename;

        error_log("DEBUG: Moving file $index to: $filePath");

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            error_log("DEBUG: File $index moved successfully");

            // Get file info
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            // Store file information in database
            $stmt = $conn->prepare("INSERT INTO documents (investor_id, original_filename, stored_filename, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                $fileResult['message'] = 'Database prepare failed: ' . $conn->error;
                error_log("DEBUG: File $index database prepare failed: " . $conn->error);
                $results[] = $fileResult;
                $errorCount++;
                unlink($filePath);
                continue;
            }

            $relativePath = 'uploads/investors/' . $investor_id . '/' . $storedFilename;
            $stmt->bind_param("isssss", $investor_id, $originalFilename, $storedFilename, $relativePath, $file['size'], $fileType);

            if ($stmt->execute()) {
                $document_id = $conn->insert_id;
                $fileResult['status'] = 'success';
                $fileResult['message'] = 'File uploaded successfully';
                $fileResult['document_id'] = $document_id;
                $fileResult['data'] = [
                    'id' => $document_id,
                    'investor_id' => $investor_id,
                    'original_filename' => $originalFilename,
                    'stored_filename' => $storedFilename,
                    'file_path' => $relativePath,
                    'file_size' => $file['size'],
                    'file_type' => $fileType
                ];
                $successCount++;
                error_log("DEBUG: File $index saved to database with ID: $document_id");
            } else {
                // Remove uploaded file if database insert fails
                unlink($filePath);
                $fileResult['message'] = 'Failed to save file information to database: ' . $stmt->error;
                error_log("DEBUG: File $index database insert failed: " . $stmt->error);
                $errorCount++;
            }
            $stmt->close();
        } else {
            $fileResult['message'] = 'Failed to move uploaded file';
            error_log("DEBUG: File $index move failed");
            $errorCount++;
        }

        $results[] = $fileResult;
    }

    error_log("DEBUG: Processing complete. Success: $successCount, Errors: $errorCount, Total: " . count($files));

    return [
        'results' => $results,
        'success_count' => $successCount,
        'error_count' => $errorCount,
        'total_count' => count($files)
    ];
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
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $investor_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Investor not found']);
        exit;
    }
    $stmt->close();

    // Check if files were uploaded
    $filesField = 'documents';
    if (!isset($_FILES['documents']) && isset($_FILES['documents[]'])) {
        $filesField = 'documents[]';
    }

    if (!isset($_FILES[$filesField]) || $_FILES[$filesField]['error'] === UPLOAD_ERR_NO_FILE) {
        // Debug information
        error_log("DEBUG: _FILES contents: " . print_r($_FILES, true));
        error_log("DEBUG: POST contents: " . print_r($_POST, true));

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No files uploaded']);
        exit;
    }

    // Handle multiple files
    $uploadedFiles = [];

    // Debug information
    error_log("DEBUG: _FILES structure: " . print_r($_FILES, true));
    error_log("DEBUG: Using field: $filesField");
    error_log("DEBUG: Number of files received: " . (is_array($_FILES[$filesField]['name']) ? count($_FILES[$filesField]['name']) : 1));

    // Check if it's a single file or multiple files
    if (is_array($_FILES[$filesField]['name'])) {
        // Multiple files
        $fileCount = count($_FILES[$filesField]['name']);
        error_log("DEBUG: Processing $fileCount files");

        for ($i = 0; $i < $fileCount; $i++) {
            $uploadedFiles[] = [
                'name' => $_FILES[$filesField]['name'][$i],
                'type' => $_FILES[$filesField]['type'][$i],
                'tmp_name' => $_FILES[$filesField]['tmp_name'][$i],
                'error' => $_FILES[$filesField]['error'][$i],
                'size' => $_FILES[$filesField]['size'][$i]
            ];
            error_log("DEBUG: Added file $i: " . $_FILES[$filesField]['name'][$i]);
        }
    } else {
        // Single file
        error_log("DEBUG: Processing single file: " . $_FILES[$filesField]['name']);
        $uploadedFiles[] = $_FILES[$filesField];
    }

    error_log("DEBUG: Total files to process: " . count($uploadedFiles));

    // Process all files
    $uploadResults = processMultipleFiles($uploadedFiles, $investor_id, $conn);

    // Prepare response
    if ($uploadResults['error_count'] === 0) {
        // All files uploaded successfully
        echo json_encode([
            'status' => 'success',
            'message' => 'All files uploaded successfully',
            'data' => [
                'total_uploaded' => $uploadResults['success_count'],
                'files' => array_filter($uploadResults['results'], function ($result) {
                    return $result['status'] === 'success';
                })
            ]
        ]);
    } elseif ($uploadResults['success_count'] === 0) {
        // All files failed
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'All file uploads failed',
            'data' => [
                'total_failed' => $uploadResults['error_count'],
                'errors' => array_filter($uploadResults['results'], function ($result) {
                    return $result['status'] === 'error';
                })
            ]
        ]);
    } else {
        // Partial success
        echo json_encode([
            'status' => 'partial',
            'message' => 'Some files uploaded successfully',
            'data' => [
                'total_uploaded' => $uploadResults['success_count'],
                'total_failed' => $uploadResults['error_count'],
                'total_files' => $uploadResults['total_count'],
                'successful_files' => array_filter($uploadResults['results'], function ($result) {
                    return $result['status'] === 'success';
                }),
                'failed_files' => array_filter($uploadResults['results'], function ($result) {
                    return $result['status'] === 'error';
                })
            ]
        ]);
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
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database prepare failed: ' . $conn->error]);
        exit;
    }

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