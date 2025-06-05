<?php
// Include CORS headers
require_once '../../utils/cors_headers.php';

// Include database connection
include_once '../../config/database.php';
include_once '../../models/Category.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create category object
$category = new Category($db);

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get category ID from URL if provided
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
  case 'GET':
    // If ID is provided, get one category
    if ($id) {
      $category->id = $id;
      $result = $category->getOne();

      if ($result) {
        http_response_code(200);
        echo json_encode([
          'status' => 200,
          'message' => 'Category found',
          'data' => $result
        ]);
      } else {
        http_response_code(404);
        echo json_encode([
          'status' => 404,
          'message' => 'Category not found'
        ]);
      }
    } else {
      // Get all categories
      $results = $category->getAll();

      http_response_code(200);
      echo json_encode([
        'status' => 200,
        'message' => 'Categories found',
        'data' => $results
      ]);
    }
    break;

  case 'POST':
    // Create a category
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->name)) {
      $category->naziv = $data->name;
      $category->url_naziv = $category->createSlug($data->name);
      $category->opis = $data->description ?? '';
      $category->slika_url = $data->image_url ?? null;
      $category->aktivan = $data->is_active ?? 1;

      if ($category->create()) {
        http_response_code(201);
        echo json_encode([
          'status' => 201,
          'message' => 'Category created successfully',
          'data' => [
            'id' => $category->id,
            'naziv' => $category->naziv,
            'url_naziv' => $category->url_naziv
          ]
        ]);
      } else {
        http_response_code(500);
        echo json_encode([
          'status' => 500,
          'message' => 'Unable to create category'
        ]);
      }
    } else {
      http_response_code(400);
      echo json_encode([
        'status' => 400,
        'message' => 'Unable to create category. Data is incomplete.'
      ]);
    }
    break;

  case 'PUT':
    // Update a category
    if ($id) {
      $data = json_decode(file_get_contents("php://input"));

      $category->id = $id;
      $category->naziv = $data->name ?? null;
      $category->opis = $data->description ?? null;
      $category->slika_url = $data->image_url ?? null;
      $category->aktivan = $data->is_active ?? null;

      if ($category->update()) {
        http_response_code(200);
        echo json_encode([
          'status' => 200,
          'message' => 'Category updated successfully'
        ]);
      } else {
        http_response_code(500);
        echo json_encode([
          'status' => 500,
          'message' => 'Unable to update category'
        ]);
      }
    } else {
      http_response_code(400);
      echo json_encode([
        'status' => 400,
        'message' => 'Unable to update category. No ID provided.'
      ]);
    }
    break;

  case 'DELETE':
    // Delete a category
    if ($id) {
      $category->id = $id;

      if ($category->delete()) {
        http_response_code(200);
        echo json_encode([
          'status' => 200,
          'message' => 'Category deleted successfully'
        ]);
      } else {
        http_response_code(500);
        echo json_encode([
          'status' => 500,
          'message' => 'Unable to delete category'
        ]);
      }
    } else {
      http_response_code(400);
      echo json_encode([
        'status' => 400,
        'message' => 'Unable to delete category. No ID provided.'
      ]);
    }
    break;

  default:
    http_response_code(405);
    echo json_encode([
      'status' => 405,
      'message' => 'Method not allowed'
    ]);
    break;
}
?>