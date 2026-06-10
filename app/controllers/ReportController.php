<?php

namespace App\Controllers;

require_once dirname(__DIR__, 2) . '/app/helpers/autoloader.php';
require_once dirname(__DIR__, 2) . '/app/helpers/functions.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\Report;
use App\Models\User;
use App\Models\Department;

class ReportController
{
    private Database $db;
    private Session $session;
    private Report $reportModel;
    private User $userModel;
    private Department $departmentModel;

    private array $reportTypes = ['tasks', 'users', 'performance', 'departments', 'activity', 'login', 'audit', 'notifications'];

    public function __construct()
    {
        session();
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
        $this->reportModel = new Report();
        $this->userModel = new User();
        $this->departmentModel = new Department();

        if (!$this->session->has('user')) {
            redirect('login');
        }
    }

    public function index(string $type = 'tasks'): void
    {
        try {
            if (!in_array($type, $this->reportTypes)) {
                $type = 'tasks';
            }

            $filters = $this->getFilters();
            $reportData = $this->getReportData($type, $filters);
            $chartData = $this->reportModel->getChartData($type, $filters);
            $users = $this->userModel->getActive();
            $departments = $this->departmentModel->getAll();
            $statuses = ['Open', 'Assigned', 'In Progress', 'Completed', 'Overdue', 'Cancelled', 'On Hold'];
            $priorities = ['Low', 'Medium', 'High', 'Urgent'];

            $reportType = $type;

            $pageTitle = ucfirst($type) . ' Report';
            $content = __DIR__ . '/../views/reports/' . $type . '.php';

            if (!file_exists($content)) {
                $content = __DIR__ . '/../views/reports/index.php';
            }

            include dirname(__DIR__, 2) . '/layouts/main-layout.php';

        } catch (\Throwable $e) {
            logError('Report index error', $e);
            flash('error', 'Unable to load report');
            redirect('dashboard');
        }
    }

    public function export(string $type, string $format): void
    {
        try {
            if (!in_array($type, $this->reportTypes)) {
                flash('error', 'Invalid report type');
                redirect('reports');
                return;
            }

            if (!in_array($format, ['xlsx', 'csv', 'pdf', 'txt', 'print'])) {
                flash('error', 'Invalid export format');
                redirect('reports/' . $type);
                return;
            }

            $filters = $this->getFilters();
            $data = $this->getReportData($type, $filters);

            switch ($format) {
                case 'xlsx':
                    $this->exportXlsx($type, $data);
                    break;
                case 'csv':
                    $this->exportCsv($type, $data);
                    break;
                case 'pdf':
                    $this->exportPdf($type, $data);
                    break;
                case 'txt':
                    $this->exportTxt($type, $data);
                    break;
                case 'print':
                    $this->exportPrint($type, $data);
                    break;
            }

        } catch (\Throwable $e) {
            logError('Report export error', $e);
            flash('error', 'Failed to export report');
            redirect('reports/' . $type);
        }
    }

