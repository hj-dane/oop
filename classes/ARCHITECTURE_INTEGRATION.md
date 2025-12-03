# InSync Architecture: Classes vs PHP Files Integration

## **Current Architecture Overview**

Your application has a **hybrid architecture**:

```
┌─────────────────────────────────────────────────────────────┐
│                   EXISTING PHP FILES                         │
│  (navigation/admin, navigation/manager, navigation/employee) │
│                                                               │
│  • fetch_*.php → Direct PDO queries → Database              │
│  • save_*.php → Direct PDO queries → Database               │
│  • update_*.php → Direct PDO queries → Database             │
│  • *page.php (UI) → jQuery/AJAX → fetch_*.php              │
└─────────────────────────────────────────────────────────────┘
                          ↕ (currently direct)
┌─────────────────────────────────────────────────────────────┐
│                      DATABASE (PDO)                          │
│  (users, tasks, projects, departments, feedback, etc.)       │
└─────────────────────────────────────────────────────────────┘
```

---

## **How Classes CURRENTLY Connect**

The new OOP entity classes are **NOT YET INTEGRATED** into your PHP files. 

Currently, the classes exist as **standalone infrastructure** that you can use. Here's the connectivity:

### **Option 1: Direct Database Connection (Current)**
```php
// Current approach in fetch_tasks.php
require '../../../database/connection.php'; // PDO $conn

$sql = "SELECT ... FROM tasks ...";
$stmt = $conn->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['data' => $rows]);
```

**Connection Path**: PHP File → PDO `$conn` → MySQL Database

---

### **Option 2: Using OOP Classes (Available but NOT YET IMPLEMENTED)**
```php
// New approach with classes
require_once '../../../classes/autoload.php';
require '../../../database/connection.php'; // PDO $conn

// Use Task class instead of raw SQL
$tasks = Task::getAll($conn);  // Returns array of tasks
echo json_encode(['data' => $tasks]);
```

**Connection Path**: PHP File → Task Class → PDO `$conn` → MySQL Database

---

## **The Choice: Three Integration Strategies**

### **STRATEGY 1: Keep Classes Separate (No Integration)**
```
Current State: BOTH exist independently
├─ PHP files continue using PDO directly
└─ Classes available for future use when needed

✅ Pros:
   • No refactoring needed
   • Classes ready when team is ready to use
   • Backward compatible

❌ Cons:
   • Code duplication between PHP and classes
   • Classes sit unused
   • No OOP benefits in current operations
```

### **STRATEGY 2: Parallel Implementation (Gradual Migration)**
```
New State: PHP files REFACTORED to use Classes
├─ Replace raw PDO code with class methods
├─ Keep class → PDO → Database chain
└─ Classes now do the heavy lifting

✅ Pros:
   • Cleaner code in PHP files
   • Reuse logic across files
   • Better error handling (in classes)
   • Easier testing

❌ Cons:
   • Requires refactoring existing PHP
   • More complex troubleshooting
   • Classes need to match existing SQL exactly
```

### **STRATEGY 3: Wrapper Pattern (Classes wrap PDO)**
```
New State: Classes sit between PHP and PDO
PHP File → Class Methods → PDO Queries → Database
         (encapsulates logic)

✅ Pros:
   • Classes validate input
   • Consistent database access
   • Classes handle business logic
   • Easy to change queries in one place

❌ Cons:
   • Small performance overhead
   • Debugging more layers
```

---

## **Current Data Flow (Without Classes)**

### Example: Fetching Tasks

