<?php
require '../../assets/fpdf/fpdf.php';
function generateDonationReceipt($donation, $donor) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'Donation Receipt',0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,'Donor: ' . $donor['name'],0,1);
    $pdf->Cell(0,10,'Amount: $' . number_format($donation['amount'],2),0,1);
    $pdf->Cell(0,10,'Date: ' . $donation['date_received'],0,1);
    $pdf->Cell(0,10,'Type: ' . $donation['donation_type'],0,1);
    $pdf->Cell(0,10,'Description: ' . $donation['description'],0,1);
    $pdf->Output('D', 'DonationReceipt.pdf');
}
?>