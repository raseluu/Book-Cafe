<?php
require_once __DIR__ . '/../config/Database.php';

class UserController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // --- CANCELLATION ---
    public function requestCancellation($data) {
        $regId = $data->registration_id;
        $reason = $data->reason;
        
        if (!$regId || !$reason) {
            http_response_code(400); 
            return ['message' => 'Registration ID and Reason required.'];
        }
        
        // Verify ownership? (Assume auth middleware handles user context, but ideally check here)
        // For simplicity, directly update
        $query = "UPDATE registrations SET status = 'pending_cancellation', cancellation_reason = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if ($stmt->execute([$reason, $regId])) {
            return ['message' => 'Cancellation request sent. Waiting for approval.'];
        }
        return ['message' => 'Failed to request cancellation.'];
    }

    public function registerEvent($data) {
        $user_id = $data->user_id;
        $event_id = $data->event_id;
        if (!$user_id || !$event_id) {
            return ['message' => 'Missing required fields'];
        }

        $guests = isset($data->guests) ? (int)$data->guests : 1;
        $contact_name = isset($data->contact_name) ? $data->contact_name : null;
        $contact_email = isset($data->contact_email) ? $data->contact_email : null;
        $contact_phone = isset($data->contact_phone) ? $data->contact_phone : null;

        // Validations
        if ($guests < 1) $guests = 1;

        // Check Event Existence & Date
        $stmt = $this->conn->prepare("
            SELECT e.*, 
                   IFNULL((SELECT SUM(guests) FROM registrations WHERE event_id = e.id AND status = 'confirmed'), 0) as booked
            FROM events e 
            WHERE e.id = :eid
        ");
        $stmt->execute(['eid' => $event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            http_response_code(404);
            return ['message' => 'Event not found.'];
        }

        // 1. Check if Past Event
        $eventDate = new DateTime($event['date']);
        $now = new DateTime();
        if ($eventDate < $now) {
             http_response_code(400); 
             return ['message' => 'Cannot register for past events.'];
        }

        // 2. Check if already registered (any status except cancelled)
        $chk = $this->conn->prepare("SELECT status FROM registrations WHERE user_id = :uid AND event_id = :eid AND status != 'cancelled'");
        $chk->execute(['uid' => $user_id, 'eid' => $event_id]);
        if ($chk->rowCount() > 0) {
            $existing = $chk->fetch(PDO::FETCH_ASSOC);
            http_response_code(400);
            return ['message' => 'You are already registered (' . $existing['status'] . ').'];
        }

        // 3. Determine Status (Waitlist vs Confirmed)
        $status = 'confirmed';
        $message = 'Registration Successful!';
        
        if (($event['booked'] + $guests) > $event['capacity']) {
            $status = 'waitlist';
            $message = 'Event is full. You have been added to the Waitlist.';
        }

        // Register
        $query = "INSERT INTO registrations (user_id, event_id, guests, contact_name, contact_email, contact_phone, status, created_at) 
                  VALUES (:uid, :eid, :guests, :cname, :cemail, :cphone, :status, NOW())";
        $stmt = $this->conn->prepare($query);
        $res = $stmt->execute([
            'uid' => $user_id, 
            'eid' => $event_id, 
            'guests' => $guests,
            'cname' => $contact_name,
            'cemail' => $contact_email,
            'cphone' => $contact_phone,
            'status' => $status
        ]);

        if ($res) {
            return ['message' => $message, 'status' => $status];
        } else {
            http_response_code(500);
            return ['message' => 'Registration failed.'];
        }
    }

    public function getUserRegistrations($user_id) {
        if (!$user_id) {
            http_response_code(400);
            return ['message' => 'User ID is required.'];
        }

        // Include id of registration to allow cancellation
        $query = "
            SELECT r.id as registration_id, e.*, r.created_at as registration_date, r.status, r.cancellation_reason, r.cancelled_by 
            FROM events e 
            JOIN registrations r ON e.id = r.event_id 
            WHERE r.user_id = ? 
            ORDER BY e.date ASC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function requestEmailChangeOTP($data) {
        $id = $data->user_id ?? null;
        if (!$id) return ['message' => 'User ID required.'];

        // Fetch current email
        $stmt = $this->conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            return ['message' => 'User not found.'];
        }

        $code = rand(100000, 999999);
        $update = $this->conn->prepare("UPDATE users SET verification_code = ? WHERE id = ?");
        if ($update->execute([$code, $id])) {
            Mail::send($user['email'], "Verify Email Update", "Your verification code to change your email is: <b>$code</b>");
            return ['message' => 'Verification code sent to your current email.'];
        }
        
        http_response_code(500);
        return ['message' => 'Failed to send code.'];
    }

    public function updateProfile($data) {
        $id = $data->id ?? null;
        if (!$id) {
            http_response_code(400); 
            return ['message' => 'User ID required.'];
        }

        $name = $data->name ?? null;
        $email = $data->email ?? null;
        $phone = $data->phone ?? null;
        $newPassword = $data->new_password ?? null;
        $oldPassword = $data->old_password ?? null;
        $otp = $data->otp ?? null;

        // Fetch current user data
        $currQ = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $currQ->execute([$id]);
        $currentUser = $currQ->fetch(PDO::FETCH_ASSOC);

        if (!$currentUser) {
            http_response_code(404);
            return ['message' => 'User not found.'];
        }

        // 1. Handle Basic Info Update (Name, Phone)
        // Email is special, handled below
        if ($name) $currentUser['name'] = $name;
        if ($phone) $currentUser['phone'] = $phone;

        // 2. Handle Email Change
        if ($email && $email !== $currentUser['email']) {
            // Require OTP
            if (!$otp) {
                http_response_code(403);
                return ['message' => 'Verification code required to change email.'];
            }
            if ($currentUser['verification_code'] != $otp) {
                http_response_code(403);
                return ['message' => 'Invalid verification code.'];
            }
            // Code valid, update email
            $currentUser['email'] = $email;
            // Clear code
            $clearCode = $this->conn->prepare("UPDATE users SET verification_code = NULL WHERE id = ?");
            $clearCode->execute([$id]);
        }

        // Update Basic Info + Email
        $updateQ = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
        $uStmt = $this->conn->prepare($updateQ);
        if (!$uStmt->execute([$currentUser['name'], $currentUser['email'], $currentUser['phone'], $id])) {
             http_response_code(500);
             return ['message' => 'Failed to update profile info.'];
        }

        // 3. Handle Password Update
        if ($newPassword && strlen($newPassword) >= 6) {
             if (!$oldPassword) {
                 http_response_code(400); 
                 return ['message' => 'Current password is required to set a new one.'];
             }

             if (!password_verify($oldPassword, $currentUser['password'])) {
                 http_response_code(403);
                 return ['message' => 'Current password is incorrect.'];
             }

             $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
             $passQuery = "UPDATE users SET password = ? WHERE id = ?";
             $pStmt = $this->conn->prepare($passQuery);
             $pStmt->execute([$hashed, $id]);
        }

        // Fetch fresh object to return
        $finalQ = "SELECT id, name, email, phone, role FROM users WHERE id = ?";
        $fStmt = $this->conn->prepare($finalQ);
        $fStmt->execute([$id]);
        $finalUser = $fStmt->fetch(PDO::FETCH_ASSOC);

        if ($finalUser) {
            $finalUser['member_id'] = 'Aesthetic' . str_pad($finalUser['id'], 5, '0', STR_PAD_LEFT);
        }

        return ['message' => 'Profile updated successfully.', 'user' => $finalUser];
    }
}