**Current Flow** (fetch_tasks.php):
```
1. User clicks "Monitor Tasks" page
   ↓
2. Browser loads taskspage.php (HTML/CSS/JS)
   ↓
3. JavaScript calls $.ajax('../tasks/fetch_tasks.php')
   ↓
4. fetch_tasks.php receives AJAX request
   ↓
5. PHP includes connection.php (PDO $conn)
   ↓
6. PHP writes raw SQL query:
   SELECT t.task_id, t.title, ... FROM tasks t
   LEFT JOIN projects p ...
   LEFT JOIN users u1 ...
   LEFT JOIN users u2 ...
   ↓
7. $conn->prepare() → $stmt->execute()
   ↓
8. $stmt->fetchAll(PDO::FETCH_ASSOC) → array
   ↓
9. json_encode(['data' => $rows])
   ↓
10. Browser receives JSON
    ↓
11. JavaScript renders DataTable with results
```

---

## **Proposed Data Flow (With Classes)**

### Example: Using Task Class

**New Flow** (if refactored):
```
1. User clicks "Monitor Tasks" page
   ↓
2. Browser loads taskspage.php (HTML/CSS/JS)
   ↓
3. JavaScript calls $.ajax('../tasks/fetch_tasks.php')
   ↓
4. fetch_tasks.php receives AJAX request
   ↓
5. PHP includes classes/autoload.php
   ↓
6. PHP includes connection.php (PDO $conn)
   ↓
7. PHP calls Task::getAll($conn)
   ↓
8. Task class contains prepared SQL query
   ↓
9. Task::getAll() → $stmt->execute()
   ↓
10. Task::getAll() → $stmt->fetchAll()
    ↓
11. Task::getAll() → return array
    ↓
12. PHP receives array from Task::getAll()
    ↓
13. json_encode(['data' => $array])
    ↓
14. Browser receives JSON
    ↓
15. JavaScript renders DataTable with results
```

**Difference**: SQL logic moves from fetch_tasks.php → into Task class

---

## **How Classes Connect to PDO**

All entity classes receive PDO `$conn` as constructor parameter:

```php
// In Task.php
public function __construct($conn = null)
{
    if ($conn === null) {
        global $conn;  // Fallback to global if not passed
    }
    $this->conn = $conn;
}

// In PHP file
require_once 'classes/autoload.php';
require 'database/connection.php';  // PDO $conn

// Option A: Pass $conn explicitly
$tasks = Task::getAll($conn);  // Static method receives $conn

// Option B: Let it use global $conn
$task = new Task();  // Uses global $conn internally
```

---

## **Connection Architecture Diagram**

```
┌──────────────────────────────────────────────────────────────┐
│                 Existing PHP Files                            │
│  (fetch_tasks.php, save_task.php, etc.)                      │
│  Status: USING RAW PDO DIRECTLY                              │
└──────────────────────────────────────────────────────────────┘
                        ↓
        Can be refactored to use classes ↓
                        ↓
┌──────────────────────────────────────────────────────────────┐
│              OOP Entity Classes (NEW)                          │
│  • Task.php (wraps task queries)                             │
│  • Project.php (wraps project queries)                       │
│  • User.php (wraps user queries)                             │
│  • Department.php (wraps dept queries)                       │
│  • Feedback.php, Announcement.php, etc.                      │
│  Status: READY TO USE but NOT INTEGRATED                     │
└──────────────────────────────────────────────────────────────┘
                        ↓
        PDO Connection ($conn) - handles actual queries
                        ↓
┌──────────────────────────────────────────────────────────────┐
│              Database Layer (PDO)                             │
│  • Prepared statements                                        │
│  • Parameter binding                                          │
│  • Transaction support                                        │
│  Status: UNCHANGED (works with or without classes)           │
└──────────────────────────────────────────────────────────────┘
                        ↓
┌──────────────────────────────────────────────────────────────┐
│              MySQL Database                                   │
│  • users, tasks, projects, departments, feedback, etc.       │
│  Status: UNCHANGED                                            │
└──────────────────────────────────────────────────────────────┘
```

---

## **Current State: Classes Exist Independently**

Your classes are created but **not being called** by your PHP files. 

### Example:
```
tasks/fetch_tasks.php
├─ Currently: SQL query hardcoded in file
├─ Alternative: Could call Task::getAll($conn)
└─ Status: PHP doesn't know classes exist yet
```

