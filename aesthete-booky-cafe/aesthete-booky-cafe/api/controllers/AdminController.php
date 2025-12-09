<?php
require_once __DIR__ . '/../config/Database.php';

class AdminController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // --- BOOKS ---
    public function addBook($data) {
        $query = "INSERT INTO books (title, author, price, image, description) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$data->title, $data->author, $data->price, $data->image, $data->description])) {
            return ['message' => 'Book added successfully'];
        }
        return ['message' => 'Failed to add book'];
    }

    public function updateBook($data) {
        $query = "UPDATE books SET title = ?, author = ?, price = ?, image = ?, description = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$data->title, $data->author, $data->price, $data->image, $data->description, $data->id])) {
            return ['message' => 'Book updated successfully'];
        }
        return ['message' => 'Failed to update book'];
    }

    public function deleteBook($id) {
        $query = "DELETE FROM books WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$id])) {
            return ['message' => 'Book deleted successfully'];
        }
        return ['message' => 'Failed to delete book'];
    }

    // --- MENUS ---
    public function addMenu($data) {
        $query = "INSERT INTO menu_items (name, category, price, image, description) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$data->name, $data->category, $data->price, $data->image, $data->description])) {
            return ['message' => 'Menu item added successfully'];
        }
        return ['message' => 'Failed to add menu item'];
    }

    public function updateMenu($data) {
        $query = "UPDATE menu_items SET name = ?, category = ?, price = ?, image = ?, description = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$data->name, $data->category, $data->price, $data->image, $data->description, $data->id])) {
            return ['message' => 'Menu item updated successfully'];
        }
        return ['message' => 'Failed to update menu item'];
    }

    public function deleteMenu($id) {
        $query = "DELETE FROM menu_items WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$id])) {
            return ['message' => 'Menu item deleted successfully'];
        }
        return ['message' => 'Failed to delete menu item'];
    }

    // --- EVENTS ---
    public function addEvent($data) {
        $capacity = $data->capacity ?? 50;
        $query = "INSERT INTO events (title, date, location, image, description, capacity) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        try {
            if ($stmt->execute([$data->title, $data->date, $data->location, $data->image, $data->description, $capacity])) {
                return ['message' => 'Event added successfully'];
            }
        } catch (PDOException $e) {
            file_put_contents(__DIR__ . '/../../db_debug.txt', "AddEvent SQL Error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        // Log generic failure if no exception but false returned (rare with PDO::ERRMODE_EXCEPTION but possible)
        file_put_contents(__DIR__ . '/../../db_debug.txt', "AddEvent Failed: " . print_r($stmt->errorInfo(), true) . "\n", FILE_APPEND);
        return ['message' => 'Failed to add event'];
    }

    public function updateEvent($data) {
         $capacity = $data->capacity ?? 50;
         $query = "UPDATE events SET title = ?, date = ?, location = ?, image = ?, description = ?, capacity = ? WHERE id = ?";
         $stmt = $this->conn->prepare($query);
         try {
             if ($stmt->execute([$data->title, $data->date, $data->location, $data->image, $data->description, $capacity, $data->id])) {
                 return ['message' => 'Event updated successfully'];
             }
         } catch (PDOException $e) {
             file_put_contents(__DIR__ . '/../../db_debug.txt', "UpdateEvent SQL Error: " . $e->getMessage() . "\n", FILE_APPEND);
         }
         return ['message' => 'Failed to update event'];
    }

    public function deleteEvent($id) {
        $query = "DELETE FROM events WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$id])) {
            return ['message' => 'Event deleted successfully'];
        }
        return ['message' => 'Failed to delete event'];
    }

    // --- USERS ---
    public function getUsers() {
        $query = "SELECT id, name, email, phone, role, is_verified, is_banned, created_at FROM users ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as &$user) {
            $user['member_id'] = 'Aesthetic' . str_pad($user['id'], 5, '0', STR_PAD_LEFT);
        }

        return $users;
    }
    
    public function deleteUser($id) {
        // Prevent deleting self? Frontend should handle, but backend safe check ideal
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$id])) {
            return ['message' => 'User deleted successfully'];
        }
        return ['message' => 'Failed to delete user'];
    }
    
    public function toggleBan($data) {
        $id = $data->id;
        $status = $data->is_banned ? '1' : '0';
        
        $query = "UPDATE users SET is_banned = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$status, $id])) {
            return ['message' => 'User status updated'];
        }
        return ['message' => 'Failed to update status'];
    }

    public function addUser($data) {
        // Basic Validation
        if (empty($data->name) || empty($data->email) || empty($data->password)) {
             return ['message' => 'Missing required fields'];
        }

        // Check availability
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data->email]);
        if ($stmt->rowCount() > 0) return ['message' => 'Email already exists'];

        $password = password_hash($data->password, PASSWORD_DEFAULT);
        $role = $data->role === 'admin' ? 'admin' : 'member';
        
        // Auto-verify admins created by admins
        $isVerified = 1;

        $query = "INSERT INTO users (name, email, password, role, is_verified) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute([$data->name, $data->email, $password, $role, $isVerified])) {
            return ['message' => 'User created successfully'];
        }
        return ['message' => 'Failed to create user'];
    }

    public function updateUser($data) {
        $id = $data->id;
        $name = $data->name;
        $email = $data->email;
        $phone = $data->phone ?? null;
        $role = $data->role;
        $password = $data->password ?? null;

        // Validation
        if (!$id || !$name || !$email || !$role) {
             return ['message' => 'Missing required fields'];
        }

        if ($password) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET name = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?";
            $params = [$name, $email, $phone, $role, $hashed, $id];
        } else {
            $query = "UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?";
            $params = [$name, $email, $phone, $role, $id];
        }

        $stmt = $this->conn->prepare($query);
        if ($stmt->execute($params)) {
             return ['message' => 'User updated successfully'];
        }
        return ['message' => 'Failed to update user'];
    }


    
    // --- CANCELLATIONS ---
    public function getCancellations() {
        $query = "
            SELECT r.id as registration_id, r.user_id, r.event_id, r.guests, r.status, r.cancellation_reason, r.created_at,
                   u.name as user_name, u.email as user_email,
                   e.title as event_title
            FROM registrations r
            JOIN users u ON r.user_id = u.id
            JOIN events e ON r.event_id = e.id
            WHERE r.status = 'pending_cancellation'
            ORDER BY r.created_at ASC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function handleCancellation($data) {
        $regId = $data->registration_id;
        $action = $data->action; // 'approve' or 'reject'
        
        if (!$regId || !$action) return ['message' => 'Missing ID or Action'];
        
        $newStatus = ($action === 'approve') ? 'cancelled' : 'confirmed';
        
        $query = "UPDATE registrations SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$newStatus, $regId])) {
            return ['message' => 'Cancellation ' . $action . 'd successfully'];
        }
        return ['message' => 'Failed to update status'];
    }

    public function getEventRegistrations($eventId) {
        $stmt = $this->conn->prepare("
            SELECT r.*, 
                   IFNULL(r.contact_name, u.name) as name, 
                   IFNULL(r.contact_email, u.email) as email,
                   IFNULL(r.contact_phone, u.phone) as phone
            FROM registrations r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.event_id = ?
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelBookingByAdmin($data) {
        $regId = $data->registration_id;
        $reason = $data->reason; // Admin should provide a reason
        
        if (!$regId) return ['message' => 'Registration ID required'];
        
        $query = "UPDATE registrations SET status = 'cancelled', cancellation_reason = ?, cancelled_by = 'admin' WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$reason, $regId])) {
            return ['message' => 'Booking cancelled by admin'];
        }
        return ['message' => 'Failed to cancel booking'];
    }

    // --- UPLOAD ---
    // --- UPLOAD ---
    public function uploadImage() {
        // DEBUG LOG
        $log = __DIR__ . '/../../upload_debug.txt';
        // file_put_contents($log, "Upload Init: " . print_r($_FILES, true) . "\n", FILE_APPEND);

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $err = $_FILES['image']['error'] ?? 4; // 4 = No file
            $msg = 'Unknown upload error';
            if ($err == UPLOAD_ERR_INI_SIZE || $err == UPLOAD_ERR_FORM_SIZE) {
                $msg = 'File is too large (Server Limit).';
            } elseif ($err == UPLOAD_ERR_PARTIAL) {
                $msg = 'File was only partially uploaded.';
            } elseif ($err == UPLOAD_ERR_NO_FILE) {
                $msg = 'No file was uploaded.';
            }
            file_put_contents($log, "Upload Error Code $err: $msg\n", FILE_APPEND);
            http_response_code(400);
            return ['message' => $msg];
        }

        $targetDir = __DIR__ . '/../../public/images/uploads/';
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        // Generate unique filename to avoid partial matches on old names
        $originalName = basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $fileName = uniqid() . '.' . $imageFileType;
        $targetFile = $targetDir . $fileName;

        // Validations
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check === false) {
             return ['message' => 'File is not an image.'];
        }
        
        // 25MB Limit (25 * 1024 * 1024)
        if ($_FILES['image']['size'] > 26214400) {
             return ['message' => 'File is too large (> 25MB).'];
        }
        
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "webp") {
             return ['message' => 'Only JPG, JPEG, PNG, GIF, & WEBP files are allowed.'];
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // Return relative URL for frontend
            return ['url' => 'images/uploads/' . $fileName];
        } else {
            file_put_contents($log, "Move failed. Error: " . $_FILES['image']['error'] . "\n", FILE_APPEND);
            return ['message' => 'Error uploading file.'];
        }
    }

    public function adminAddBooking($data) {
        $event_id = $data->event_id ?? null;
        $name = $data->name ?? null;
        $email = $data->email ?? null;
        $phone = $data->phone ?? null;
        $guests = $data->guests ?? 1;

        if (!$event_id || !$name || !$email) {
            http_response_code(400); 
            return ['message' => 'Event ID, Name, and Email required'];
        }

        // Check if user exists, else create placeholder or just store in overrides?
        // Logic: Try to find user by email. If not found, create a "Guest User" or require them to register?
        // Simplest for Admin Manual Add: find user by email OR create a shadow user OR just use ID=0 or NULL?
        // Schema requires user_id. Let's find or create.
        
        $uStmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $uStmt->execute([$email]);
        $uid = $uStmt->fetchColumn();

        if (!$uid) {
            // Create a quick user or error?
            // "Admin Book" usually implies for existing users OR walking-ins.
            // Let's create a walker-in account implicitly? Or just fail.
            // Safe bet: Fail and ask to create user first? Or Auto-create.
            // Let's Auto-create a member with random password.
            $password = password_hash(uniqid(), PASSWORD_DEFAULT);
            $cStmt = $this->conn->prepare("INSERT INTO users (name, email, phone, password, role, is_verified) VALUES (?, ?, ?, ?, 'member', 1)");
            $cStmt->execute([$name, $email, $phone, $password]);
            $uid = $this->conn->lastInsertId();
        }

        // Register
        $query = "INSERT INTO registrations (user_id, event_id, guests, contact_name, contact_email, contact_phone, status, created_at) 
                  VALUES (:uid, :eid, :guests, :cname, :cemail, :cphone, 'confirmed', NOW())";
        $stmt = $this->conn->prepare($query);
        $res = $stmt->execute([
            'uid' => $uid,
            'eid' => $event_id,
            'guests' => $guests,
            'cname' => $name,
            'cemail' => $email,
            'cphone' => $phone
        ]);

        if ($res) return ['message' => 'Attendee added successfully'];
        return ['message' => 'Failed to add attendee'];
    }

    public function adminCancelAllBookings($data) {
        $event_id = $data->event_id ?? null;
        $reason = $data->reason ?? 'Event Cancelled by Admin';

        if (!$event_id) return ['message' => 'Event ID required'];

        $query = "UPDATE registrations SET status = 'cancelled', cancellation_reason = ?, cancelled_by = 'admin' WHERE event_id = ? AND status != 'cancelled'";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$reason, $event_id])) {
            return ['message' => 'All bookings cancelled'];
        }
        return ['message' => 'Failed to cancel bookings'];
    }

    public function approveBooking($data) {
        $id = $data->registration_id ?? null;
        if (!$id) return ['message' => 'ID required'];

        $stmt = $this->conn->prepare("UPDATE registrations SET status = 'confirmed' WHERE id = ?");
        if ($stmt->execute([$id])) {
            return ['message' => 'Booking approved'];
        }
        return ['message' => 'Failed to approve'];
    }
}
