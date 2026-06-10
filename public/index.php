<?php
require_once dirname(__DIR__) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__) . '/app/helpers/functions.php';

use App\Helpers\Session;
use App\Helpers\Database;

session(); // Initialize session

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path (supports both subdirectory and root domain)
$basePath = getenv('APP_BASE_PATH') ?: '';
$uri = $basePath ? str_replace($basePath, '', $uri) : $uri;
$uri = '/' . trim($uri, '/');

$method = $_SERVER['REQUEST_METHOD'];
$routes = [];

function route(string $method, string $path, string $controllerAction, bool $auth = true, bool $admin = false): void {
    global $routes;
    $routes[] = compact('method', 'path', 'controllerAction', 'auth', 'admin');
}

// Define all routes here

// Auth routes
route('GET', '/login', 'AuthController@login', false);
route('POST', '/login', 'AuthController@login', false);
route('GET', '/logout', 'AuthController@logout', true);
route('GET', '/forgot-password', 'AuthController@forgotPassword', false);
route('POST', '/forgot-password', 'AuthController@forgotPassword', false);
route('GET', '/verify-otp', 'AuthController@verifyOtp', false);
route('POST', '/verify-otp', 'AuthController@verifyOtp', false);
route('GET', '/reset-password', 'AuthController@resetPassword', false);
route('POST', '/reset-password', 'AuthController@resetPassword', false);

// Dashboard
route('GET', '/', 'DashboardController@index', true);
route('GET', '/dashboard', 'DashboardController@index', true);

// Users (Admin only)
route('GET', '/users', 'UserController@index', true, true);
route('GET', '/users/create', 'UserController@create', true, true);
route('POST', '/users/store', 'UserController@store', true, true);
route('GET', '/users/edit/{id}', 'UserController@edit', true, true);
route('POST', '/users/update/{id}', 'UserController@update', true, true);
route('POST', '/users/delete/{id}', 'UserController@delete', true, true);
route('GET', '/users/view/{id}', 'UserController@view', true, true);
route('POST', '/users/reset-password/{id}', 'UserController@resetPassword', true, true);
route('POST', '/users/update-status/{id}', 'UserController@updateStatus', true, true);
route('POST', '/users/unlock/{id}', 'UserController@unlock', true, true);

// Roles (Admin only)
route('GET', '/roles', 'RoleController@index', true, true);
route('GET', '/roles/create', 'RoleController@create', true, true);
route('POST', '/roles/store', 'RoleController@store', true, true);
route('GET', '/roles/edit/{id}', 'RoleController@edit', true, true);
route('POST', '/roles/update/{id}', 'RoleController@update', true, true);
route('POST', '/roles/delete/{id}', 'RoleController@delete', true, true);
route('POST', '/roles/clone/{id}', 'RoleController@clone', true, true);
route('POST', '/roles/permissions/{id}', 'RoleController@updatePermissions', true, true);
route('GET', '/roles/permissions/{id}', 'RoleController@permissions', true, true);

// Permissions (Admin only)
route('GET', '/permissions', 'PermissionController@index', true, true);
route('POST', '/permissions/update-role', 'PermissionController@updateRolePermission', true, true);
route('POST', '/permissions/update-user', 'PermissionController@updateUserPermission', true, true);
route('GET', '/permissions/role/{id}', 'PermissionController@getRolePermissions', true, true);
route('GET', '/permissions/user/{id}', 'PermissionController@getUserPermissions', true, true);
route('POST', '/permissions/save', 'PermissionController@batchUpdate', true, true);
route('POST', '/permissions/clone', 'PermissionController@clonePermissions', true, true);