---

## **To Use Classes: You Need to Refactor**

### **Example Refactoring: fetch_tasks.php**

**BEFORE (Current - Using raw PDO):**
```php
<?php
require '../../../database/connection.php';

$sql = "
    SELECT 
        t.task_id, t.project_id, t.title,
        p.project_name,
        u1.first_name, u1.last_name
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.project_id
    LEFT JOIN users u1 ON t.assigned_to = u1.user_id
    ORDER BY t.task_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $rows]);
```

**AFTER (Refactored - Using Task class):**
```php
<?php
require_once '../../../classes/autoload.php';
require '../../../database/connection.php';

// Get all tasks using Task class
$tasks = Task::getAll($conn);

// Tasks are already properly formatted
echo json_encode(['data' => $tasks]);
```

**Benefits**:
- SQL logic in one place (Task.php), not scattered in multiple fetch_*.php files
- Easier to change queries
- Reusable across different PHP files
- Better testing

---

## **Classes to PHP File Mapping**

| Class | PHP Files That Could Use It | Purpose |
|-------|----------------------------|---------|
| **User.php** | signin.php, register.php, accountpage.php | User login, registration, account management |
| **Task.php** | fetch_tasks.php, save_task.php, update_task.php, mytaskspage.php | Task CRUD operations |
| **Project.php** | fetch_projects.php, save_project.php, projectpage.php | Project CRUD operations |
| **Department.php** | departmentpage.php | Department lookup, employee lists |
| **Announcement.php** | announcementpage.php, fetch_announcements.php | Get announcements |
| **Feedback.php** | feedbackpage.php, fetch_feedback.php, save_feedback.php | Feedback submission, retrieval |
| **Report.php** | reportspage.php, print_report.php, export_report.php | Generate reports |
| **TaskHistory.php** | taskhistorypage.php, fetch_task_history.php | Task status history |
| **LogHistory.php** | (security/admin only) | Log user actions |
| **Archive.php** | (admin operations) | Archive/soft-delete operations |

---

## **Decision: Which Strategy for Your Team?**

### **Recommended: STRATEGY 2 (Gradual Migration)**

Why?
1. ✅ Classes already exist and work
2. ✅ You can refactor one page at a time
3. ✅ Testing becomes easier
4. ✅ Team learns OOP patterns gradually
5. ✅ No breaking changes - classes use same PDO `$conn`
6. ✅ Easy rollback if needed

---

## **Implementation Example: Refactoring One File**

### Refactor: navigation/admin/tasks/fetch_tasks.php

```php
<?php
session_start();
ob_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['accountID'])) {
    ob_clean();
    echo json_encode(['data' => [], 'error' => 'Unauthorized']);
    exit();
}

// Load classes and database
require_once '../../../classes/autoload.php';
require '../../../database/connection.php';

try {
    // Instead of raw SQL, use Task class
    $tasks = Task::getAll($conn);
    
    // Classes return arrays compatible with JSON
    ob_clean();
    echo json_encode(['data' => $tasks]);
    
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'data'  => [],
        'error' => $e->getMessage()
    ]);
}
?>
```

---

## **Summary: Classes vs PHP vs PDO**

| Layer | Current Status | Class Usage |
|-------|----------------|------------|
| **PHP Files** (UI/AJAX handlers) | Using raw PDO | Could use classes for cleaner code |
| **Classes** (Business Logic) | Created but not used | Ready to be called by PHP files |
| **PDO** (Database Layer) | Core database handler | Unchanged - classes use it internally |
| **MySQL** (Data Storage) | Stores all data | Unchanged - no new tables needed |

**Bottom Line**: 
- Classes don't **replace** PDO - they **wrap** it
- PHP files don't **require** classes - they're optional
- You can use classes OR raw PDO, or **both together**
- Team can migrate gradually at their own pace
