<?php
/**
 * Returns the Indian financial year label for a date, e.g. "2026-27".
 */
function getFinancialYear(?DateTime $date = null): string
{
    $date  = $date ?? new DateTime();
    $year  = (int) $date->format('Y');
    $month = (int) $date->format('n');

    if ($month >= FY_START_MONTH) {
        $startYear = $year;
        $endYear   = $year + 1;
    } else {
        $startYear = $year - 1;
        $endYear   = $year;
    }

    return $startYear . '-' . substr((string) $endYear, -2);
}

/**
 * Returns the [start, end] DATE strings (inclusive) of the financial year containing $date.
 */
function getFinancialYearRange(?DateTime $date = null): array
{
    $date  = $date ?? new DateTime();
    $year  = (int) $date->format('Y');
    $month = (int) $date->format('n');

    if ($month >= FY_START_MONTH) {
        $start = new DateTime("$year-" . FY_START_MONTH . "-01");
        $end   = new DateTime(($year + 1) . '-03-31');
    } else {
        $start = new DateTime(($year - 1) . '-' . FY_START_MONTH . '-01');
        $end   = new DateTime("$year-03-31");
    }

    return [$start->format('Y-m-d'), $end->format('Y-m-d')];
}

function money(float $amount): string
{
    return number_format($amount, 2);
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function isIntraState(string $customerState): bool
{
    return strcasecmp(trim($customerState), trim(SHOP_STATE)) === 0;
}

/**
 * Allocates the next sequential invoice number for the given financial year,
 * e.g. INV/2026-27/0001. Must be called inside an open PDO transaction so the
 * row lock (FOR UPDATE) serializes concurrent bill saves.
 */
function nextInvoiceNumber(PDO $pdo, string $fy): string
{
    $pdo->prepare('INSERT IGNORE INTO invoice_counters (fy_label, last_number) VALUES (?, 0)')
        ->execute([$fy]);

    $stmt = $pdo->prepare('SELECT last_number FROM invoice_counters WHERE fy_label = ? FOR UPDATE');
    $stmt->execute([$fy]);
    $last = (int) $stmt->fetchColumn();
    $next = $last + 1;

    $pdo->prepare('UPDATE invoice_counters SET last_number = ? WHERE fy_label = ?')
        ->execute([$next, $fy]);

    return sprintf('INV/%s/%04d', $fy, $next);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}