// Tasks
route('GET', '/tasks', 'TaskController@index', true);
route('GET', '/tasks/create', 'TaskController@create', true);
route('POST', '/tasks/store', 'TaskController@store', true);
route('GET', '/tasks/edit/{id}', 'TaskController@edit', true);
route('POST', '/tasks/update/{id}', 'TaskController@update', true);
route('POST', '/tasks/delete/{id}', 'TaskController@delete', true);
route('GET', '/tasks/view/{id}', 'TaskController@view', true);
route('POST', '/tasks/assign/{id}', 'TaskController@assign', true);
route('POST', '/tasks/reassign/{id}', 'TaskController@reassign', true);
route('POST', '/tasks/complete/{id}', 'TaskController@complete', true);
route('POST', '/tasks/reopen/{id}', 'TaskController@reopen', true);
route('POST', '/tasks/in-progress/{id}', 'TaskController@inProgress', true);
route('POST', '/tasks/archive/{id}', 'TaskController@archive', true);
route('POST', '/tasks/clone/{id}', 'TaskController@clone', true);
route('POST', '/tasks/bulk-action', 'TaskController@bulkAction', true);
route('GET', '/tasks/my-tasks', 'TaskController@myTasks', true);
route('GET', '/tasks/sent-tasks', 'TaskController@sentTasks', true);
route('GET', '/tasks/abused-tasks', 'TaskController@abusedTasks', true);
route('GET', '/tasks/workload', 'TaskController@workload', true);
route('GET', '/tasks/my-files', 'TaskController@myFiles', true);
route('POST', '/tasks/my-files/upload', 'TaskController@uploadMyFile', true);
route('POST', '/tasks/my-files/delete/{id}', 'TaskController@deleteMyFile', true);
route('GET', '/tasks/my-files/download/{id}', 'TaskController@downloadMyFile', true);
route('POST', '/tasks/dependencies/add/{id}', 'TaskController@addDependency', true);
route('POST', '/tasks/dependencies/remove/{id}', 'TaskController@removeDependency', true);
route('POST', '/tasks/watchers/add/{id}', 'TaskController@addWatcher', true);
route('POST', '/tasks/watchers/remove/{id}', 'TaskController@removeWatcher', true);
route('GET', '/tasks/templates', 'TaskController@templates', true);
route('GET', '/tasks/templates/create-from/{id}', 'TaskController@fromTemplate', true);
route('POST', '/tasks/templates/store', 'TaskController@storeTemplate', true);
route('POST', '/tasks/progress/{id}', 'TaskController@updateProgress', true);
route('POST', '/tasks/tags/add/{id}', 'TaskController@addTag', true);
route('POST', '/tasks/tags/remove/{id}', 'TaskController@removeTag', true);

// Task Comments
route('POST', '/tasks/comments/store', 'TaskCommentController@store', true);
route('POST', '/tasks/comments/delete/{id}', 'TaskCommentController@delete', true);

// Task Attachments
route('POST', '/tasks/attachments/upload', 'TaskAttachmentController@upload', true);
route('GET', '/tasks/attachments/download/{id}', 'TaskAttachmentController@download', true);
route('POST', '/tasks/attachments/delete/{id}', 'TaskAttachmentController@delete', true);

// Meetings
route('GET', '/meetings', 'MeetingController@index', true);
route('GET', '/meetings/create', 'MeetingController@create', true);
route('POST', '/meetings/store', 'MeetingController@store', true);
route('GET', '/meetings/edit/{id}', 'MeetingController@edit', true);
route('POST', '/meetings/update/{id}', 'MeetingController@update', true);
route('POST', '/meetings/delete/{id}', 'MeetingController@delete', true);
route('GET', '/meetings/view/{id}', 'MeetingController@view', true);
route('POST', '/meetings/sessions/store/{id}', 'MeetingController@storeSession', true);
route('POST', '/meetings/sessions/delete/{id}', 'MeetingController@deleteSession', true);
route('POST', '/meetings/tasks/store/{id}', 'MeetingController@storeTask', true);

// Special Meeting Requests
route('GET', '/meetings/special-requests', 'MeetingController@specialRequests', true);
route('GET', '/meetings/special-requests/create', 'MeetingController@createSpecial', true);
route('POST', '/meetings/special-requests/store', 'MeetingController@storeSpecial', true);
route('POST', '/meetings/special-requests/approve/{id}', 'MeetingController@approveSpecial', true, true);

// Notifications
route('GET', '/notifications', 'NotificationController@index', true);
route('GET', '/notifications/unread-count', 'NotificationController@getUnreadCount', true);
route('POST', '/notifications/mark-read/{id}', 'NotificationController@markAsRead', true);
route('POST', '/notifications/mark-all-read', 'NotificationController@markAllAsRead', true);
route('POST', '/notifications/delete/{id}', 'NotificationController@delete', true);

// Reports (Admin only)
route('GET', '/reports', 'ReportController@index', true, true);
route('GET', '/reports/{type}', 'ReportController@index', true, true);
route('GET', '/reports/{type}/export/{format}', 'ReportController@export', true, true);
route('POST', '/reports/{type}/export/email', 'ReportController@exportEmail', true, true);

// Activity Logs (Admin only)
route('GET', '/activity-logs', 'ActivityLogController@index', true, true);

// Database Backup (Admin only)
route('GET', '/backups', 'BackupController@index', true, true);
route('POST', '/backups/create', 'BackupController@create', true, true);
route('POST', '/backups/restore/{id}', 'BackupController@restore', true, true);
route('GET', '/backups/download/{id}', 'BackupController@download', true, true);
route('POST', '/backups/delete/{id}', 'BackupController@delete', true, true);

