<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

requireLogin();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT i.*, c.name AS customer_name, c.phone, c.state, c.gstin
     FROM invoices i JOIN customers c ON c.customer_id = i.customer_id
     WHERE i.invoice_id = ?'
);
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    http_response_code(404);
    die('Invoice not found.');
}

$stmt = $pdo->prepare(
    'SELECT ii.*, p.name, p.hsn_code, p.unit FROM invoice_items ii
     JOIN products p ON p.product_id = ii.product_id
     WHERE ii.invoice_id = ?'
);
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$rupee = fn (float $amt) => 'Rs. ' . number_format($amt, 2);

class InvoicePDF extends FPDF
{
    public function tableHeader(): void
    {
        $this->SetFont('Courier', 'B', 8);
        $this->SetFillColor(230, 230, 230);
        $this->SetX(10);
        $this->Cell(10, 7, '#', 1, 0, 'C', true);
        $this->Cell(55, 7, 'Product', 1, 0, 'L', true);
        $this->Cell(18, 7, 'HSN', 1, 0, 'C', true);
        $this->Cell(16, 7, 'Qty', 1, 0, 'R', true);
        $this->Cell(20, 7, 'Rate', 1, 0, 'R', true);
        $this->Cell(23, 7, 'Taxable', 1, 0, 'R', true);
        $this->Cell(13, 7, 'GST%', 1, 0, 'R', true);
        $this->Cell(20, 7, 'GST Amt', 1, 0, 'R', true);
        $this->Cell(25, 7, 'Total', 1, 1, 'R', true);
    }
}

$pdf = new InvoicePDF();
$pdf->SetTitle('Invoice ' . $invoice['invoice_no']);
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

$pdf->SetFont('Courier', 'B', 14);
$pdf->SetXY(10, 10);
$pdf->Cell(190, 7, SHOP_NAME, 0, 1, 'C');
$pdf->SetFont('Courier', '', 9);
$pdf->SetX(10);
$pdf->Cell(190, 5, SHOP_ADDRESS, 0, 1, 'C');
$pdf->SetX(10);
$pdf->Cell(190, 5, 'GSTIN: ' . SHOP_GSTIN . '   State: ' . SHOP_STATE . '   Ph: ' . SHOP_PHONE, 0, 1, 'C');

$pdf->SetY($pdf->GetY() + 2);
$pdf->SetLineWidth(0.3);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->SetY($pdf->GetY() + 3);

$pdf->SetFont('Courier', 'B', 11);
$pdf->SetX(10);
$pdf->Cell(95, 6, 'TAX INVOICE', 0, 0, 'L');
$pdf->SetFont('Courier', '', 9);
$pdf->Cell(95, 6, 'Invoice No: ' . $invoice['invoice_no'], 0, 1, 'R');
$pdf->SetX(10);
$pdf->Cell(95, 6, '', 0, 0, 'L');
$pdf->Cell(95, 6, 'Date: ' . $invoice['invoice_date'], 0, 1, 'R');

$pdf->SetY($pdf->GetY() + 2);
$pdf->SetFont('Courier', 'B', 9);
$pdf->SetX(10);
$pdf->Cell(95, 5, 'Bill To:', 0, 1);
$pdf->SetFont('Courier', '', 9);
$pdf->SetX(10);
$pdf->Cell(95, 5, $invoice['customer_name'], 0, 1);
if ($invoice['phone']) {
    $pdf->SetX(10);
    $pdf->Cell(95, 5, 'Phone: ' . $invoice['phone'], 0, 1);
}
$pdf->SetX(10);
$pdf->Cell(95, 5, 'State: ' . $invoice['state'], 0, 1);
if ($invoice['gstin']) {
    $pdf->SetX(10);
    $pdf->Cell(95, 5, 'GSTIN: ' . $invoice['gstin'], 0, 1);
}
$pdf->SetX(10);
$pdf->Cell(95, 5, 'Payment Mode: ' . strtoupper($invoice['payment_mode']), 0, 1);

$pdf->SetY($pdf->GetY() + 3);
$pdf->tableHeader();

$pdf->SetFont('Courier', '', 8);
foreach ($items as $i => $it) {
    if ($pdf->GetY() > 265) {
        $pdf->AddPage();
        $pdf->tableHeader();
        $pdf->SetFont('Courier', '', 8);
    }
    $lineTotal = $it['taxable_value'] + $it['gst_amount'];
    $qtyDisplay = rtrim(rtrim((string) $it['qty'], '0'), '.') . ' ' . $it['unit'];

    $pdf->SetX(10);
    $pdf->Cell(10, 6, (string) ($i + 1), 1, 0, 'C');
    $pdf->Cell(55, 6, substr($it['name'], 0, 32), 1, 0, 'L');
    $pdf->Cell(18, 6, $it['hsn_code'], 1, 0, 'C');
    $pdf->Cell(16, 6, $qtyDisplay, 1, 0, 'R');
    $pdf->Cell(20, 6, number_format($it['rate'], 2), 1, 0, 'R');
    $pdf->Cell(23, 6, number_format($it['taxable_value'], 2), 1, 0, 'R');
    $pdf->Cell(13, 6, rtrim(rtrim((string) $it['gst_rate'], '0'), '.') . '%', 1, 0, 'R');
    $pdf->Cell(20, 6, number_format($it['gst_amount'], 2), 1, 0, 'R');
    $pdf->Cell(25, 6, number_format($lineTotal, 2), 1, 1, 'R');
}

$pdf->SetY($pdf->GetY() + 4);

$labelW = 140;
$valueW = 50;
$pdf->SetFont('Courier', '', 9);
$pdf->SetX(10);
$pdf->Cell($labelW, 6, 'Taxable Total', 0, 0, 'R');
$pdf->Cell($valueW, 6, $rupee((float) $invoice['taxable_total']), 0, 1, 'R');

if ((float) $invoice['cgst'] > 0 || (float) $invoice['sgst'] > 0) {
    $pdf->SetX(10);
    $pdf->Cell($labelW, 6, 'CGST', 0, 0, 'R');
    $pdf->Cell($valueW, 6, $rupee((float) $invoice['cgst']), 0, 1, 'R');
    $pdf->SetX(10);
    $pdf->Cell($labelW, 6, 'SGST', 0, 0, 'R');
    $pdf->Cell($valueW, 6, $rupee((float) $invoice['sgst']), 0, 1, 'R');
} else {
    $pdf->SetX(10);
    $pdf->Cell($labelW, 6, 'IGST', 0, 0, 'R');
    $pdf->Cell($valueW, 6, $rupee((float) $invoice['igst']), 0, 1, 'R');
}

$pdf->SetX(10);
$pdf->Cell($labelW, 6, 'Round Off', 0, 0, 'R');
$pdf->Cell($valueW, 6, $rupee((float) $invoice['round_off']), 0, 1, 'R');

$pdf->SetFont('Courier', 'B', 11);
$pdf->SetX(10);
$pdf->Cell($labelW, 8, 'Grand Total', 'T', 0, 'R');
$pdf->Cell($valueW, 8, $rupee((float) $invoice['grand_total']), 'T', 1, 'R');

$pdf->SetY($pdf->GetY() + 10);
$pdf->SetFont('Courier', '', 8);
$pdf->SetX(10);
$pdf->Cell(190, 5, 'This is a computer generated invoice. Thank you for your business!', 0, 1, 'C');

$pdf->Output('I', str_replace('/', '_', $invoice['invoice_no']) . '.pdf');