    private function getFilters(): array
    {
        return [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'assigned_to' => $_GET['assigned_to'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'user_id' => $_GET['user_id'] ?? '',
            'role_id' => $_GET['role_id'] ?? '',
            'action' => $_GET['action'] ?? '',
            'module' => $_GET['module'] ?? '',
            'type' => $_GET['type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];
    }

    private function getReportData(string $type, array $filters): array
    {
        return match($type) {
            'tasks' => $this->reportModel->getTaskReport($filters),
            'users' => $this->reportModel->getUserReport($filters),
            'performance' => $this->reportModel->getPerformanceReport($filters),
            'departments' => $this->reportModel->getDepartmentReport($filters),
            'activity' => $this->reportModel->getActivityReport($filters),
            'login' => $this->reportModel->getLoginReport($filters),
            'audit' => $this->reportModel->getAuditReport($filters),
            'notifications' => $this->reportModel->getNotificationReport($filters),
            default => [],
        };
    }

    private function getHeaders(string $type): array
    {
        return match($type) {
            'tasks' => ['Task #', 'Title', 'Status', 'Priority', 'Department', 'Assigned To', 'Due Date', 'Created At'],
            'users' => ['Employee ID', 'Name', 'Email', 'Role', 'Department', 'Status', 'Tasks Assigned', 'Tasks Created', 'Created At'],
            'performance' => ['Name', 'Total Tasks', 'Completed', 'Overdue', 'Avg Completion (Hours)'],
            'departments' => ['Department', 'Total Tasks', 'Completed', 'Pending', 'In Progress', 'Active Users', 'Total Users'],
            'activity' => ['Date/Time', 'User', 'Action', 'Module', 'IP Address', 'Device'],
            'login' => ['Date/Time', 'User', 'Email', 'Status', 'IP Address', 'User Agent'],
            'audit' => ['Date/Time', 'User', 'Email', 'Action', 'Module', 'IP Address'],
            'notifications' => ['Date/Time', 'User', 'Type', 'Title', 'Status'],
            default => [],
        };
    }

    private function formatRow(string $type, object $row): array
    {
        return match($type) {
            'tasks' => [
                $row->task_number ?? '',
                $row->title ?? '',
                $row->status ?? '',
                $row->priority ?? '',
                $row->department_name ?? '',
                ($row->assigned_first_name ?? '') . ' ' . ($row->assigned_last_name ?? ''),
                $row->due_date ?? '',
                $row->created_at ?? '',
            ],
            'users' => [
                $row->employee_id ?? '',
                ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                $row->email ?? '',
                $row->role_name ?? '',
                $row->department_name ?? '',
                $row->status ?? '',
                $row->tasks_assigned ?? 0,
                $row->tasks_created ?? 0,
                $row->created_at ?? '',
            ],
            'performance' => [
                ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                $row->total_tasks ?? 0,
                $row->completed_tasks ?? 0,
                $row->overdue_tasks ?? 0,
                $row->avg_completion_hours ?? 'N/A',
            ],
            'departments' => [
                $row->department_name ?? '',
                $row->total_tasks ?? 0,
                $row->completed_tasks ?? 0,
                $row->pending_tasks ?? 0,
                $row->in_progress_tasks ?? 0,
                $row->active_users ?? 0,
                $row->total_users ?? 0,
            ],
            'activity' => [
                $row->created_at ?? '',
                ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                $row->action ?? '',
                $row->module ?? '',
                $row->ip_address ?? '',
                $row->device ?? '',
            ],
            'login' => [
                $row->created_at ?? '',
                ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                $row->email ?? '',
                $row->status ?? '',
                $row->ip_address ?? '',
                $row->user_agent ?? '',
            ],
            'audit' => [
                $row->created_at ?? '',
                ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                $row->email ?? '',
                $row->action ?? '',
                $row->module ?? '',
                $row->ip_address ?? '',
            ],
            'notifications' => [
                $row->created_at ?? '',
                ($row->first_name ?? '') . ' ' . ($row->last_name ?? ''),
                $row->type ?? '',
                $row->title ?? '',
                $row->is_read ? 'Read' : 'Unread',
            ],
            default => [],
        };
    }

    private function exportCsv(string $type, array $data): void
    {
        $filename = $type . '_report_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $this->getHeaders($type));

        foreach ($data as $row) {
            fputcsv($output, $this->formatRow($type, $row));
        }

        fclose($output);
        exit;
    }

    private function exportTxt(string $type, array $data): void
    {
        $filename = $type . '_report_' . date('Y-m-d') . '.txt';
        $headers = $this->getHeaders($type);

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo strtoupper($type) . " REPORT\n";
        echo 'Generated: ' . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('=', 80) . "\n\n";
        echo implode("\t", $headers) . "\n";
        echo str_repeat('-', 80) . "\n";

        foreach ($data as $row) {
            echo implode("\t", $this->formatRow($type, $row)) . "\n";
        }

        exit;
    }

    private function exportPrint(string $type, array $data): void
    {
        $headers = $this->getHeaders($type);
        $appName = 'Task Management System';
        $generatedAt = date('F j, Y g:i A');
        $filterInfo = $this->getFilterDescription();

        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        echo '<title>' . ucfirst($type) . ' Report</title>';
        echo '<style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: "Segoe UI", Arial, sans-serif;
                font-size: 11px; line-height: 1.5; color: #1e293b; padding: 20px;
            }
            .header {
                display: flex; justify-content: space-between; align-items: center;
                border-bottom: 2px solid #1e2a3a; padding-bottom: 12px; margin-bottom: 16px;
            }
            .header h1 { font-size: 20px; color: #1e2a3a; font-weight: 700; }
            .header .brand { text-align: right; font-size: 10px; color: #64748b; }
            .meta-row {
                display: flex; flex-wrap: wrap; gap: 8px 24px;
                background: #f8fafc; padding: 10px 14px; border-radius: 6px; margin-bottom: 16px;
                font-size: 10px; color: #475569;
            }
            .meta-row span { display: inline-block; }
            .meta-row .label { color: #94a3b8; }
            .table-wrap { overflow-x: auto; margin-top: 4px; }
            table {
                width: 100%; border-collapse: collapse; font-size: 9px;
            }
            thead th {
                background: #1e2a3a; color: #fff; padding: 6px 8px;
                text-align: left; font-weight: 600; font-size: 9px;
                border: 1px solid #1e2a3a; white-space: nowrap;
            }
            tbody td {
                padding: 4px 8px; border: 1px solid #e2e8f0; vertical-align: middle;
            }
            tbody tr:nth-child(even) { background: #f8fafc; }
            .footer {
                margin-top: 24px; padding-top: 10px; border-top: 1px solid #e2e8f0;
                text-align: center; font-size: 9px; color: #94a3b8;
            }
            .no-data {
                text-align: center; padding: 40px 0; color: #94a3b8; font-size: 13px;
            }
            @media print {
                body { padding: 6px; font-size: 8px; }
                .header h1 { font-size: 16px; }
                thead th { background: #1e2a3a !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                tbody tr:nth-child(even) { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .meta-row { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                @page { margin: 10mm 8mm; size: landscape; }
                thead { display: table-header-group; }
                tbody { display: table-row-group; }
                .table-wrap { overflow-x: visible; }
                table { font-size: 7.5px; }
                thead th, tbody td { padding: 3px 5px; }
            }
        </style></head><body>';

        echo '<div class="header">';
        echo '<div><h1>' . htmlspecialchars(ucfirst($type)) . ' Report</h1></div>';
        echo '<div class="brand">' . htmlspecialchars($appName) . '</div>';
        echo '</div>';

        echo '<div class="meta-row">';
        echo '<span><span class="label">Generated:</span> ' . htmlspecialchars($generatedAt) . '</span>';
        echo '<span><span class="label">Records:</span> ' . count($data) . '</span>';
        if ($filterInfo) {
            echo '<span><span class="label">Filters:</span> ' . htmlspecialchars($filterInfo) . '</span>';
        }
        echo '</div>';

        if (!empty($data)) {
            echo '<div class="table-wrap"><table><thead><tr>';
            foreach ($headers as $h) {
                echo '<th>' . htmlspecialchars($h) . '</th>';
            }
            echo '</tr></thead><tbody>';
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($this->formatRow($type, $row) as $cell) {
                    echo '<td>' . htmlspecialchars((string)$cell) . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="no-data">No records found for this report.</div>';
        }

        echo '<div class="footer">' . htmlspecialchars($appName) . '</div>';
        echo '<script>
            window.onload = function() {
                setTimeout(function() { window.print(); }, 300);
            };
            window.onafterprint = function() { window.history.back(); };
        </script>';
        echo '</body></html>';
        exit;
    }

    private function getFilterDescription(): string
    {
        $parts = [];
        if (!empty($_GET['status'])) $parts[] = 'Status: ' . $_GET['status'];
        if (!empty($_GET['priority'])) $parts[] = 'Priority: ' . $_GET['priority'];
        if (!empty($_GET['department_id'])) $parts[] = 'Dept ID: ' . $_GET['department_id'];
        if (!empty($_GET['user_id'])) $parts[] = 'User ID: ' . $_GET['user_id'];
        if (!empty($_GET['date_from'])) $parts[] = 'From: ' . $_GET['date_from'];
        if (!empty($_GET['date_to'])) $parts[] = 'To: ' . $_GET['date_to'];
        return implode(' | ', $parts);
    }

    public function exportEmail(string $type): void
    {
        try {
            $to = $_POST['email'] ?? '';
            $format = $_POST['format'] ?? 'csv';
            $filters = $_POST['filters'] ?? [];

            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Invalid email address');
                redirect('reports/' . $type);
                return;
            }

            if (!in_array($format, ['csv', 'xlsx', 'pdf'])) {
                $format = 'csv';
            }

            $data = $this->getReportData($type, $filters);
            $headers = $this->getHeaders($type);

            $content = '';
            $mime = 'text/csv';
            $filename = $type . '_report.' . $format;

            switch ($format) {
                case 'csv':
                    $content = $this->renderCsv($type, $data);
                    $mime = 'text/csv';
                    break;
                case 'xlsx':
                    $content = $this->renderXlsx($type, $data);
                    $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    break;
                case 'pdf':
                    $content = $this->renderPdf($type, $data);
                    $mime = 'application/pdf';
                    break;
            }

            $emailService = new \App\Services\EmailService();
            $sent = $emailService->sendWithAttachment($to, ucfirst($type) . ' Report', 'Please find the attached ' . $type . ' report.', $content, $filename, $mime);

            if ($sent) {
                flash('success', 'Report sent to ' . e($to));
            } else {
                flash('error', 'Failed to send report. Check email settings.');
            }
        } catch (\Throwable $e) {
            logError('Report email export error', $e);
            flash('error', 'Failed to send report via email');
        }

        redirect('reports/' . $type);
    }

    private function renderCsv(string $type, array $data): string
    {
        $headers = $this->getHeaders($type);
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($data as $row) {
            fputcsv($output, $this->formatRow($type, $row));
        }
        rewind($output);
        return stream_get_contents($output);
    }

    private function renderXlsx(string $type, array $data): string
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new \RuntimeException('PhpSpreadsheet not installed');
        }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ucfirst($type) . ' Report');
        $headers = $this->getHeaders($type);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }
        $rowNum = 2;
        foreach ($data as $row) {
            $col = 'A';
            foreach ($this->formatRow($type, $row) as $cell) {
                $sheet->setCellValue($col . $rowNum, $cell);
                $col++;
            }
            $rowNum++;
        }
        foreach (range('A', chr(ord('A') + count($headers) - 1)) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    private function renderPdf(string $type, array $data): string
    {
        if (!class_exists('\TCPDF')) {
            throw new \RuntimeException('TCPDF not installed');
        }
        $headers = $this->getHeaders($type);
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Task Management System');
        $pdf->SetTitle(ucfirst($type) . ' Report');
        $pdf->SetHeaderData('', 0, 'Task Management System', ucfirst($type) . ' Report');
        $pdf->setHeaderFont(['helvetica', '', 10]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();
        $html = '<h1>' . ucfirst($type) . ' Report</h1>';
        $html .= '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '<table border="1" cellpadding="4"><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th style="background:#1e2a3a;color:#fff;">' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($this->formatRow($type, $row) as $cell) {
                $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        return $pdf->Output($type . '_report.pdf', 'S');
    }

    private function exportXlsx(string $type, array $data): void
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            logError('PhpSpreadsheet not installed for XLSX export');
            flash('error', 'XLSX export requires PhpSpreadsheet library');
            redirect('reports/' . $type);
            return;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ucfirst($type) . ' Report');

        $headers = $this->getHeaders($type);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        $rowNum = 2;
        foreach ($data as $row) {
            $col = 'A';
            foreach ($this->formatRow($type, $row) as $cell) {
                $sheet->setCellValue($col . $rowNum, $cell);
                $col++;
            }
            $rowNum++;
        }

        foreach (range('A', $col) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = $type . '_report_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportPdf(string $type, array $data): void
    {
        if (!class_exists('\TCPDF')) {
            logError('TCPDF not installed for PDF export');
            flash('error', 'PDF export requires TCPDF library');
            redirect('reports/' . $type);
            return;
        }

        $headers = $this->getHeaders($type);

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Task Management System');
        $pdf->SetTitle(ucfirst($type) . ' Report');
        $pdf->SetHeaderData('', 0, 'Task Management System', ucfirst($type) . ' Report');
        $pdf->setHeaderFont(['helvetica', '', 10]);
        $pdf->setFooterFont(['helvetica', '', 8]);
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $html = '<h1>' . ucfirst($type) . ' Report</h1>';
        $html .= '<p>Generated: ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '<table border="1" cellpadding="4"><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th style="background:#1e2a3a;color:#fff;">' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($this->formatRow($type, $row) as $cell) {
                $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($type . '_report_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }
}
