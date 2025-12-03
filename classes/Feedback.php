<?php
/**
 * Feedback - Entity class for feedback management
 * Handles feedback data and operations
 */
class Feedback extends BaseEntity
{
    protected $table = 'feedback';
    protected $feedbackId;
    protected $submittedBy;
    protected $subject;
    protected $message;
    protected $statusId;
    protected $response;

    // Getters
    public function getFeedbackId() { return $this->feedbackId; }
    public function getSubmittedBy() { return $this->submittedBy; }
    public function getSubject() { return $this->subject; }
    public function getMessage() { return $this->message; }
    public function getStatusId() { return $this->statusId; }
    public function getResponse() { return $this->response; }

    // Setters
    public function setFeedbackId($feedbackId) { $this->feedbackId = $feedbackId; }
    public function setSubmittedBy($submittedBy) { $this->submittedBy = $submittedBy; }
    public function setSubject($subject) { $this->subject = $subject; }
    public function setMessage($message) { $this->message = $message; }
    public function setStatusId($statusId) { $this->statusId = $statusId; }
    public function setResponse($response) { $this->response = $response; }

    /**
     * Find feedback by ID
     * @param int $feedbackId
     * @return Feedback|null
     */
    public static function findById($feedbackId, $conn)
    {
        $query = "SELECT * FROM feedback WHERE feedback_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$feedbackId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $feedback = new self($conn);
            $feedback->setFeedbackId($data['feedback_id']);
            $feedback->setSubmittedBy($data['submitted_by']);
            $feedback->setSubject($data['subject']);
            $feedback->setMessage($data['message']);
            $feedback->setStatusId($data['status_id']);
            $feedback->setResponse($data['response']);
            return $feedback;
        }
        return null;
    }

    /**
     * Get all feedback
     * @return array
     */
    public static function getAll($conn)
    {
        $query = "SELECT * FROM feedback ORDER BY submitted_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get feedback by status
     * @param int $statusId
     * @return array
     */
    public static function getByStatus($statusId, $conn)
    {
        $query = "SELECT * FROM feedback WHERE status_id = ? ORDER BY submitted_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$statusId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get feedback submitted by user
     * @param int $userId
     * @return array
     */
    public static function getByUser($userId, $conn)
    {
        $query = "SELECT * FROM feedback WHERE submitted_by = ? ORDER BY submitted_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get submitter details
     * @return array|null
     */
    public function getSubmitter()
    {
        $query = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->submittedBy]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get status name
     * @return string|null
     */
    public function getStatusName()
    {
        $query = "SELECT status_name FROM fb_status WHERE status_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->statusId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['status_name'] : null;
    }

    /**
     * Update status
     * @param int $statusId
     * @return bool
     */
    public function updateStatus($statusId)
    {
        $this->statusId = $statusId;
        return $this->save();
    }

    /**
     * Add response to feedback
     * @param string $response
     * @return bool
     */
    public function addResponse($response)
    {
        $this->response = $response;
        return $this->save();
    }

    /**
     * Save feedback to database
     * @return bool
     */
    public function save()
    {
        if ($this->feedbackId) {
            // Update existing feedback
            $query = "UPDATE feedback SET subject = ?, message = ?, status_id = ?, response = ? WHERE feedback_id = ?";
            return $this->executeQuery($query, [
                $this->subject, $this->message, $this->statusId, $this->response, $this->feedbackId
            ]) !== false;
        } else {
            // Insert new feedback
            $query = "INSERT INTO feedback (submitted_by, subject, message, status_id) VALUES (?, ?, ?, ?)";
            $result = $this->executeQuery($query, [
                $this->submittedBy, $this->subject, $this->message, $this->statusId ?? 1
            ]);
            if ($result) {
                $this->feedbackId = $this->getLastInsertId();
                return true;
            }
            return false;
        }
    }

    /**
     * Delete feedback from database
     * @return bool
     */
    public function delete()
    {
        if (!$this->feedbackId) return false;
        $query = "DELETE FROM feedback WHERE feedback_id = ?";
        return $this->executeQuery($query, [$this->feedbackId]) !== false;
    }
}
?>
