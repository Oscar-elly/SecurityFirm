<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/fpdf.php';

requireRole('admin');

// Fetch summary data for reports

$totalGuards = executeQuery("SELECT COUNT(*) as count FROM guards", [], ['single' => true])['count'] ?? 0;
$totalOrganizations = executeQuery("SELECT COUNT(*) as count FROM organizations", [], ['single' => true])['count'] ?? 0;
$totalLocations = executeQuery("SELECT COUNT(*) as count FROM locations", [], ['single' => true])['count'] ?? 0;
$incidentSeverities = executeQuery("SELECT severity, COUNT(*) as count FROM incidents GROUP BY severity");
$attendanceSummary = executeQuery("SELECT status, COUNT(*) as count FROM attendance GROUP BY status");
$dutyAssignmentsSummary = executeQuery("SELECT status, COUNT(*) as count FROM duty_assignments GROUP BY status");
$avgPerformance = executeQuery("SELECT AVG(overall_rating) as avg_rating FROM performance_evaluations", [], ['single' => true])['avg_rating'] ?? 0;

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Reports Summary - ' . SITE_NAME, 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Total Guards: ' . $totalGuards, 0, 1);
$pdf->Cell(0, 10, 'Total Organizations: ' . $totalOrganizations, 0, 1);
$pdf->Cell(0, 10, 'Total Locations: ' . $totalLocations, 0, 1);
$pdf->Cell(0, 10, 'Average Performance Rating: ' . number_format($avgPerformance, 2), 0, 1);
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Incidents by Severity:', 0, 1);
$pdf->SetFont('Arial', '', 12);
foreach ($incidentSeverities as $row) {
    $pdf->Cell(0, 10, ucfirst($row['severity']) . ': ' . $row['count'], 0, 1);
}
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Attendance Summary:', 0, 1);
$pdf->SetFont('Arial', '', 12);
foreach ($attendanceSummary as $row) {
    $pdf->Cell(0, 10, ucfirst($row['status']) . ': ' . $row['count'], 0, 1);
}
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Duty Assignments Summary:', 0, 1);
$pdf->SetFont('Arial', '', 12);
foreach ($dutyAssignmentsSummary as $row) {
    $pdf->Cell(0, 10, ucfirst($row['status']) . ': ' . $row['count'], 0, 1);
}

$pdf->Output('D', 'reports_summary.pdf');
exit;
?>
