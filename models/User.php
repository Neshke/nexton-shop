<?php
class User
{
    // Database connection and table name
    private $conn;
    private $table_name = "users";

    // Object properties
    public $id;
    public $username;
    public $email;
    public $password;
    public $role;
    public $created_at;

    // Constructor with database connection
    public function __construct($database)
    {
        $this->conn = $database;
    }    /**
     * Get user by ID
     */
    public function getUserById($user_id)
    {
        try {
            $query = "SELECT id, username, email, role, created_at 
                     FROM " . $this->table_name . " 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Map database fields to object properties
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->created_at = $row['created_at'];

                return $row;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            return false;
        }
    }    /**
     * Get user by email
     */
    public function getUserByEmail($email)
    {
        try {
            $query = "SELECT id, username, email, role, created_at 
                     FROM " . $this->table_name . " 
                     WHERE email = :email";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error fetching user by email: " . $e->getMessage());
            return false;
        }
    }    /**
     * Update user profile
     */
    public function updateProfile($user_id, $data)
    {
        try {
            // Build dynamic update query based on provided data
            $updateFields = [];
            $bindParams = [];
            
            // Handle username field - accept both 'name' and 'username' from frontend
            if (isset($data['name']) || isset($data['username'])) {
                $updateFields[] = "username = :username";
                $bindParams['username'] = isset($data['name']) ? $data['name'] : $data['username'];
            }
            
            // Handle email field
            if (isset($data['email'])) {
                $updateFields[] = "email = :email";
                $bindParams['email'] = $data['email'];
            }

            if (empty($updateFields)) {
                return false; // No valid fields to update
            }

            $query = "UPDATE " . $this->table_name . " 
                     SET " . implode(', ', $updateFields) . " 
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            
            // Bind the user ID
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            
            // Bind the update data
            foreach ($bindParams as $param => $value) {
                $stmt->bindParam(":$param", $bindParams[$param]);
            }

            if ($stmt->execute()) {
                return $stmt->rowCount() > 0; // Return true if any rows were affected
            }

            return false;
        } catch (PDOException $e) {
            error_log("Error updating user profile: " . $e->getMessage());
            return false;
        }
    }    /**
     * Validate user data for profile update
     */
    public function validateProfileData($data)
    {
        $errors = [];

        // Validate username
        if (isset($data['username'])) {
            if (empty(trim($data['username'])) || strlen(trim($data['username'])) < 3) {
                $errors['username'] = 'Username mora imati najmanje 3 karaktera';
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                $errors['username'] = 'Username može sadržati samo slova, brojeve i donju crtu';
            }
        }

        // Validate email
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Neispravna email adresa';
            }
        }

        return $errors;
    }

    /**
     * Check if user exists by ID
     */
    public function userExists($user_id)
    {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking if user exists: " . $e->getMessage());
            return false;
        }
    }    /**
     * Get user's display name (username for now)
     */
    public function getDisplayName()
    {
        return $this->username;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }    /**
     * Sanitize user data for safe output
     */
    public function sanitizeUserData($user_data)
    {
        if (!$user_data || !is_array($user_data)) {
            return null;
        }

        // Remove sensitive fields and sanitize output
        $safe_data = [
            'id' => (int)$user_data['id'],
            'username' => htmlspecialchars($user_data['username'] ?? '', ENT_QUOTES, 'UTF-8'),
            'email' => htmlspecialchars($user_data['email'] ?? '', ENT_QUOTES, 'UTF-8'),
            'role' => htmlspecialchars($user_data['role'] ?? '', ENT_QUOTES, 'UTF-8'),
            'created_at' => $user_data['created_at'] ?? null,
            // Add 'name' field for frontend compatibility
            'name' => htmlspecialchars($user_data['username'] ?? '', ENT_QUOTES, 'UTF-8')
        ];

        return $safe_data;
    }    /**
     * Change user password
     */
    public function changePassword($user_id, $currentPassword, $newPassword)
    {
        try {
            // First, verify the current password
            $query = "SELECT password FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return false; // User not found
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $storedPasswordHash = $row['password'];

            // Verify current password
            if (!password_verify($currentPassword, $storedPasswordHash)) {
                return false; // Current password is incorrect
            }

            // Hash the new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password in database
            $updateQuery = "UPDATE " . $this->table_name . " SET password = :new_password WHERE id = :id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':new_password', $newPasswordHash);
            $updateStmt->bindParam(':id', $user_id, PDO::PARAM_INT);

            return $updateStmt->execute();

        } catch (PDOException $e) {
            error_log("Error changing password: " . $e->getMessage());
            return false;
        }
    }
}
?>
