<?php
/**
 * User - Entity class for user management
 * Handles user data, authentication, and profile operations
 */
class User extends BaseEntity
{
    protected $table = 'users';
    protected $userId;
    protected $roleId;
    protected $departmentId;
    protected $username;
    protected $firstName;
    protected $lastName;
    protected $middleName;
    protected $email;
    protected $mobile;
    protected $address;
    protected $profileImage;
    protected $passwordHash;
    protected $verificationCode;
    protected $verificationStatus;
    protected $status;

    // Getters
    public function getUserId() { return $this->userId; }
    public function getRoleId() { return $this->roleId; }
    public function getDepartmentId() { return $this->departmentId; }
    public function getUsername() { return $this->username; }
    public function getFirstName() { return $this->firstName; }
    public function getLastName() { return $this->lastName; }
    public function getMiddleName() { return $this->middleName; }
    public function getEmail() { return $this->email; }
    public function getMobile() { return $this->mobile; }
    public function getAddress() { return $this->address; }
    public function getProfileImage() { return $this->profileImage; }
    public function getVerificationStatus() { return $this->verificationStatus; }
    public function getStatus() { return $this->status; }

    // Setters
    public function setUserId($userId) { $this->userId = $userId; }
    public function setRoleId($roleId) { $this->roleId = $roleId; }
    public function setDepartmentId($departmentId) { $this->departmentId = $departmentId; }
    public function setUsername($username) { $this->username = $username; }
    public function setFirstName($firstName) { $this->firstName = $firstName; }
    public function setLastName($lastName) { $this->lastName = $lastName; }
    public function setMiddleName($middleName) { $this->middleName = $middleName; }
    public function setEmail($email) { $this->email = $email; }
    public function setMobile($mobile) { $this->mobile = $mobile; }
    public function setAddress($address) { $this->address = $address; }
    public function setProfileImage($profileImage) { $this->profileImage = $profileImage; }
    public function setVerificationStatus($status) { $this->verificationStatus = $status; }
    public function setStatus($status) { $this->status = $status; }

    /**
     * Hash a password for storage
     * @param string $password
     * @return string Hashed password
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password against hash
     * @param string $password Plain text password
     * @return bool
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->passwordHash);
    }

    /**
     * Set password and hash it
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->passwordHash = $this->hashPassword($password);
    }

    /**
     * Find user by email
     * @param string $email
     * @return User|null
     */
    public static function findByEmail($email, $conn)
    {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $user = new self($conn);
            $user->fromArray($data);
            $user->setUserId($data['user_id']);
            $user->setRoleId($data['role_id']);
            $user->setDepartmentId($data['department_id']);
            $user->setUsername($data['username']);
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            $user->setMiddleName($data['middle_name']);
            $user->setEmail($data['email']);
            $user->setMobile($data['mobile']);
            $user->setAddress($data['address']);
            $user->setProfileImage($data['profile_image']);
            $user->setVerificationStatus($data['verification_status']);
            $user->setStatus($data['status']);
            return $user;
        }
        return null;
    }

    /**
     * Find user by username
     * @param string $username
     * @return User|null
     */
    public static function findByUsername($username, $conn)
    {
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $user = new self($conn);
            $user->setUserId($data['user_id']);
            $user->setRoleId($data['role_id']);
            $user->setDepartmentId($data['department_id']);
            $user->setUsername($data['username']);
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            $user->setMiddleName($data['middle_name']);
            $user->setEmail($data['email']);
            $user->setMobile($data['mobile']);
            $user->setAddress($data['address']);
            $user->setProfileImage($data['profile_image']);
            $user->setVerificationStatus($data['verification_status']);
            $user->setStatus($data['status']);
            return $user;
        }
        return null;
    }

    /**
     * Find user by ID
     * @param int $userId
     * @return User|null
     */
    public static function findById($userId, $conn)
    {
        $query = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $user = new self($conn);
            $user->setUserId($data['user_id']);
            $user->setRoleId($data['role_id']);
            $user->setDepartmentId($data['department_id']);
            $user->setUsername($data['username']);
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            $user->setMiddleName($data['middle_name']);
            $user->setEmail($data['email']);
            $user->setMobile($data['mobile']);
            $user->setAddress($data['address']);
            $user->setProfileImage($data['profile_image']);
            $user->setVerificationStatus($data['verification_status']);
            $user->setStatus($data['status']);
            return $user;
        }
        return null;
    }

    /**
     * Get all users
     * @return array
     */
    public static function getAll($conn)
    {
        $query = "SELECT * FROM users";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get full name
     * @return string
     */
    public function getFullName()
    {
        return trim("{$this->firstName} {$this->middleName} {$this->lastName}");
    }

    /**
     * Verify user email
     * @return bool
     */
    public function verify()
    {
        $query = "UPDATE users SET verification_status = 'Verified', status = 'active' WHERE user_id = ?";
        return $this->executeQuery($query, [$this->userId]) !== false;
    }

    /**
     * Save user to database
     * @return bool
     */
    public function save()
    {
        if ($this->userId) {
            // Update existing user
            $query = "UPDATE users SET role_id = ?, department_id = ?, username = ?, first_name = ?, 
                      last_name = ?, middle_name = ?, email = ?, mobile = ?, address = ?, 
                      profile_image = ?, status = ? WHERE user_id = ?";
            return $this->executeQuery($query, [
                $this->roleId, $this->departmentId, $this->username, $this->firstName,
                $this->lastName, $this->middleName, $this->email, $this->mobile,
                $this->address, $this->profileImage, $this->status, $this->userId
            ]) !== false;
        } else {
            // Insert new user
            $query = "INSERT INTO users (role_id, department_id, username, first_name, last_name, 
                      middle_name, email, mobile, address, profile_image, password_hash, 
                      verification_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $result = $this->executeQuery($query, [
                $this->roleId, $this->departmentId, $this->username, $this->firstName,
                $this->lastName, $this->middleName, $this->email, $this->mobile,
                $this->address, $this->profileImage, $this->passwordHash,
                $this->verificationStatus ?? 'Pending', $this->status ?? 'inactive'
            ]);
            if ($result) {
                $this->userId = $this->getLastInsertId();
                return true;
            }
            return false;
        }
    }

    /**
     * Delete user from database
     * @return bool
     */
    public function delete()
    {
        if (!$this->userId) return false;
        $query = "DELETE FROM users WHERE user_id = ?";
        return $this->executeQuery($query, [$this->userId]) !== false;
    }
}
?>
