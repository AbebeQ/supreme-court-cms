<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: text/plain; charset=UTF-8');

$pdo = get_pdo();

function request_payload_map()
{
    $body = file_get_contents('php://input');
    $decoded = [];
    if (!empty($body)) {
        $json = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $decoded = $json;
        } else {
            parse_str($body, $decoded);
        }
    }

    return array_merge($_GET, $_POST, $decoded);
}

function request_value($payload, $keys)
{
    foreach ($keys as $key) {
        if (isset($payload[$key]) && trim((string)$payload[$key]) !== '') {
            return trim((string)$payload[$key]);
        }
    }

    return '';
}

$payload = request_payload_map();
$phoneNumber = request_value($payload, ['phoneNumber', 'phone_number', 'phone', 'msisdn', 'msisdnNumber', 'number']);
$text = request_value($payload, ['text', 'message', 'sessionText']);
$serviceCode = request_value($payload, ['serviceCode', 'service_code', 'servicecode']);

function normalize_phone($value)
{
    $digits = preg_replace('/[^0-9]/', '', (string)$value) ?? '';

    if (str_starts_with($digits, '251')) {
        $digits = substr($digits, 3);
    }

    if (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }

    return $digits;
}

function find_user_by_phone($pdo, $phoneNumber)
{
    $digits = normalize_phone($phoneNumber);
    if ($digits === '') {
        return null;
    }

    $stmt = $pdo->query('SELECT id, username, email, full_name, role, phone, address, is_active FROM users WHERE is_active = TRUE ORDER BY id ASC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        $storedPhone = normalize_phone($user['phone'] ?? '');
        if ($storedPhone === $digits) {
            return $user;
        }
    }

    return null;
}

function find_process_user_cases($pdo, $user)
{
    $role = strtoupper($user['role'] ?? 'LITIGANT');
    $viewerId = (int)($user['id'] ?? 0);
    $viewerName = (string)($user['full_name'] ?? '');

    if (in_array($role, ['ADMIN', 'CLERK'], true)) {
        $stmt = $pdo->query('SELECT c.id, c.case_number, c.title, c.status, c.filing_date, c.hearing_date FROM cases c ORDER BY c.id DESC LIMIT 5');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($role === 'JUDGE') {
        $stmt = $pdo->prepare('SELECT c.id, c.case_number, c.title, c.status, c.filing_date, c.hearing_date FROM cases c WHERE c.assigned_judge = ? ORDER BY c.id DESC LIMIT 5');
        $stmt->execute([$viewerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($role === 'ADVOCATE') {
        $stmt = $pdo->prepare('SELECT DISTINCT c.id, c.case_number, c.title, c.status, c.filing_date, c.hearing_date FROM cases c JOIN parties p ON p.case_id = c.id WHERE LOWER(p.advocate_name) = LOWER(?) ORDER BY c.id DESC LIMIT 5');
        $stmt->execute([$viewerName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->prepare('SELECT DISTINCT c.id, c.case_number, c.title, c.status, c.filing_date, c.hearing_date FROM cases c JOIN parties p ON p.case_id = c.id WHERE LOWER(p.name) = LOWER(?) ORDER BY c.id DESC LIMIT 5');
    $stmt->execute([$viewerName]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function render_cases_list($cases)
{
    if (empty($cases)) {
        return 'No cases found for your role.';
    }

    $buffer = [];
    foreach ($cases as $case) {
        $buffer[] = $case['case_number'] . ' - ' . $case['title'] . ' - ' . $case['status'];
    }

    return implode('\n', array_slice($buffer, 0, 5));
}

function render_hearings_list($pdo, $user)
{
    $role = strtoupper($user['role'] ?? 'LITIGANT');
    $viewerId = (int)($user['id'] ?? 0);
    $viewerName = (string)($user['full_name'] ?? '');

    if (in_array($role, ['ADMIN', 'CLERK'], true)) {
        $stmt = $pdo->query('SELECT h.id, h.case_id, h.hearing_date, h.hearing_time, h.Courtroom FROM hearings h ORDER BY h.hearing_date DESC LIMIT 5');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($role === 'JUDGE') {
        $stmt = $pdo->prepare('SELECT h.id, h.case_id, h.hearing_date, h.hearing_time, h.Courtroom FROM hearings h WHERE h.judge_id = ? ORDER BY h.hearing_date DESC LIMIT 5');
        $stmt->execute([$viewerId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($role === 'ADVOCATE') {
        $stmt = $pdo->prepare('SELECT DISTINCT h.id, h.case_id, h.hearing_date, h.hearing_time, h.Courtroom FROM hearings h JOIN parties p ON p.case_id = h.case_id WHERE LOWER(p.advocate_name) = LOWER(?) ORDER BY h.hearing_date DESC LIMIT 5');
        $stmt->execute([$viewerName]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare('SELECT DISTINCT h.id, h.case_id, h.hearing_date, h.hearing_time, h.Courtroom FROM hearings h JOIN parties p ON p.case_id = h.case_id WHERE LOWER(p.name) = LOWER(?) ORDER BY h.hearing_date DESC LIMIT 5');
        $stmt->execute([$viewerName]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($rows)) {
        return 'No hearings found for your role.';
    }

    $buffer = [];
    foreach ($rows as $row) {
        $buffer[] = 'Case ' . ($row['case_id'] ?? 'NA') . ' - ' . date('d M Y', strtotime($row['hearing_date'])) . ' - ' . $row['Courtroom'];
    }

    return implode('\n', $buffer);
}

$user = find_user_by_phone($pdo, $phoneNumber);
if (!$user) {
    $phonePrompt = normalize_phone($phoneNumber);
    echo "END User not found. Register your phone number {$phonePrompt} in the portal before using USSD.";
    exit;
}

$role = strtoupper($user['role'] ?? 'LITIGANT');

if ($text === '') {
    echo "CON Amhara Supreme Court CMS\n1. My Cases\n2. My Hearings\n3. Help\n0. Exit";
    exit;
}

$choices = array_filter(explode('*', $text), static fn($v) => trim($v) !== '');
$choice = strtolower(trim($choices[0] ?? ''));

if ($choice === '1') {
    $cases = find_process_user_cases($pdo, $user);
    $list = render_cases_list($cases);
    echo "END {$list}";
    exit;
}

if ($choice === '2') {
    $list = render_hearings_list($pdo, $user);
    echo "END {$list}";
    exit;
}

if ($choice === '3') {
    echo "END Amhara Supreme Court CMS\nRole: {$role}\nUse 1 for cases and 2 for hearings.";
    exit;
}

echo "END Amhara Supreme Court CMS\nInvalid option.\n1. My Cases\n2. My Hearings";
