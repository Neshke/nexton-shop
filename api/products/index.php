<?php
// Include CORS headers first, before any output
require_once '../../utils/cors_headers.php';

// Include database connection
include_once '../../config/database.php';
include_once '../../models/Product.php';
include_once '../../models/Category.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create product object
$product = new Product($db);
// Create category object for validation
$category = new Category($db);

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get product ID from URL if provided
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;

switch ($method) {
  case 'GET':
    // If ID is provided, get one product
    if ($id) {
      $product->id = $id;
      $result = $product->getOne();

      if ($result) {
        http_response_code(200);
        echo json_encode([
          'status' => 200,
          'message' => 'Product found',
          'data' => $result
        ]);
      } else {
        http_response_code(404);
        echo json_encode([
          'status' => 404,
          'message' => 'Product not found'
        ]);
      }
    }
    // If category_id is provided, get products by category
    else if ($category_id) {
      $product->kategorija_id = $category_id;
      $results = $product->getByCategory();

      http_response_code(200);
      echo json_encode([
        'status' => 200,
        'message' => 'Products found',
        'data' => $results
      ]);
    }
    // Otherwise get all products
    else {
      $results = $product->getAll();

      http_response_code(200);
      echo json_encode([
        'status' => 200,
        'message' => 'Products found',
        'data' => $results
      ]);
    }
    break;

  case 'POST':
    // Create a product
    $data = json_decode(file_get_contents("php://input"));

    if (
      !empty($data->naziv) &&
      !empty($data->kategorija_id) &&
      !empty($data->cena)
    ) {
      // Validate that the category exists before creating the product
      $kategorija_id = intval($data->kategorija_id);
      $category->id = $kategorija_id;
      $category_exists = $category->getOne();
      
      if (!$category_exists) {
        http_response_code(400);
        echo json_encode([
          'status' => 400,
          'message' => 'Category ID does not exist. Please select a valid category.'
        ]);
        break;
      }
      
      $product->naziv = $data->naziv;
      $product->slug = $product->createSlug($data->naziv);
      $product->opis = $data->opis ?? '';
      $product->cena = $data->cena;
      $product->kategorija_id = $kategorija_id;
      $product->kolicina_na_stanju = $data->kolicina_na_stanju ?? 0;
      $product->slika_url = $data->slika_url ?? null;
      $product->istaknuto = $data->istaknuto ?? 0;
      $product->aktivan = $data->aktivan ?? 1;

      try {
        if ($product->create()) {
          http_response_code(201);
          echo json_encode([
            'status' => 201,
            'message' => 'Product created successfully',
            'data' => [
              'id' => $product->id,
              'naziv' => $product->naziv,
              'slug' => $product->slug
            ]
          ]);
        } else {
          http_response_code(500);
          echo json_encode([
            'status' => 500,
            'message' => 'Unable to create product'
          ]);
        }
      } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
          'status' => 500,
          'message' => 'Database error: ' . $e->getMessage()
        ]);
      }
    } else {
      http_response_code(400);
      echo json_encode([
        'status' => 400,
        'message' => 'Unable to create product. Data is incomplete.'
      ]);
    }
    break;

  case 'PUT':
    // Update a product
    if ($id) {
      $data = json_decode(file_get_contents("php://input"));

      // First, check if the product exists
      $product->id = $id;
      $existingProduct = $product->getOne(); // Assuming getOne() fetches current product data

      if (!$existingProduct) {
        http_response_code(404);
        echo json_encode([
          'status' => 404,
          'message' => 'Product not found for update.'
        ]);
        break;
      }

      // Validate category_id if provided and changed
      if (isset($data->kategorija_id) && $data->kategorija_id !== $existingProduct['kategorija_id']) {
        $category->id = intval($data->kategorija_id);
        if (!$category->getOne()) {
          http_response_code(400);
          echo json_encode([
            'status' => 400,
            'message' => 'Category ID does not exist. Please select a valid category.'
          ]);
          break;
        }
        $product->kategorija_id = intval($data->kategorija_id);
      } else {
        // Keep existing category_id if not provided in $data or not changed
        $product->kategorija_id = $existingProduct['kategorija_id'];
      }
      
      $product->id = $id; // Ensure ID is set on the product object for update

      // Handle name and slug update
      if (isset($data->naziv) && $data->naziv !== $existingProduct['naziv']) {
        $product->naziv = $data->naziv;
        $product->slug = $product->createSlug($data->naziv); // Regenerate slug
      } else {
        $product->naziv = $existingProduct['naziv']; // Keep existing if not provided
        $product->slug = $existingProduct['slug'];   // Keep existing slug
      }

      $product->opis = $data->opis ?? $existingProduct['opis'];
      $product->cena = $data->cena ?? $existingProduct['cena'];
      // kategorija_id is handled above
      $product->kolicina_na_stanju = $data->kolicina_na_stanju ?? $existingProduct['kolicina_na_stanju'];
      $product->slika_url = $data->slika_url ?? $existingProduct['slika_url']; // Frontend handles image deletion/upload separately
      $product->istaknuto = $data->istaknuto ?? $existingProduct['istaknuto'];
      $product->aktivan = $data->aktivan ?? $existingProduct['aktivan'];
      // akcijska_cena is not explicitly handled here, assuming it might be part of $data or managed elsewhere
      $product->akcijska_cena = $data->akcijska_cena ?? $existingProduct['akcijska_cena'];


      if ($product->update()) {
        http_response_code(200);
        echo json_encode([
          'status' => 200,
          'message' => 'Product updated successfully'
        ]);
      } else {
        http_response_code(500);
        echo json_encode([
          'status' => 500,
          'message' => 'Unable to update product'
        ]);
      }
    } else {
      http_response_code(400);
      echo json_encode([
        'status' => 400,
        'message' => 'Unable to update product. No ID provided.'
      ]);
    }
    break;

  case 'DELETE':
    try {
      // Add authentication middleware check for admin role
      require_once '../../utils/auth_middleware.php';
      
      // Debug headers
      $headers = getallheaders();
      $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 'Not provided';
      error_log("DELETE request received. Auth header: " . substr($authHeader, 0, 20) . "...");
      
      // Check if the request has an Authorization header before requireAdmin()
      if (!isset($headers['Authorization']) || empty($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode([
          'status' => 401,
          'message' => 'Unauthorized - Authentication token missing',
          'help' => 'Please include an Authorization header with a valid token (Bearer token)'
        ]);
        exit;
      }
      
      requireAdmin(); // This will exit if user is not admin
      
      if ($id) {
        $product->id = $id;
        
        // Get the current product to get the image URL
        $currentProduct = $product->getOne();
        
        if ($currentProduct) {
          // Set the image URL to delete
          $product->slika_url = $currentProduct['slika_url'] ?? '';
          
          // Delete the image file first
          $imageDeleted = $product->deleteProductImage();
          
          // Now delete the product from the database
          if ($product->delete()) {
            http_response_code(200);
            echo json_encode([
              'status' => 200,
              'message' => 'Product deleted successfully' . 
                ($imageDeleted ? '' : ' (Note: Unable to delete associated image file)')
            ]);
          } else {
            http_response_code(500);
            echo json_encode([
              'status' => 500,
              'message' => 'Unable to delete product'
            ]);
          }
        } else {
          http_response_code(404);
          echo json_encode([
            'status' => 404,
            'message' => 'Product not found'
          ]);
        }
      } else {
        http_response_code(400);
        echo json_encode([
          'status' => 400,
          'message' => 'Unable to delete product. No ID provided.'
        ]);
      }
    } catch (Exception $e) {
      error_log("Error in DELETE endpoint: " . $e->getMessage());
      http_response_code(500);
      echo json_encode([
        'status' => 500,
        'message' => 'Server error: ' . $e->getMessage()
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