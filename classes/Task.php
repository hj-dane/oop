<?php
/**
 * Task - Entity class for task management
 * Handles task data and operations
 */
class Task extends BaseEntity
{
    protected $table = 'tasks';
    protected $taskId;
    protected $projectId;
    protected $assignedTo;
    protected $assignedBy;
    protected $title;
    protected $description;
    protected $dueDate;
    protected $status;

    // Getters
    public function getTaskId() { return $this->taskId; }
    public function getProjectId() { return $this->projectId; }
    public function getAssignedTo() { return $this->assignedTo; }
    public function getAssignedBy() { return $this->assignedBy; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getDueDate() { return $this->dueDate; }
    public function getStatus() { return $this->status; }

    // Setters
    public function setTaskId($taskId) { $this->taskId = $taskId; }
    public function setProjectId($projectId) { $this->projectId = $projectId; }
    public function setAssignedTo($assignedTo) { $this->assignedTo = $assignedTo; }
    public function setAssignedBy($assignedBy) { $this->assignedBy = $assignedBy; }
    public function setTitle($title) { $this->title = $title; }
    public function setDescription($description) { $this->description = $description; }
    public function setDueDate($dueDate) { $this->dueDate = $dueDate; }
    public function setStatus($status) { $this->status = $status; }

    /**
     * Find task by ID
     * @param int $taskId
     * @return Task|null
     */
    public static function findById($taskId, $conn)
    {
        $query = "SELECT * FROM tasks WHERE task_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$taskId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $task = new self($conn);
            $task->setTaskId($data['task_id']);
            $task->setProjectId($data['project_id']);
            $task->setAssignedTo($data['assigned_to']);
            $task->setAssignedBy($data['assigned_by']);
            $task->setTitle($data['title']);
            $task->setDescription($data['description']);
            $task->setDueDate($data['due_date']);
            $task->setStatus($data['status']);
            return $task;
        }
        return null;
    }

    /**
     * Get all tasks
     * @return array
     */
    public static function getAll($conn)
    {
        $query = "SELECT * FROM tasks ORDER BY due_date";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get tasks by project
     * @param int $projectId
     * @return array
     */
    public static function getByProject($projectId, $conn)
    {
        $query = "SELECT * FROM tasks WHERE project_id = ? ORDER BY due_date";
        $stmt = $conn->prepare($query);
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find tasks assigned to user
     * @param int $userId
     * @return array
     */
    public static function findAssignedTo($userId, $conn)
    {
        $query = "SELECT * FROM tasks WHERE assigned_to = ? AND status != 'Completed' ORDER BY due_date";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find tasks assigned by user
     * @param int $userId
     * @return array
     */
    public static function findAssignedBy($userId, $conn)
    {
        $query = "SELECT * FROM tasks WHERE assigned_by = ? ORDER BY due_date";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get assignee details
     * @return array|null
     */
    public function getAssignee()
    {
        $query = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->assignedTo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get assigner details
     * @return array|null
     */
    public function getAssigner()
    {
        $query = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->assignedBy]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get project details
     * @return array|null
     */
    public function getProject()
    {
        $query = "SELECT * FROM projects WHERE project_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->projectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get task history
     * @return array
     */
    public function getHistory()
    {
        $query = "SELECT * FROM task_history WHERE task_id = ? ORDER BY change_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if task is overdue
     * @return bool
     */
    public function isOverdue()
    {
        return $this->dueDate && $this->dueDate < date('Y-m-d') && $this->status != 'Completed';
    }

    /**
     * Check if task is due soon (within 3 days)
     * @return bool
     */
    public function isDueSoon()
    {
        if (!$this->dueDate || $this->status == 'Completed') return false;
        $daysUntilDue = (strtotime($this->dueDate) - time()) / 86400;
        return $daysUntilDue <= 3 && $daysUntilDue >= 0;
    }

    /**
     * Update task status
     * @param string $status
     * @param int $updatedBy User ID who updated the status
     * @param string|null $remarks Optional remarks
     * @return bool
     */
    public function updateStatus($status, $updatedBy, $remarks = null)
    {
        $oldStatus = $this->status;
        $this->status = $status;
        
        // Save task update
        if (!$this->save()) {
            return false;
        }

        // Log the status change in task_history
        $query = "INSERT INTO task_history (task_id, updated_by, old_status, new_status, remarks) 
                  VALUES (?, ?, ?, ?, ?)";
        return $this->executeQuery($query, [
            $this->taskId, $updatedBy, $oldStatus, $status, $remarks
        ]) !== false;
    }

    /**
     * Save task to database
     * @return bool
     */
    public function save()
    {
        if ($this->taskId) {
            // Update existing task
            $query = "UPDATE tasks SET project_id = ?, assigned_to = ?, assigned_by = ?, 
                      title = ?, description = ?, due_date = ?, status = ? WHERE task_id = ?";
            return $this->executeQuery($query, [
                $this->projectId, $this->assignedTo, $this->assignedBy,
                $this->title, $this->description, $this->dueDate, $this->status, $this->taskId
            ]) !== false;
        } else {
            // Insert new task
            $query = "INSERT INTO tasks (project_id, assigned_to, assigned_by, title, 
                      description, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $result = $this->executeQuery($query, [
                $this->projectId, $this->assignedTo, $this->assignedBy,
                $this->title, $this->description, $this->dueDate, $this->status ?? 'Pending'
            ]);
            if ($result) {
                $this->taskId = $this->getLastInsertId();
                return true;
            }
            return false;
        }
    }

    /**
     * Delete task from database
     * @return bool
     */
    public function delete()
    {
        if (!$this->taskId) return false;
        $query = "DELETE FROM tasks WHERE task_id = ?";
        return $this->executeQuery($query, [$this->taskId]) !== false;
    }
}
?>
