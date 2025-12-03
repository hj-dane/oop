<?php
/**
 * Autoloader for Entity Classes
 * Include this file at the top of your PHP files to automatically load entity classes
 * 
 * Usage: require_once 'classes/autoload.php';
 */

// Define the base path to the classes directory
$classesPath = dirname(__FILE__);

// Autoloader function
spl_autoload_register(function ($class) use ($classesPath) {
    // Convert class name to file name (e.g., User -> User.php)
    $file = $classesPath . DIRECTORY_SEPARATOR . $class . '.php';
    
    // Check if file exists and include it
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

/**
 * Manual class inclusion if needed
 * If autoloader doesn't work, you can manually require classes:
 * 
 * require_once 'classes/BaseEntity.php';
 * require_once 'classes/User.php';
 * require_once 'classes/Department.php';
 * require_once 'classes/Project.php';
 * require_once 'classes/Task.php';
 * require_once 'classes/Announcement.php';
 * require_once 'classes/Feedback.php';
 * require_once 'classes/Report.php';
 * require_once 'classes/TaskHistory.php';
 * require_once 'classes/LogHistory.php';
 * require_once 'classes/Archive.php';
 */
?>
