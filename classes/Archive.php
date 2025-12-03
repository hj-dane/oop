<?php
/**
 * Archive - Entity class for archived entities
 * Handles archival of departments, projects, and tasks
 */
class Archive extends BaseEntity
{
    protected $table = 'archive';
    protected $archiveId;
    protected $entityId;
    protected $entityType;
    protected $name;
    protected $description;
    protected $relatedDepartmentId;
    protected $relatedProjectId;
    protected $archivedBy;
    protected $originalStatus;

    // Getters
    public function getArchiveId() { return $this->archiveId; }
    public function getEntityId() { return $this->entityId; }
    public function getEntityType() { return $this->entityType; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getRelatedDepartmentId() { return $this->relatedDepartmentId; }
    public function getRelatedProjectId() { return $this->relatedProjectId; }
    public function getArchivedBy() { return $this->archivedBy; }
    public function getOriginalStatus() { return $this->originalStatus; }

    // Setters
    public function setArchiveId($archiveId) { $this->archiveId = $archiveId; }
    public function setEntityId($entityId) { $this->entityId = $entityId; }
    public function setEntityType($entityType) { $this->entityType = $entityType; }
    public function setName($name) { $this->name = $name; }
    public function setDescription($description) { $this->description = $description; }
    public function setRelatedDepartmentId($relatedDepartmentId) { $this->relatedDepartmentId = $relatedDepartmentId; }
    public function setRelatedProjectId($relatedProjectId) { $this->relatedProjectId = $relatedProjectId; }
    public function setArchivedBy($archivedBy) { $this->archivedBy = $archivedBy; }
    public function setOriginalStatus($originalStatus) { $this->originalStatus = $originalStatus; }

    /**
     * Find archive record by ID
     * @param int $archiveId
     * @return Archive|null
     */
    public static function findById($archiveId, $conn)
    {
        $query = "SELECT * FROM archive WHERE archive_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$archiveId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $archive = new self($conn);
            $archive->setArchiveId($data['archive_id']);
            $archive->setEntityId($data['entity_id']);
            $archive->setEntityType($data['entity_type']);
            $archive->setName($data['name']);
            $archive->setDescription($data['description']);
            $archive->setRelatedDepartmentId($data['related_department_id']);
            $archive->setRelatedProjectId($data['related_project_id']);
            $archive->setArchivedBy($data['archived_by']);
            $archive->setOriginalStatus($data['original_status']);
            return $archive;
        }
        return null;
    }

    /**
     * Get all archived records
     * @return array
     */
    public static function getAll($conn)
    {
        $query = "SELECT * FROM archive ORDER BY archived_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get archived records by type
     * @param string $entityType (department, project, task)
     * @return array
     */
    public static function getByType($entityType, $conn)
    {
        $query = "SELECT * FROM archive WHERE entity_type = ? ORDER BY archived_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$entityType]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get archived records by department
     * @param int $departmentId
     * @return array
     */
    public static function getByDepartment($departmentId, $conn)
    {
        $query = "SELECT * FROM archive WHERE related_department_id = ? ORDER BY archived_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get archived records by project
     * @param int $projectId
     * @return array
     */
    public static function getByProject($projectId, $conn)
    {
        $query = "SELECT * FROM archive WHERE related_project_id = ? ORDER BY archived_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user who archived
     * @return array|null
     */
    public function getArchivedByUser()
    {
        $query = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->archivedBy]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Archive an entity
     * @param string $entityType
     * @param int $entityId
     * @param string $name
     * @param string|null $description
     * @param int|null $departmentId
     * @param int|null $projectId
     * @param int $archivedBy
     * @param string|null $originalStatus
     * @return bool
     */
    public static function archiveEntity($entityType, $entityId, $name, $description, 
                                         $departmentId, $projectId, $archivedBy, 
                                         $originalStatus, $conn)
    {
        $archive = new self($conn);
        $archive->setEntityType($entityType);
        $archive->setEntityId($entityId);
        $archive->setName($name);
        $archive->setDescription($description);
        $archive->setRelatedDepartmentId($departmentId);
        $archive->setRelatedProjectId($projectId);
        $archive->setArchivedBy($archivedBy);
        $archive->setOriginalStatus($originalStatus);
        return $archive->save();
    }

    /**
     * Save archive record to database
     * @return bool
     */
    public function save()
    {
        if ($this->archiveId) {
            // Update existing archive record
            $query = "UPDATE archive SET name = ?, description = ? WHERE archive_id = ?";
            return $this->executeQuery($query, [
                $this->name, $this->description, $this->archiveId
            ]) !== false;
        } else {
            // Insert new archive record
            $query = "INSERT INTO archive (entity_id, entity_type, name, description, 
                      related_department_id, related_project_id, archived_by, original_status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $result = $this->executeQuery($query, [
                $this->entityId, $this->entityType, $this->name, $this->description,
                $this->relatedDepartmentId, $this->relatedProjectId, $this->archivedBy, $this->originalStatus
            ]);
            if ($result) {
                $this->archiveId = $this->getLastInsertId();
                return true;
            }
            return false;
        }
    }

    /**
     * Delete archive record from database
     * @return bool
     */
    public function delete()
    {
        if (!$this->archiveId) return false;
        $query = "DELETE FROM archive WHERE archive_id = ?";
        return $this->executeQuery($query, [$this->archiveId]) !== false;
    }
}
?>
