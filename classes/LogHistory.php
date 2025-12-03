<?php
/**
 * LogHistory - Entity class for action logging
 * Tracks user actions for audit purposes
 */
class LogHistory extends BaseEntity
{
    protected $table = 'log_history';
    protected $logId;
    protected $userId;
    protected $actionType;
    protected $createdAt;

    // Getters
    public function getLogId() { return $this->logId; }
    public function getUserId() { return $this->userId; }
    public function getActionType() { return $this->actionType; }
    public function getCreatedAt() { return $this->createdAt; }

    // Setters
    public function setLogId($logId) { $this->logId = $logId; }
    public function setUserId($userId) { $this->userId = $userId; }
    public function setActionType($actionType) { $this->actionType = $actionType; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }

    /**
     * Find log record by ID
     * @param int $logId
     * @return LogHistory|null
     */
    public static function findById($logId, $conn)
    {
        $query = "SELECT * FROM log_history WHERE log_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$logId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $log = new self($conn);
            $log->setLogId($data['log_id']);
            $log->setUserId($data['user_id']);
            $log->setActionType($data['action_type']);
            $log->setCreatedAt($data['created_at']);
            return $log;
        }
        return null;
    }

    /**
     * Get all logs
     * @return array
     */
    public static function getAll($conn)
    {
        $query = "SELECT * FROM log_history ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get logs by user
     * @param int $userId
     * @return array
     */
    public static function getByUser($userId, $conn)
    {
        $query = "SELECT * FROM log_history WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get logs by action type
     * @param int $actionType
     * @return array
     */
    public static function getByAction($actionType, $conn)
    {
        $query = "SELECT * FROM log_history WHERE action_type = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$actionType]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user who performed the action
     * @return array|null
     */
    public function getUser()
    {
        $query = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get action name
     * @return string|null
     */
    public function getActionName()
    {
        $query = "SELECT action_name FROM actions WHERE action_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->actionType]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['action_name'] : null;
    }

    /**
     * Log an action
     * @param int $userId
     * @param int $actionType
     * @return bool
     */
    public static function logAction($userId, $actionType, $conn)
    {
        $log = new self($conn);
        $log->setUserId($userId);
        $log->setActionType($actionType);
        return $log->save();
    }

    /**
     * Save log to database
     * @return bool
     */
    public function save()
    {
        // Insert new log record only (don't update existing)
        $query = "INSERT INTO log_history (user_id, action_type) VALUES (?, ?)";
        $result = $this->executeQuery($query, [$this->userId, $this->actionType]);
        if ($result) {
            $this->logId = $this->getLastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Delete log record from database
     * @return bool
     */
    public function delete()
    {
        if (!$this->logId) return false;
        $query = "DELETE FROM log_history WHERE log_id = ?";
        return $this->executeQuery($query, [$this->logId]) !== false;
    }
}
?>
