<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\Notification;

class NotificationController
{
    private Database $db;
    private Session $session;
    private Notification $notificationModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->notificationModel = new Notification();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(): void
    {
        try {
            $user = $this->session->get('user');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 25;

            $notifications = $this->notificationModel->getByUser($user->id, $page, $perPage);
            $total = $this->db->fetch(
                "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ?",
                [$user->id]
            )->cnt ?? 0;
            $totalPages = ceil($total / $perPage);

            $pageTitle = 'Notifications';
            $content = __DIR__ . '/../views/notifications/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Notification index error', $e);
            flash('error', 'Unable to load notifications');
            redirect('dashboard');
        }
    }

    public function getUnreadCount(): void
    {
        try {
            $user = $this->session->get('user');
            $count = $this->notificationModel->getUnreadCount($user->id);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'count' => $count]);

        } catch (\Throwable $e) {
            logError('Get unread count error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'count' => 0]);
        }
    }

    public function markAsRead(int $id): void
    {
        try {
            $user = $this->session->get('user');
            $this->notificationModel->markAsRead($id, $user->id);

            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

            if ($isAjax) {
                echo json_encode(['success' => true]);
                return;
            }

            $notif = $this->db->fetch("SELECT link FROM notifications WHERE id = ?", [$id]);

            if ($notif && !empty($notif->link)) {
                redirect(trim($notif->link, '/'));
            } else {
                redirect('notifications');
            }

        } catch (\Throwable $e) {
            logError('Mark as read error', $e);
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
            } else {
                redirect('notifications');
            }
        }
    }

    public function markAllAsRead(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $user = $this->session->get('user');
            $this->notificationModel->markAllAsRead($user->id);

            logActivity('Mark All Read', 'Notifications');

            echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);

        } catch (\Throwable $e) {
            logError('Mark all as read error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to mark all as read']);
        }
    }

    public function delete(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                return;
            }

            $user = $this->session->get('user');
            $this->notificationModel->delete($id, $user->id);

            logActivity('Delete Notification', 'Notifications', $id);

            echo json_encode(['success' => true, 'message' => 'Notification deleted']);

        } catch (\Throwable $e) {
            logError('Delete notification error', $e);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
        }
    }
}
