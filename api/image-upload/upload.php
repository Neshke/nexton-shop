<?php
// Include CORS headers
require_once '../../utils/cors_headers.php';

// Define the upload directory
$uploadDir = __DIR__ . '/../uploads/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file was uploaded without errors
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tempFile = $_FILES['image']['tmp_name'];
        $fileName = isset($_POST['imageName']) && !empty($_POST['imageName']) 
            ? sanitizeFileName($_POST['imageName']) 
            : sanitizeFileName($_FILES['image']['name']);
            
        // Extract file extension and ensure it exists
        $fileNameParts = pathinfo($fileName);
        $extension = isset($fileNameParts['extension']) ? $fileNameParts['extension'] : '';
        
        // If no extension found, try to determine from mime type
        if (empty($extension)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tempFile);
            finfo_close($finfo);
            
            // Map common mime types to extensions
            $mime_map = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/bmp' => 'bmp',
                'image/webp' => 'webp'
            ];
            
            $extension = isset($mime_map[$mime]) ? $mime_map[$mime] : 'jpg';
        }
        
        // Create a filename with the extension
        $baseFileName = isset($fileNameParts['filename']) && !empty($fileNameParts['filename']) 
            ? $fileNameParts['filename'] 
            : 'image_' . time();
            
        $fileName = $baseFileName . '_' . time() . '.' . $extension;
        
        $targetFile = $uploadDir . $fileName;

        // Check file type
        $imageFileType = strtolower($extension);
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 400,
                'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.'
            ]);
            exit;
        }

        // Try to move the uploaded file
        if (move_uploaded_file($tempFile, $targetFile)) {
            $fileUrl = '/nexton/api/uploads/' . $fileName;
            http_response_code(201);
            echo json_encode([
                'status' => 201,
                'message' => 'File uploaded successfully',
                'data' => [
                    'url' => $fileUrl,
                    'filename' => $fileName
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'message' => 'Failed to upload file.'
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'status' => 400,
            'message' => 'No file uploaded or file upload error.'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 405,
        'message' => 'Method not allowed'
    ]);
}

// Function to sanitize filename
function sanitizeFileName($fileName) {
    // Remove special characters
    $fileName = preg_replace('/[^\w\._]+/', '_', $fileName);
    // Ensure filename is not too long
    return substr($fileName, 0, 100);
}
?>