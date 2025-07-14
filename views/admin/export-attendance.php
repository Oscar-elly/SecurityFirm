<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

requireRole('admin');

$date = $_GET['date'] ?? null;

$query = "SELECT a.id, g.id_number, u.name as guard_name, a.check_in_time, a.status, l.name as location_name
          FROM attendance a
          JOIN guards g ON a.guard_id = g.id
          JOIN users u ON g.user_id = u.id
          JOIN locations l ON a.location_id = l.id";

$params = [];
if ($date) {
    $query .= " WHERE DATE(a.check_in_time) = ?";
    $params[] = $date;
}

$query .= " ORDER BY a.check_in_time DESC, u.name ASC";

$attendanceRecords = executeQuery($query, $params);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_export_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');

// Output CSV headers
fputcsv($output, ['Guard ID', 'Name', 'Date', 'Status', 'Location']);

// Output data rows
foreach ($attendanceRecords as $record) {
    fputcsv($output, [
        $record['id_number'],
        $record['guard_name'],
        $record['check_in_time'],
        ucfirst($record['status']),
        $record['location_name']
    ]);
}

fclose($output);
exit;
?>
