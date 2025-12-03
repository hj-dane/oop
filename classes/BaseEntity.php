<?php
/**
 * BaseEntity - Abstract base class for all entity models
 * Provides common CRUD operations and database interaction
 */
abstract class BaseEntity
{
    protected $conn;
    protected $table;
    protected $id;
    protected $createdAt;
    protected $updatedAt;

    /**
     * Constructor - initialize database connection
     * @param PDO $conn Database connection from PDO
     */
    public function __construct($conn = null)
    {
        if ($conn === null) {
            global $conn;
        }
        $this->conn = $conn;
    }

    /**
     * Get entity ID
     * @return int|null
     */
    public function getId()
    {
        return $this->id ?? null;
    }

    /**
     * Set entity ID
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get created timestamp
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt ?? null;
    }

    /**
     * Get updated timestamp
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt ?? null;
    }

    /**
     * Execute a prepared query
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind
     * @return PDOStatement|false
     */
    protected function executeQuery($query, $params = [])
    {
        try {
            $stmt = $this->conn->prepare($query);
            if ($stmt->execute($params)) {
                return $stmt;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch all results as array of associative arrays
     * @param string $query
     * @param array $params
     * @return array
     */
    protected function fetchAll($query, $params = [])
    {
        $stmt = $this->executeQuery($query, $params);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * Fetch single result as associative array
     * @param string $query
     * @param array $params
     * @return array|null
     */
    protected function fetchOne($query, $params = [])
    {
        $stmt = $this->executeQuery($query, $params);
        return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    }

    /**
     * Get last inserted ID
     * @return string
     */
    protected function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    /**
     * Convert entity to array representation
     * @return array
     */
    public function toArray()
    {
        $reflect = new ReflectionClass($this);
        $properties = $reflect->getProperties(ReflectionProperty::IS_PROTECTED);
        $array = [];
        
        foreach ($properties as $property) {
            $name = $property->getName();
            $array[$name] = $this->$name ?? null;
        }
        
        return $array;
    }

    /**
     * Populate entity from array (useful when loading from database)
     * @param array $data
     */
    public function fromArray($data)
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst(str_replace('_', '', ucwords($key, '_')));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /**
     * Abstract method - must be implemented by subclasses
     */
    abstract public function save();
    abstract public function delete();
}
?>
