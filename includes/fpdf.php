<?php
/*
 * FPDF - Free PDF class
 * Version 1.82
 * Official website: http://www.fpdf.org/
 * This is the full source code of the FPDF library single file.
 * For brevity, only a minimal version is included here.
 * For full features, please download from the official site.
 */

class FPDF
{
    // Minimal implementation for demonstration
    function __construct()
    {
        // Initialize PDF document
    }
    function AddPage()
    {
        // Add a page
    }
    function SetFont($family, $style = '', $size = 0)
    {
        // Set font
    }
    function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        // Output a cell
    }
    function Ln($h = null)
    {
        // Line break
    }
    function Output($dest = '', $name = '', $isUTF8 = false)
    {
        // Output PDF
        // For demonstration, just output a simple PDF header
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        echo '%PDF-1.4
        %âãÏÓ
        1 0 obj
        << /Type /Catalog /Pages 2 0 R >>
        endobj
        2 0 obj
        << /Type /Pages /Kids [3 0 R] /Count 1 >>
        endobj
        3 0 obj
        << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>
        endobj
        4 0 obj
        << /Length 44 >>
        stream
        BT
        /F1 24 Tf
        100 700 Td
        (Sample PDF) Tj
        ET
        endstream
        endobj
        xref
        0 5
        0000000000 65535 f 
        0000000010 00000 n 
        0000000060 00000 n 
        0000000117 00000 n 
        0000000214 00000 n 
        trailer
        << /Size 5 /Root 1 0 R >>
        startxref
        312
        %%EOF';
    }
}
?>
