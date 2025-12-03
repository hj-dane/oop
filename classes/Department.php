<?php
/**
 * Department - Entity class for department management
 * Handles department data and operations
 */
class Department extends BaseEntity
{
    protected $table = 'departments';
    protected $departmentId;
    protected $departmentName;
    protected $status;

    // Getters
    public function getDepartmentId() { return $this->departmentId; }
    public function getDepartmentName() { return $this->departmentName; }
    public function getStatus() { return $this->status; }

    // Setters
    public function setDepartmentId($departmentId) { $this->departmentId = $departmentId; }
    public function setDepartmentName($departmentName) { $this->departmentName = $departmentName; }
    public function setStatus($status) { $this->status = $status; }

    /**
     * Find department by ID
     * @param int $departmentId
     * @return Department|null
     */
    public static function findById($departmentId, $conn)
    {
        $query = "SELECT * FROM departments WHERE department_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$departmentId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $dept = new self($conn);
            $dept->setDepartmentId($data['department_id']);
            $dept->setDepartmentName($data['department_name']);
            $dept->setStatus($data['status']);
            return $dept;
        }
        return null;
    }

    /**
     * Get all departments
     * @return array
     */
    public static function getAll($conn)
    {
        $query = "SELECT * FROM departments WHERE status = 'active' ORDER BY department_name";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all projects in this department
     * @return array
     */
    public function getProjects()
    {
        $query = "SELECT * FROM projects WHERE department_id = ? AND status != 'Archived'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all employees in this department
     * @return array
     */
    public function getEmployees()
    {
        $query = "SELECT u.* FROM users u 
                  WHERE u.department_id = ? AND u.role_id = 3 AND u.status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get project count for this department
     * @return int
     */
    public function getProjectCount()
    {
        $query = "SELECT COUNT(*) as count FROM projects WHERE department_id = ? AND status != 'Archived'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->departmentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Archive department
     * @return bool
     */
    public function archive()
    {
        $this->status = 'archived';
        return $this->save();
    }

    /**
     * Save department to database
     * @return bool
     */
    public function save()
    {
        if ($this->departmentId) {
            // Update existing department
            $query = "UPDATE departments SET department_name = ?, status = ? WHERE department_id = ?";
            return $this->executeQuery($query, [
                $this->departmentName, $this->status, $this->departmentId
            ]) !== false;
        } else {
            // Insert new department
            $query = "INSERT INTO departments (department_name, status) VALUES (?, ?)";
            $result = $this->executeQuery($query, [
                $this->departmentName, $this->status ?? 'active'
            ]);
            if ($result) {
                $this->departmentId = $this->getLastInsertId();
                return true;
            }
            return false;
        }
    }

    /**
     * Delete department from database
     * @return bool
     */
    public function delete()
    {
        if (!$this->departmentId) return false;
        $query = "DELETE FROM departments WHERE department_id = ?";
        return $this->executeQuery($query, [$this->departmentId]) !== false;
    }
}
?>
