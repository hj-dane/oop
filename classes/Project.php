<?php
/**
 * Project - Entity class for project management
 * Handles project data and operations
 */
class Project extends BaseEntity
{
    protected $table = 'projects';
    protected $projectId;
    protected $projectName;
    protected $departmentId;
    protected $managerId;
    protected $description;
    protected $startDate;
    protected $endDate;
    protected $status;

    // Getters
    public function getProjectId() { return $this->projectId; }
    public function getProjectName() { return $this->projectName; }
    public function getDepartmentId() { return $this->departmentId; }
    public function getManagerId() { return $this->managerId; }
    public function getDescription() { return $this->description; }
    public function getStartDate() { return $this->startDate; }
    public function getEndDate() { return $this->endDate; }
    public function getStatus() { return $this->status; }

    // Setters
    public function setProjectId($projectId) { $this->projectId = $projectId; }
    public function setProjectName($projectName) { $this->projectName = $projectName; }
    public function setDepartmentId($departmentId) { $this->departmentId = $departmentId; }
    public function setManagerId($managerId) { $this->managerId = $managerId; }
    public function setDescription($description) { $this->description = $description; }
    public function setStartDate($startDate) { $this->startDate = $startDate; }
    public function setEndDate($endDate) { $this->endDate = $endDate; }
    public function setStatus($status) { $this->status = $status; }

    /**
     * Find project by ID
     * @param int $projectId
     * @return Project|null
     */
    public static function findById($projectId, $conn)
    {
        $query = "SELECT * FROM projects WHERE project_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$projectId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $project = new self($conn);
            $project->setProjectId($data['project_id']);
            $project->setProjectName($data['project_name']);
            $project->setDepartmentId($data['department_id']);
            $project->setManagerId($data['manager_id']);
            $project->setDescription($data['description']);
            $project->setStartDate($data['start_date']);
            $project->setEndDate($data['end_date']);
            $project->setStatus($data['status']);
            return $project;
        }
        return null;
    }

    /**
     * Get all projects
     * @return array
     */
    public static function getAll($conn)
    {
        $query = "SELECT * FROM projects WHERE status != 'Archived' ORDER BY project_name";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get projects by department
     * @param int $departmentId
     * @return array
     */
    public static function getByDepartment($departmentId, $conn)
    {
        $query = "SELECT * FROM projects WHERE department_id = ? AND status != 'Archived'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get projects by manager
     * @param int $managerId
     * @return array
     */
    public static function getByManager($managerId, $conn)
    {
        $query = "SELECT * FROM projects WHERE manager_id = ? AND status != 'Archived'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$managerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all tasks in this project
     * @return array
     */
    public function getTasks()
    {
        $query = "SELECT * FROM tasks WHERE project_id = ? ORDER BY due_date";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get manager details
     * @return array|null
     */
    public function getManager()
    {
        $query = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->managerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get department details
     * @return array|null
     */
    public function getDepartment()
    {
        $query = "SELECT * FROM departments WHERE department_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->departmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get task count for this project
     * @return int
     */
    public function getTaskCount()
    {
        $query = "SELECT COUNT(*) as count FROM tasks WHERE project_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->projectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Get completed task count
     * @return int
     */
    public function getCompletedTaskCount()
    {
        $query = "SELECT COUNT(*) as count FROM tasks WHERE project_id = ? AND status = 'Completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->projectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Get project progress percentage
     * @return float
     */
    public function getProgress()
    {
        $total = $this->getTaskCount();
        if ($total == 0) return 0;
        $completed = $this->getCompletedTaskCount();
        return ($completed / $total) * 100;
    }

    /**
     * Check if project is overdue
     * @return bool
     */
    public function isOverdue()
    {
        return $this->endDate && $this->endDate < date('Y-m-d') && $this->status != 'Completed';
    }

    /**
     * Update project status
     * @param string $status
     * @return bool
     */
    public function updateStatus($status)
    {
        $this->status = $status;
        return $this->save();
    }

    /**
     * Archive project
     * @return bool
     */
    public function archive()
    {
        return $this->updateStatus('Archived');
    }

    /**
     * Save project to database
     * @return bool
     */
    public function save()
    {
        if ($this->projectId) {
            // Update existing project
            $query = "UPDATE projects SET project_name = ?, department_id = ?, manager_id = ?, 
                      description = ?, start_date = ?, end_date = ?, status = ? WHERE project_id = ?";
            return $this->executeQuery($query, [
                $this->projectName, $this->departmentId, $this->managerId,
                $this->description, $this->startDate, $this->endDate, $this->status, $this->projectId
            ]) !== false;
        } else {
            // Insert new project
            $query = "INSERT INTO projects (project_name, department_id, manager_id, description, 
                      start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $result = $this->executeQuery($query, [
                $this->projectName, $this->departmentId, $this->managerId, $this->description,
                $this->startDate, $this->endDate, $this->status ?? 'Ongoing'
            ]);
            if ($result) {
                $this->projectId = $this->getLastInsertId();
                return true;
            }
            return false;
        }
    }

    /**
     * Delete project from database
     * @return bool
     */
    public function delete()
    {
        if (!$this->projectId) return false;
        $query = "DELETE FROM projects WHERE project_id = ?";
        return $this->executeQuery($query, [$this->projectId]) !== false;
    }
}
?>
