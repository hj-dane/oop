<?php
/**
 * Announcement - Entity class for announcements
 * Handles announcement data and operations
 */
class Announcement extends BaseEntity
{
    protected $table = 'announcements';
    protected $announcementId;
    protected $postedBy;
    protected $title;
    protected $message;
    protected $audience;
    protected $status;

    // Getters
    public function getAnnouncementId() { return $this->announcementId; }
    public function getPostedBy() { return $this->postedBy; }
    public function getTitle() { return $this->title; }
    public function getMessage() { return $this->message; }
    public function getAudience() { return $this->audience; }
    public function getStatus() { return $this->status; }

    // Setters
    public function setAnnouncementId($announcementId) { $this->announcementId = $announcementId; }
    public function setPostedBy($postedBy) { $this->postedBy = $postedBy; }
    public function setTitle($title) { $this->title = $title; }
    public function setMessage($message) { $this->message = $message; }
    public function setAudience($audience) { $this->audience = $audience; }
    public function setStatus($status) { $this->status = $status; }

    /**
     * Find announcement by ID
     * @param int $announcementId
     * @return Announcement|null
     */
    public static function findById($announcementId, $conn)
    {
        $query = "SELECT * FROM announcements WHERE announcement_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$announcementId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $ann = new self($conn);
            $ann->setAnnouncementId($data['announcement_id']);
            $ann->setPostedBy($data['posted_by']);
            $ann->setTitle($data['title']);
            $ann->setMessage($data['message']);
            $ann->setAudience($data['audience']);
            $ann->setStatus($data['status']);
            return $ann;
        }
        return null;
    }

    /**
     * Get all active announcements
     * @return array
     */
    public static function getActive($conn)
    {
        $query = "SELECT * FROM announcements WHERE status = 'active' ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get announcements by audience
     * @param string $audience
     * @return array
     */
    public static function getByAudience($audience, $conn)
    {
        $query = "SELECT * FROM announcements WHERE (audience = ? OR audience = 'All') 
                  AND status = 'active' ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$audience]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get author details
     * @return array|null
     */
    public function getAuthor()
    {
        $query = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->postedBy]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Save announcement to database
     * @return bool
     */
    public function save()
    {
        if ($this->announcementId) {
            // Update existing announcement
            $query = "UPDATE announcements SET title = ?, message = ?, audience = ?, status = ? WHERE announcement_id = ?";
            return $this->executeQuery($query, [
                $this->title, $this->message, $this->audience, $this->status, $this->announcementId
            ]) !== false;
        } else {
            // Insert new announcement
            $query = "INSERT INTO announcements (posted_by, title, message, audience, status) 
                      VALUES (?, ?, ?, ?, ?)";
            $result = $this->executeQuery($query, [
                $this->postedBy, $this->title, $this->message, 
                $this->audience ?? 'All', $this->status ?? 'active'
            ]);
            if ($result) {
                $this->announcementId = $this->getLastInsertId();
                return true;
            }
            return false;
        }
    }

    /**
     * Delete announcement from database
     * @return bool
     */
    public function delete()
    {
        if (!$this->announcementId) return false;
        $query = "DELETE FROM announcements WHERE announcement_id = ?";
        return $this->executeQuery($query, [$this->announcementId]) !== false;
    }
}
?>
