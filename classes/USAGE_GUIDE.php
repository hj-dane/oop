<?php
/**
 * ============================================================================
 * INSYNC OOP ENTITY CLASSES - IMPLEMENTATION GUIDE
 * ============================================================================
 * 
 * This directory contains lightweight OOP entity classes that work alongside
 * your existing PDO database abstraction. These classes provide:
 * 
 * ✓ Clean data encapsulation
 * ✓ Reusable business logic
 * ✓ Type safety with getters/setters
 * ✓ Common database operations (save, delete, find)
 * ✓ Entity relationships
 * 
 * ============================================================================
 * QUICK START
 * ============================================================================
 * 
 * 1. At the top of any file using entities, include the autoloader:
 *    
 *    require_once 'classes/autoload.php';
 * 
 * 2. Use the classes with your global $conn PDO connection:
 *    
 *    // Load a user by ID
 *    $user = User::findById(1, $conn);
 *    
 *    // Create a new user
 *    $user = new User($conn);
 *    $user->setFirstName('John');
 *    $user->setEmail('john@example.com');
 *    $user->setPassword('password123');
 *    $user->save();
 * 
 * ============================================================================
 * AVAILABLE CLASSES
 * ============================================================================
 * 
 * CORE ENTITIES:
 * - User         : User management with authentication
 * - Department   : Department management
 * - Project      : Project management with progress tracking
 * - Task         : Task management with status tracking
 * 
 * SUPPORTING ENTITIES:
 * - Announcement : System announcements
 * - Feedback     : User feedback with status tracking
 * - Report       : Generated reports
 * - Archive      : Archived entities tracking
 * - TaskHistory  : Task status change history
 * - LogHistory   : Action logs for audit trail
 * 
 * ============================================================================
 * CLASS USAGE EXAMPLES
 * ============================================================================
 * 
 * USER CLASS
 * ----------
 * 
 * // Load user by email
 * $user = User::findByEmail('user@example.com', $conn);
 * 
 * // Get user properties
 * echo $user->getFullName();
 * echo $user->getEmail();
 * 
 * // Authenticate user
 * if ($user->verifyPassword($_POST['password'])) {
 *     // Password is correct
 * }
 * 
 * // Update user profile
 * $user->setFirstName('Jane');
 * $user->setMobile('1234567890');
 * $user->save();
 * 
 * // Verify email
 * $user->verify();
 * 
 * // Get all users
 * $users = User::getAll($conn);
 * 
 * 
 * PROJECT CLASS
 * ----------
 * 
 * // Load project
 * $project = Project::findById(1, $conn);
 * 
 * // Get project tasks
 * $tasks = $project->getTasks();
 * 
 * // Get project manager
 * $manager = $project->getManager();
 * 
 * // Get project progress (0-100)
 * $progress = $project->getProgress();
 * 
 * // Check if overdue
 * if ($project->isOverdue()) {
 *     // Send reminder
 * }
 * 
 * // Get project statistics
 * $totalTasks = $project->getTaskCount();
 * $completedTasks = $project->getCompletedTaskCount();
 * 
 * // Update project status
 * $project->updateStatus('Completed');
 * 
 * // Archive project
 * $project->archive();
 * 
 * 
 * TASK CLASS
 * ----------
 * 
 * // Load task
 * $task = Task::findById(1, $conn);
 * 
 * // Get task details
 * $assignee = $task->getAssignee();
 * $project = $task->getProject();
 * 
 * // Check if overdue
 * if ($task->isOverdue()) {
 *     // Send notification
 * }
 * 
 * // Check if due soon (within 3 days)
 * if ($task->isDueSoon()) {
 *     // Send reminder
 * }
 * 
 * // Update task status with history tracking
 * $task->updateStatus('In Progress', $currentUserId, 'Started working');
 * 
 * // Get task history
 * $history = $task->getHistory();
 * 
 * // Get tasks assigned to user
 * $myTasks = Task::getAssignedTo($userId, $conn);
 * 
 * 
 * DEPARTMENT CLASS
 * ----------
 * 
 * // Load department
 * $dept = Department::findById(1, $conn);
 * 
 * // Get department projects
 * $projects = $dept->getProjects();
 * 
 * // Get department employees
 * $employees = $dept->getEmployees();
 * 
 * // Get project count
 * $count = $dept->getProjectCount();
 * 
 * // Archive department
 * $dept->archive();
 * 
 * 
 * ANNOUNCEMENT CLASS
 * ----------
 * 
 * // Get active announcements
 * $announcements = Announcement::getActive($conn);
 * 
 * // Get announcements for specific audience
 * $announcements = Announcement::getByAudience('Manager', $conn);
 * 
 * // Create new announcement
 * $ann = new Announcement($conn);
 * $ann->setPostedBy($userId);
 * $ann->setTitle('New Project');
 * $ann->setMessage('Starting new project...');
 * $ann->setAudience('All');
 * $ann->save();
 * 
 * 
 * TASK HISTORY CLASS
 * ----------
 * 
 * // Get history for a task
 * $history = TaskHistory::getTaskHistory($taskId, $conn);
 * 
 * // Get history entries by user
 * $history = TaskHistory::getByUser($userId, $conn);
 * 
 * // Get the user who made the update
 * $updater = $historyRecord->getUpdater();
 * 
 * 
 * LOG HISTORY CLASS
 * ----------
 * 
 * // Log a user action
 * LogHistory::logAction($userId, $actionType, $conn);
 * 
 * // Get all logs
 * $logs = LogHistory::getAll($conn);
 * 
 * // Get logs by user
 * $logs = LogHistory::getByUser($userId, $conn);
 * 
 * // Get logs by action
 * $logs = LogHistory::getByAction($actionType, $conn);
 * 
 * // Get action name
 * $actionName = $log->getActionName();
 * 
 * 
 * FEEDBACK CLASS
 * ----------
 * 
 * // Get all feedback
 * $feedbacks = Feedback::getAll($conn);
 * 
 * // Get feedback by status
 * $newFeedback = Feedback::getByStatus(1, $conn); // 1 = New
 * 
 * // Create new feedback
 * $feedback = new Feedback($conn);
 * $feedback->setSubmittedBy($userId);
 * $feedback->setSubject('Bug Report');
 * $feedback->setMessage('Found an issue...');
 * $feedback->save();
 * 
 * // Update feedback status
 * $feedback->updateStatus(2); // In Review
 * 
 * // Add response
 * $feedback->addResponse('We are working on this...');
 * 
 * 
 * REPORT CLASS
 * ----------
 * 
 * // Get reports by type
 * $reports = Report::getByType('Project Summary', $conn);
 * 
 * // Get reports by user
 * $reports = Report::getByUser($userId, $conn);
 * 
 * // Create new report
 * $report = new Report($conn);
 * $report->setGeneratedBy($userId);
 * $report->setReportType('Project Summary');
 * $report->setPeriodStart('2025-01-01');
 * $report->setPeriodEnd('2025-12-31');
 * $report->save();
 * 
 * 
 * ARCHIVE CLASS
 * ----------
 * 
 * // Get archived records by type
 * $archived = Archive::getByType('project', $conn);
 * 
 * // Get archived records by department
 * $archived = Archive::getByDepartment($deptId, $conn);
 * 
 * // Archive an entity programmatically
 * Archive::archiveEntity('project', $projectId, $projectName, $description,
 *                        $deptId, null, $currentUserId, 'Ongoing', $conn);
 * 
 * ============================================================================
 * INTEGRATION WITH EXISTING CODE
 * ============================================================================
 * 
 * You don't need to refactor your entire codebase! These classes work 
 * alongside your existing PDO queries. Gradually adopt them:
 * 
 * BEFORE (procedural):
 * ------
 * $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
 * $stmt->execute([$email]);
 * $user = $stmt->fetch(PDO::FETCH_ASSOC);
 * if ($user && password_verify($password, $user['password_hash'])) {
 *     $_SESSION['user_id'] = $user['user_id'];
 * }
 * 
 * AFTER (with classes):
 * -----
 * $user = User::findByEmail($email, $conn);
 * if ($user && $user->verifyPassword($password)) {
 *     $_SESSION['user_id'] = $user->getUserId();
 * }
 * 
 * 
 * BEFORE (procedural):
 * ------
 * $stmt = $conn->prepare("SELECT * FROM projects WHERE project_id = ?");
 * $stmt->execute([$projectId]);
 * $project = $stmt->fetch(PDO::FETCH_ASSOC);
 * $stmt = $conn->prepare("SELECT * FROM tasks WHERE project_id = ?");
 * $stmt->execute([$projectId]);
 * $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
 * $totalTasks = count($tasks);
 * $completedTasks = 0;
 * foreach ($tasks as $task) {
 *     if ($task['status'] == 'Completed') $completedTasks++;
 * }
 * $progress = ($completedTasks / $totalTasks) * 100;
 * 
 * AFTER (with classes):
 * -----
 * $project = Project::findById($projectId, $conn);
 * $progress = $project->getProgress();
 * 
 * ============================================================================
 * BENEFITS
 * ============================================================================
 * 
 * 1. CODE REUSE
 *    Instead of repeating database queries everywhere, use class methods
 * 
 * 2. CONSISTENCY
 *    All user data is handled the same way across the application
 * 
 * 3. MAINTAINABILITY
 *    Need to change how projects work? Update one class instead of 10 files
 * 
 * 4. TYPE SAFETY
 *    IDEs provide autocompletion and catch typos
 * 
 * 5. BUSINESS LOGIC
 *    Complex operations are encapsulated (e.g., task->isOverdue())
 * 
 * 6. RELATIONSHIPS
 *    Easily navigate between related entities (task->getProject()->getManager())
 * 
 * ============================================================================
 * NOTES
 * ============================================================================
 * 
 * - These classes are LIGHTWEIGHT and don't require a framework
 * - They work seamlessly with your existing PDO $conn
 * - You can mix procedural and OOP code while transitioning
 * - All classes extend BaseEntity which handles common DB operations
 * - Static methods (findById, getAll) use the passed $conn parameter
 * - Instance methods use $this->conn set in the constructor
 * 
 * ============================================================================
 */
?>
