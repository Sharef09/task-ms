<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\Meeting;
use App\Models\MeetingSession;
use App\Models\MeetingTask;
use App\Models\SpecialMeeting;
use App\Models\User;
use App\Models\Department;

class MeetingController
{
    private Database $db;
    private Session $session;
    private Meeting $meetingModel;
    private MeetingSession $sessionModel;
    private MeetingTask $meetingTaskModel;
    private SpecialMeeting $specialMeetingModel;
    private User $userModel;
    private Department $departmentModel;

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->meetingModel = new Meeting();
        $this->sessionModel = new MeetingSession();
        $this->meetingTaskModel = new MeetingTask();
        $this->specialMeetingModel = new SpecialMeeting();
        $this->userModel = new User();
        $this->departmentModel = new Department();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    // ===== MAIN MEETINGS =====

    public function index(): void
    {
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 15;
            $filters = [
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? '',
                'department_id' => $_GET['department_id'] ?? '',
            ];
            $meetings = $this->meetingModel->getAll($page, $perPage, $filters);
            $total = $this->db->fetch("SELECT COUNT(*) as cnt FROM meetings WHERE deleted_at IS NULL")->cnt ?? 0;
            $totalPages = ceil($total / $perPage);
            $departments = $this->departmentModel->getAll();
            $pendingRequests = $this->specialMeetingModel->countPending();

            $pageTitle = 'Meetings';
            $content = __DIR__ . '/../views/meetings/index.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Meeting index error', $e);
            flash('error', 'Unable to load meetings');
            redirect('dashboard');
        }
    }

    public function create(): void
    {
        try {
            $users = $this->userModel->getActive();
            $departments = $this->departmentModel->getAll();
            $statuses = ['Scheduled', 'Ongoing', 'Completed', 'Cancelled'];

            $pageTitle = 'Create Meeting';
            $content = __DIR__ . '/../views/meetings/create.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Meeting create form error', $e);
            flash('error', 'Unable to load form');
            redirect('meetings');
        }
    }

    public function store(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('meetings/create');
                return;
            }
            $user = $this->session->get('user');
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
                'organizer_id' => !empty($_POST['organizer_id']) ? (int)$_POST['organizer_id'] : $user->id,
                'status' => $_POST['status'] ?? 'Scheduled',
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'location' => trim($_POST['location'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            if (empty($data['title'])) {
                flash('error', 'Meeting title is required');
                redirect('meetings/create');
                return;
            }
            $meetingId = $this->meetingModel->create($data);
            if ($meetingId) {
                logActivity('Create Meeting', 'Meetings', $meetingId, null, json_encode(['title' => $data['title']]));
                flash('success', 'Meeting created successfully');
            } else {
                flash('error', 'Failed to create meeting');
            }
            redirect('meetings');
        } catch (\Throwable $e) {
            logError('Meeting store error', $e);
            flash('error', 'Failed to create meeting');
            redirect('meetings/create');
        }
    }

    public function edit(int $id): void
    {
        try {
            $meeting = $this->meetingModel->getById($id);
            if (!$meeting) {
                flash('error', 'Meeting not found');
                redirect('meetings');
                return;
            }
            $users = $this->userModel->getActive();
            $departments = $this->departmentModel->getAll();
            $statuses = ['Scheduled', 'Ongoing', 'Completed', 'Cancelled'];

            $pageTitle = 'Edit Meeting';
            $content = __DIR__ . '/../views/meetings/edit.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Meeting edit error', $e);
            flash('error', 'Unable to load form');
            redirect('meetings');
        }
    }

    public function update(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('meetings/edit/' . $id);
                return;
            }
            $meeting = $this->meetingModel->getById($id);
            if (!$meeting) {
                flash('error', 'Meeting not found');
                redirect('meetings');
                return;
            }
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
                'organizer_id' => !empty($_POST['organizer_id']) ? (int)$_POST['organizer_id'] : $meeting->organizer_id,
                'status' => $_POST['status'] ?? 'Scheduled',
                'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'location' => trim($_POST['location'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
            ];
            if (empty($data['title'])) {
                flash('error', 'Meeting title is required');
                redirect('meetings/edit/' . $id);
                return;
            }
            $this->meetingModel->update($id, $data);
            logActivity('Update Meeting', 'Meetings', $id);
            flash('success', 'Meeting updated successfully');
            redirect('meetings');
        } catch (\Throwable $e) {
            logError('Meeting update error', $e);
            flash('error', 'Failed to update meeting');
            redirect('meetings/edit/' . $id);
        }
    }

    public function delete(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('meetings');
                return;
            }
            $this->meetingModel->delete($id);
            logActivity('Delete Meeting', 'Meetings', $id);
            flash('success', 'Meeting deleted successfully');
            redirect('meetings');
        } catch (\Throwable $e) {
            logError('Meeting delete error', $e);
            flash('error', 'Failed to delete meeting');
            redirect('meetings');
        }
    }

    // ===== MEETING SESSION =====

    public function view(int $id): void
    {
        try {
            $meeting = $this->meetingModel->getById($id);
            if (!$meeting) {
                flash('error', 'Meeting not found');
                redirect('meetings');
                return;
            }
            $sessions = $this->sessionModel->getByMeeting($id);
            $tasks = $this->meetingTaskModel->getByMeeting($id);
            $users = $this->userModel->getActive();

            $pageTitle = $meeting->title;
            $content = __DIR__ . '/../views/meetings/view.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Meeting view error', $e);
            flash('error', 'Unable to load meeting');
            redirect('meetings');
        }
    }

    public function storeSession(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('meetings/view/' . $id);
                return;
            }
            $data = [
                'meeting_id' => $id,
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'presenter_id' => !empty($_POST['presenter_id']) ? (int)$_POST['presenter_id'] : null,
                'duration_minutes' => !empty($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : null,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
                'created_at' => date('Y-m-d H:i:s'),
            ];
            if (empty($data['title'])) {
                flash('error', 'Session title is required');
                redirect('meetings/view/' . $id);
                return;
            }
            $this->sessionModel->create($data);
            logActivity('Add Session', 'Meetings', $id);
            flash('success', 'Session added successfully');
            redirect('meetings/view/' . $id);
        } catch (\Throwable $e) {
            logError('Store session error', $e);
            flash('error', 'Failed to add session');
            redirect('meetings/view/' . $id);
        }
    }

    public function deleteSession(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('meetings');
                return;
            }
            $this->sessionModel->delete($id);
            flash('success', 'Session deleted');
            redirect('meetings');
        } catch (\Throwable $e) {
            logError('Delete session error', $e);
            flash('error', 'Failed to delete session');
            redirect('meetings');
        }
    }

    // ===== MEETING TASKS =====

    public function storeTask(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('meetings/view/' . $id);
                return;
            }
            $user = $this->session->get('user');
            $data = [
                'meeting_id' => $id,
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'status' => 'Pending',
                'created_by' => $user->id,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            if (empty($data['title'])) {
                flash('error', 'Task title is required');
                redirect('meetings/view/' . $id);
                return;
            }
            $this->meetingTaskModel->create($data);
            logActivity('Create Meeting Task', 'Meetings', $id);
            flash('success', 'Task added to meeting');
            redirect('meetings/view/' . $id);
        } catch (\Throwable $e) {
            logError('Store meeting task error', $e);
            flash('error', 'Failed to add task');
            redirect('meetings/view/' . $id);
        }
    }

    // ===== SPECIAL MEETINGS =====

    public function specialRequests(): void
    {
        try {
            $user = $this->session->get('user');
            $isAdmin = isAdmin();
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 15;
            $filters = ['status' => $_GET['status'] ?? ''];
            if (!$isAdmin) {
                $filters['requester_id'] = $user->id;
            }
            $requests = $this->specialMeetingModel->getAll($page, $perPage, $filters);
            $total = $this->db->fetch("SELECT COUNT(*) FROM special_meetings")->{'COUNT(*)'} ?? 0;
            $totalPages = ceil($total / $perPage);

            $pageTitle = 'Special Meeting Requests';
            $content = __DIR__ . '/../views/meetings/special-requests.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Special requests error', $e);
            flash('error', 'Unable to load requests');
            redirect('dashboard');
        }
    }

    public function createSpecial(): void
    {
        try {
            $departments = $this->departmentModel->getAll();
            $pageTitle = 'Request Special Meeting';
            $content = __DIR__ . '/../views/meetings/create-special.php';
            include dirname(__DIR__, 2) . '/layouts/main-layout.php';
        } catch (\Throwable $e) {
            logError('Create special form error', $e);
            flash('error', 'Unable to load form');
            redirect('meetings/special-requests');
        }
    }

    public function storeSpecial(): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('meetings/special-requests/create');
                return;
            }
            $user = $this->session->get('user');
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'requester_id' => $user->id,
                'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
                'preferred_date' => !empty($_POST['preferred_date']) ? $_POST['preferred_date'] : null,
                'status' => 'Pending',
                'created_at' => date('Y-m-d H:i:s'),
            ];
            if (empty($data['title'])) {
                flash('error', 'Title is required');
                redirect('meetings/special-requests/create');
                return;
            }
            $this->specialMeetingModel->create($data);
            logActivity('Request Special Meeting', 'Meetings', null, null, json_encode(['title' => $data['title']]));
            flash('success', 'Special meeting request submitted for approval');
            redirect('meetings/special-requests');
        } catch (\Throwable $e) {
            logError('Store special error', $e);
            flash('error', 'Failed to submit request');
            redirect('meetings/special-requests/create');
        }
    }

    public function approveSpecial(int $id): void
    {
        try {
            if (!validate_csrf($_POST['_csrf_token'] ?? '')) {
                flash('error', 'Invalid security token');
                redirect('meetings/special-requests');
                return;
            }
            $user = $this->session->get('user');
            $request = $this->specialMeetingModel->getById($id);
            if (!$request) {
                flash('error', 'Request not found');
                redirect('meetings/special-requests');
                return;
            }

            $action = $_POST['action'] ?? '';
            if ($action === 'approve') {
                $meetingId = null;
                if (!empty($_POST['create_meeting']) && (int)$_POST['create_meeting'] === 1) {
                    $meetingId = $this->meetingModel->create([
                        'title' => $request->title,
                        'description' => $request->description,
                        'department_id' => $request->department_id,
                        'organizer_id' => $request->requester_id,
                        'status' => 'Scheduled',
                        'start_date' => $request->preferred_date,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $this->specialMeetingModel->update($id, [
                    'status' => 'Approved',
                    'approved_by' => $user->id,
                    'approved_at' => date('Y-m-d H:i:s'),
                    'meeting_id' => $meetingId,
                ]);
                logActivity('Approve Special Meeting', 'Meetings', $id);
                flash('success', 'Special meeting request approved');
            } elseif ($action === 'reject') {
                $reason = trim($_POST['rejection_reason'] ?? '');
                $this->specialMeetingModel->update($id, [
                    'status' => 'Rejected',
                    'approved_by' => $user->id,
                    'approved_at' => date('Y-m-d H:i:s'),
                    'rejection_reason' => $reason,
                ]);
                logActivity('Reject Special Meeting', 'Meetings', $id);
                flash('success', 'Special meeting request rejected');
            }
            redirect('meetings/special-requests');
        } catch (\Throwable $e) {
            logError('Approve special error', $e);
            flash('error', 'Failed to process request');
            redirect('meetings/special-requests');
        }
    }
}