// Profile
route('GET', '/profile', 'ProfileController@index', true);
route('POST', '/profile/update', 'ProfileController@update', true);
route('POST', '/profile/change-password', 'ProfileController@changePassword', true);
route('GET', '/profile/login-history', 'ProfileController@loginHistory', true);
route('GET', '/profile/activity-history', 'ProfileController@activityHistory', true);
route('POST', '/profile/notification-preferences', 'ProfileController@notificationPreferences', true);

// Settings (Admin only)
route('GET', '/settings', 'SettingController@index', true, true);
route('POST', '/settings/general', 'SettingController@updateGeneral', true, true);
route('POST', '/settings/email', 'SettingController@updateEmail', true, true);
route('POST', '/settings/security', 'SettingController@updateSecurity', true, true);
route('POST', '/settings/send-test-email', 'SettingController@sendTestEmail', true, true);

// Departments (Admin only)
route('GET', '/departments', 'DepartmentController@index', true, true);
route('GET', '/departments/create', 'DepartmentController@create', true, true);
route('POST', '/departments/store', 'DepartmentController@store', true, true);
route('GET', '/departments/edit/{id}', 'DepartmentController@edit', true, true);
route('POST', '/departments/update/{id}', 'DepartmentController@update', true, true);
route('POST', '/departments/delete/{id}', 'DepartmentController@delete', true, true);

// Categories (Admin only)
route('GET', '/categories', 'CategoryController@index', true, true);
route('GET', '/categories/create', 'CategoryController@create', true, true);
route('POST', '/categories/store', 'CategoryController@store', true, true);
route('GET', '/categories/edit/{id}', 'CategoryController@edit', true, true);
route('POST', '/categories/update/{id}', 'CategoryController@update', true, true);
route('POST', '/categories/delete/{id}', 'CategoryController@delete', true, true);

// Employee Ideas
route('GET', '/employee-ideas', 'EmployeeIdeaController@index', true);
route('GET', '/employee-ideas/create', 'EmployeeIdeaController@create', true);
route('POST', '/employee-ideas/store', 'EmployeeIdeaController@store', true);
route('GET', '/employee-ideas/view/{id}', 'EmployeeIdeaController@view', true);
route('GET', '/employee-ideas/edit/{id}', 'EmployeeIdeaController@edit', true);
route('POST', '/employee-ideas/update/{id}', 'EmployeeIdeaController@update', true);
route('POST', '/employee-ideas/delete/{id}', 'EmployeeIdeaController@delete', true);
route('POST', '/employee-ideas/review/{id}', 'EmployeeIdeaController@review', true);

// User Files
route('GET', '/user-files', 'UserFileController@index', true);
route('POST', '/user-files/upload', 'UserFileController@upload', true);
route('GET', '/user-files/download/{id}', 'UserFileController@download', true);
route('POST', '/user-files/delete/{id}', 'UserFileController@delete', true);

// Try to match route
$matched = false;
foreach ($routes as $route) {
    // Convert route path to regex
    $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route['path']);
    $pattern = '#^' . $pattern . '$#';

    if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
        // Check auth
        if ($route['auth']) {
            \App\Middleware\AuthMiddleware::handle();
        }
        if ($route['admin']) {
            \App\Middleware\AdminMiddleware::handle();
        }

        // Check remember me
        if (!$route['auth'] && !session()->has('user')) {
            $authService = new \App\Services\AuthService();
            $authService->attemptRememberMeLogin();
        }

        // Parse controller and method
        [$controllerName, $methodName] = explode('@', $route['controllerAction']);
        $controllerClass = "App\\Controllers\\{$controllerName}";

        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo "Controller {$controllerClass} not found";
            exit;
        }

        $controller = new $controllerClass();

        // Extract named parameters
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

        // Check CSRF for POST/PUT/DELETE
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!validate_csrf($token)) {
                http_response_code(419);
                echo "CSRF token mismatch";
                exit;
            }
        }

        // Send security headers
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;");

        // Handle AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $result = call_user_func_array([$controller, $methodName], $params);
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            call_user_func_array([$controller, $methodName], $params);
        }

        $matched = true;
        break;
    }
}

if (!$matched) {
    http_response_code(404);
    echo "Page not found";
}

// === Additional helper: Schedule check for overdue tasks ===
// This runs on every request to mark overdue tasks
try {
    $db = Database::getInstance();
    $db->query(
        "UPDATE tasks SET status = 'Overdue' 
         WHERE due_date < CURDATE() 
         AND status NOT IN ('Completed', 'Cancelled', 'Overdue') 
         AND deleted_at IS NULL"
    );
} catch (\Exception $e) {
    logError('Overdue task check failed: ' . $e->getMessage());
}
