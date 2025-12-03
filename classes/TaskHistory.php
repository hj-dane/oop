<?php
/**
 * TaskHistory - Entity class for task status history tracking
 * Handles task status change records
 */
class TaskHistory extends BaseEntity
{
    protected $table = 'task_history';
    protected $historyId;
    protected $taskId;
    protected $updatedBy;
    protected $oldStatus;
    protected $newStatus;
    protected $remarks;

    // Getters
    public function getHistoryId() { return $this->historyId; }
    public function getTaskId() { return $this->taskId; }
    public function getUpdatedBy() { return $this->updatedBy; }
    public function getOldStatus() { return $this->oldStatus; }
    public function getNewStatus() { return $this->newStatus; }
    public function getRemarks() { return $this->remarks; }

    // Setters
    public function setHistoryId($historyId) { $this->historyId = $historyId; }
    public function setTaskId($taskId) { $this->taskId = $taskId; }
    public function setUpdatedBy($updatedBy) { $this->updatedBy = $updatedBy; }
    public function setOldStatus($oldStatus) { $this->oldStatus = $oldStatus; }
    public function setNewStatus($newStatus) { $this->newStatus = $newStatus; }
    public function setRemarks($remarks) { $this->remarks = $remarks; }

    /**
     * Find history record by ID
     * @param int $historyId
     * @return TaskHistory|null
     */
    public static function findById($historyId, $conn)
    {
        $query = "SELECT * FROM task_history WHERE history_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$historyId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $history = new self($conn);
            $history->setHistoryId($data['history_id']);
            $history->setTaskId($data['task_id']);
            $history->setUpdatedBy($data['updated_by']);
            $history->setOldStatus($data['old_status']);
            $history->setNewStatus($data['new_status']);
            $history->setRemarks($data['remarks']);
            return $history;
        }
        return null;
    }

    /**
     * Get history for a specific task
     * @param int $taskId
     * @return array
     */
    public static function getTaskHistory($taskId, $conn)
    {
        $query = "SELECT * FROM task_history WHERE task_id = ? ORDER BY change_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get history records by user
     * @param int $userId
     * @return array
     */
    public static function getByUser($userId, $conn)
    {
        $query = "SELECT * FROM task_history WHERE updated_by = ? ORDER BY change_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user who made the update
     * @return array|null
     */
    public function getUpdater()
    {
        $query = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->updatedBy]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Save history record to database
     * @return bool
     */
    public function save()
    {
        // Insert new history record only (don't update existing)
        $query = "INSERT INTO task_history (task_id, updated_by, old_status, new_status, remarks) 
                  VALUES (?, ?, ?, ?, ?)";
        $result = $this->executeQuery($query, [
            $this->taskId, $this->updatedBy, $this->oldStatus, $this->newStatus, $this->remarks
        ]);
        if ($result) {
            $this->historyId = $this->getLastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Delete history record from database
     * @return bool
     */
    public function delete()
    {
        if (!$this->historyId) return false;
        $query = "DELETE FROM task_history WHERE history_id = ?";
        return $this->executeQuery($query, [$this->historyId]) !== false;
    }
}
?>
