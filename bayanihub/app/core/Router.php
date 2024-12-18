<?php
require_once __DIR__ . '/../controllers/HelpRequestController.php';
require_once __DIR__ . '/../controllers/ResourceSharingController.php';  
require_once __DIR__ . '/../controllers/EventsController.php';  
require_once __DIR__ . '/../controllers/PostController.php';

class Router {
    private $viewsBase;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->viewsBase = __DIR__ . '/../../views';
    }

     public function route() {
        $page = strtolower($_GET['page'] ?? 'login');
        $routeHandlers = [
            'login' => 'handleLogin',
            'help_requests' => 'handleHelpRequests',
            'dashboard' => 'handleDashboard',
            'admindashboard' => 'handleAdminDashboard',
            'register' => 'handleRegister',
            'profile' => 'handleProfileView',
            'logout' => 'handleLogout',
            'events' => 'handleEvents',
            'create_event' => 'handleCreateEvent',
            'resource_sharing' => 'handleResourceSharing',
            'create_post' => 'handleCreatePost',
            'approve_request' => 'handleApproveRequest',
            'reject_request' => 'handleRejectRequest',
        ];

        if (isset($routeHandlers[$page])) {
            $this->{$routeHandlers[$page]}();
        } else {
            $this->show404();
        }
    }

    private function handleApproveRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $requestId = $_POST['request_id'] ?? null;
            if ($requestId) {
                $database = new Database();
                $pdo = $database->connect();
                $controller = new HelpRequestController($pdo);
                if ($controller->approveRequest($requestId)) {
                    header('Location: ?page=admindashboard&success=approved');
                } else {
                    header('Location: ?page=admindashboard&error=approve_failed');
                }
                exit();
            }
        }
    }

    private function handleRejectRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $requestId = $_POST['request_id'] ?? null;
            if ($requestId) {
                $database = new Database();
                $pdo = $database->connect();
                $controller = new HelpRequestController($pdo);
                if ($controller->rejectRequest ($requestId)) {
                    header('Location: ?page=admindashboard&success=rejected');
                } else {
                    header('Location: ?page=admindashboard&error=reject_failed');
                }
                exit();
            }
        }
    }

    private function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new AuthController();
            $controller->login();
            if (isset($_SESSION['user'])) {
                $role = $_SESSION['user']['role'];
                if ($role == 'admin') {
                    header('Location: ?page=adminDashboard');
                    exit;
                } elseif ($role == 'member') {
                    header('Location: ?page=dashboard');
                    exit;
                } else {
                    header('Location: ?page=login');
                    exit;
                }
            } else {
                header('Location: ?page=login');
                exit;
            }
        } else {
            $this->loadView('auth/login.php');
        }
    }

    private function handleLogout() {
        $controller = new AuthController();
        $controller->logout();
    }

    public function handleProfileView() {
        require_once './app/views/profile.php';
    }


    private function handleHelpRequests() {
        if (!class_exists('HelpRequestController')) {
            echo "HelpRequestController class not found!";
            exit;
        }

        $controller = new HelpRequestController(); 
        $controller->handleFormSubmission(); 
        $helpRequests = $controller->getHelpRequests();
        $this->loadView('help_requests_list.php', ['helpRequests' => $helpRequests]);
    }

    private function handleDashboard() {
        if (!isset($_SESSION['user'])) {
            echo "No user session found. Redirecting to login.";
            header("Location: ?page=login");
            exit;
        }
    
        $user = $_SESSION['user'];
    
        $database = new Database();
        $pdo = $database->connect();
    
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_post_id'])) {
            $postId = $_GET['delete_post_id'];
            $controller = new PostController($pdo);
    
            try {
                $controller->deletePost($postId, $user['id']);
                header("Location: ?page=dashboard&success=true");
                exit;
            } catch (Exception $e) {
                echo "Error deleting post: " . $e->getMessage();
            }
        }
        
        if ($user['role'] === 'admin') {
            $controller = new DashboardController($pdo);
            $pendingRequests = $controller->getPendingRequests();
            $this->loadView('auth/adminDashboard.php', ['pendingRequests' => $pendingRequests]);
        } elseif ($user['role'] === 'member') {
            $controller = new DashboardController($pdo);
            $recentActivities = $controller->getRecentActivities();
            $this->loadView('auth/dashboard.php', ['recentActivities' => $recentActivities]);
        }
    }
    
    private function handleCreatePost() {
        if (!isset($_SESSION['user'])) {
            echo "No user session found. Redirecting to login.";
            header("Location: ?page=login");
            exit;
        }
    
        // Get user details from the session
        $user = $_SESSION['user'];
    
        // Initialize the database connection and controller
        $database = new Database();
        $pdo = $database->connect();
        $controller = new PostController($pdo);
    
        // Check if form is submitted via POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postText = $_POST['post_text'] ?? null;
            $postType = $_POST['post_type'] ?? 'text'; // Default to 'text' type post
    
            // Validate and create the post
            try {
                if ($postType === 'text' && !empty($postText)) {
                    // Call the controller to create the text post
                    $controller->createTextPost($user['id'], $postText);
    
                    // After successful post creation, redirect to the dashboard
                    header("Location: ?page=dashboard&success=true");
                    exit; // Stop further execution
                } else {
                    throw new Exception("Invalid post data.");
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage(); // Handle the error if something goes wrong
            }
        }
    
        // If it's a GET request, you may show a form (not shown in your code but could be useful)
        // Load the view for creating the post (Optional if you need a page for post creation)
        $this->loadView('create_post_form.php');
    }
    
    
    

    private function handleRegister() {
        $controller = new AuthController();
        $controller->register();
    }

    private function loadView($viewFile, $data = []) {
        extract($data);
        $rootPath = dirname(__DIR__, 2); 
        $fullPath = $rootPath . '/app/views/' . $viewFile; 
        
        if (file_exists($fullPath)) {
            include $fullPath;
        } else {
            echo "View file not found: $fullPath<br>"; 
            $this->show404();
        }
    }

    private function show404() {
        header('HTTP/1.0 404 Not Found');
        echo "<h1>404 - Page Not Found</h1>";
    }

    private function handleEvents($action = 'list') {
        $database = new Database();
        $pdo = $database->connect();
    
        $eventsController = new EventsController($pdo);
    
        if ($action === 'create') {
            $eventsController->create(); 
        }
    
        switch ($action) {
            case 'list':
                $filters = [
                    'location' => $_GET['location'] ?? null,
                    'date' => $_GET['date'] ?? null,
                ];
                $events = $eventsController->getEvents($filters); 
                $this->loadView('events_list.php', ['events' => $events]);
                break;
    
            case 'rsvp':
                $eventId = $_POST['id'] ?? null;
                $userId = $_POST['user_id'] ?? null;
    
                if ($eventId && $userId) {
                    $eventsController->rsvpToEvent($eventId, $userId);
                } else {
                    echo "Missing event_id or user_id.";
                }
                break;
    
            default:
                $this->show404();
                break;
        }
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $event_name = isset($_POST['event_name']) ? htmlspecialchars($_POST['event_name']) : '';
            $location = isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '';
            $event_date = isset($_POST['event_date']) ? $_POST['event_date'] : '';

            if (empty($event_name) || empty($location) || empty($event_date)) {
                $_SESSION['error'] = 'All fields are required.';
                header('Location: ?page=events&action=create');
                exit();
            }

            $query = "INSERT INTO events (event_name, location, event_date) VALUES (:event_name, :location, :event_date)";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':event_name', $event_name);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':event_date', $event_date);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Event created successfully!';
            } else {
                $_SESSION['error'] = 'Failed to create event. Please try again.';
            }
            header('Location: ?page=events');
            exit();
        }

        $this->loadView('create_event.php');
    }

    private function handleResourceSharing() {
        $controller = new ResourceSharingController();
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle saving the resource
            $user_id = $_SESSION['user']['id'] ?? null; // Ensure the user is logged in
            $name = $_POST['name'] ?? '';
            $category = $_POST['category'] ?? '';
            $description = $_POST['description'] ?? null;
            $availability = $_POST['availability'] ?? 'available';
    
            if ($user_id && !empty($name)) {
                $success = $controller->createResource($user_id, $name, $category, $description, $availability);
                if ($success) {
                    $_SESSION['success_message'] = "Resource added successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to add resource. Please try again.";
                }
            } else {
                $_SESSION['error_message'] = "Please fill in all required fields.";
            }
    
            // Redirect to avoid form resubmission
            header("Location: ?page=resource_sharing");
            exit;
        }
    
        // Handle fetching resources
        $filters = [];
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $filters['category'] = $_GET['category'];
        }
        if (isset($_GET['availability']) && !empty($_GET['availability'])) {
            $filters['availability'] = $_GET['availability'];
        }
        if (isset($_GET['location']) && !empty($_GET['location'])) {
            $filters['location'] = $_GET['location'];
        }
    
        $resources = $controller->getResources($filters);
    
        // Load the view and pass data
        $this->loadView('resource_sharing.php', ['resources' => $resources]);
    }
    
    

    private function handleAdminDashboard() {
        if (!isset($_SESSION['user'])) {
            echo "No user session found. Redirecting to login.";
            header("Location: ?page=login");
            exit;
        }

        $user = $_SESSION['user'];

        if ($user['role'] !== 'admin') {
            echo "Access Denied: You are not an admin.";
            header("Location: ?page=login");
            exit;
        }

        $database = new Database();
        $pdo = $database->connect();

        $controller = new DashboardController($pdo);
        $pendingRequests = $controller->getPendingRequests();
        $this->loadView('auth/adminDashboard.php', ['pendingRequests' => $pendingRequests]);
    }
}


?>