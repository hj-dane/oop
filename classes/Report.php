<?php
/**
 * Report - Entity class for report management
 * Handles report data and operations
 */
class Report extends BaseEntity
{
    protected $table = 'reports';
    protected $reportId;
    protected $generatedBy;
    protected $reportType;
    protected $periodStart;
    protected $periodEnd;

    // Getters
    public function getReportId() { return $this->reportId; }
    public function getGeneratedBy() { return $this->generatedBy; }
    public function getReportType() { return $this->reportType; }
    public function getPeriodStart() { return $this->periodStart; }
    public function getPeriodEnd() { return $this->periodEnd; }

    // Setters
    public function setReportId($reportId) { $this->reportId = $reportId; }
    public function setGeneratedBy($generatedBy) { $this->generatedBy = $generatedBy; }
    public function setReportType($reportType) { $this->reportType = $reportType; }
    public function setPeriodStart($periodStart) { $this->periodStart = $periodStart; }
    public function setPeriodEnd($periodEnd) { $this->periodEnd = $periodEnd; }

    /**
     * Find report by ID
     * @param int $reportId
     * @return Report|null
     */
    public static function findById($reportId, $conn)
    {
        $query = "SELECT * FROM reports WHERE report_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$reportId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $report = new self($conn);
            $report->setReportId($data['report_id']);
            $report->setGeneratedBy($data['generated_by']);
            $report->setReportType($data['report_type']);
            $report->setPeriodStart($data['period_start']);
            $report->setPeriodEnd($data['period_end']);
            return $report;
        }
        return null;
    }

    /**
     * Get all reports
     * @return array
     */
    public static function getAll($conn)
    {
        $query = "SELECT * FROM reports ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get reports by type
     * @param string $reportType
     * @return array
     */
    public static function getByType($reportType, $conn)
    {
        $query = "SELECT * FROM reports WHERE report_type = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$reportType]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get reports by user (who generated them)
     * @param int $userId
     * @return array
     */
    public static function getByUser($userId, $conn)
    {
        $query = "SELECT * FROM reports WHERE generated_by = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get generator details
     * @return array|null
     */
    public function getGenerator()
    {
        $query = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->generatedBy]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Save report to database
     * @return bool
     */
    public function save()
    {
        if ($this->reportId) {
            // Update existing report
            $query = "UPDATE reports SET report_type = ?, period_start = ?, period_end = ? WHERE report_id = ?";
            return $this->executeQuery($query, [
                $this->reportType, $this->periodStart, $this->periodEnd, $this->reportId
            ]) !== false;
        } else {
            // Insert new report
            $query = "INSERT INTO reports (generated_by, report_type, period_start, period_end) VALUES (?, ?, ?, ?)";
            $result = $this->executeQuery($query, [
                $this->generatedBy, $this->reportType, $this->periodStart, $this->periodEnd
            ]);
            if ($result) {
                $this->reportId = $this->getLastInsertId();
                return true;
            }
            return false;
        }
    }

    /**
     * Delete report from database
     * @return bool
     */
    public function delete()
    {
        if (!$this->reportId) return false;
        $query = "DELETE FROM reports WHERE report_id = ?";
        return $this->executeQuery($query, [$this->reportId]) !== false;
    }
}
?>
