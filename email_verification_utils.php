<?php

function ensure_email_verification_schema(mysqli $conn): void
{
    $checks = [
        "email_verified" => "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0",
        "email_otp" => "ALTER TABLE users ADD COLUMN email_otp VARCHAR(255) NULL",
        "otp_expires_at" => "ALTER TABLE users ADD COLUMN otp_expires_at DATETIME NULL",
        "otp_sent_at" => "ALTER TABLE users ADD COLUMN otp_sent_at DATETIME NULL",
        "phone_verified" => "ALTER TABLE users ADD COLUMN phone_verified TINYINT(1) NOT NULL DEFAULT 0",
        "phone_otp" => "ALTER TABLE users ADD COLUMN phone_otp VARCHAR(255) NULL",
        "phone_otp_expires_at" => "ALTER TABLE users ADD COLUMN phone_otp_expires_at DATETIME NULL",
        "phone_otp_sent_at" => "ALTER TABLE users ADD COLUMN phone_otp_sent_at DATETIME NULL"
    ];

    foreach ($checks as $column => $sql) {
        $stmt = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ? LIMIT 1");
        if (!$stmt) {
            continue;
        }

        $stmt->bind_param("s", $column);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result && $result->num_rows > 0;
        $stmt->close();

        if (!$exists) {
            $conn->query($sql);
        }
    }
}

function generate_email_otp(): string
{
    return str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
}

function generate_phone_otp(): string
{
    return generate_email_otp();
}

function set_user_otp(mysqli $conn, int $userId, string $otp, string $channel = 'email'): bool
{
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);

    if ($channel === 'phone') {
        $stmt = $conn->prepare("UPDATE users SET phone_otp = ?, phone_otp_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), phone_otp_sent_at = NOW() WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE users SET email_otp = ?, otp_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), otp_sent_at = NOW() WHERE id = ?");
    }

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("si", $otpHash, $userId);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function send_verification_otp(string $email, string $name, string $otp): bool
{
    $result = send_email_otp_with_status($email, $name, $otp);
    return (bool)$result['ok'];
}

function send_email_otp_with_status(string $email, string $name, string $otp): array
{
    $safeName = trim($name) !== '' ? $name : 'User';
    $subject = 'SportsMate Email Verification OTP';
    $message = "Hi {$safeName},\n\nYour SportsMate verification OTP is: {$otp}\nThis OTP expires in 10 minutes.\n\nIf you did not request this, ignore this email.";
    $headers = "From: no-reply@sportsmate.local\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $mailOk = mail($email, $subject, $message, $headers);
    if ($mailOk) {
        return [
            'ok' => true,
            'message' => 'OTP email sent successfully.'
        ];
    }

    $errorHint = 'Email send failed. In XAMPP, configure sendmail/SMTP in php.ini + sendmail.ini.';
    write_otp_debug_log('email', $email, $otp);

    return [
        'ok' => false,
        'message' => $errorHint . otp_debug_suffix($otp)
    ];
}

function send_phone_otp_with_status(string $phone, string $name, string $otp): array
{
    $normalizedPhone = normalize_phone_number($phone);
    if ($normalizedPhone === '') {
        return [
            'ok' => false,
            'message' => 'Invalid phone number format.'
        ];
    }

    $sid = getenv('SPORTSMATE_TWILIO_SID') ?: '';
    $token = getenv('SPORTSMATE_TWILIO_TOKEN') ?: '';
    $from = getenv('SPORTSMATE_TWILIO_FROM') ?: '';

    if ($sid === '' || $token === '' || $from === '') {
        write_otp_debug_log('phone', $normalizedPhone, $otp);
        return [
            'ok' => false,
            'message' => 'SMS provider not configured. Set SPORTSMATE_TWILIO_SID, SPORTSMATE_TWILIO_TOKEN, SPORTSMATE_TWILIO_FROM.' . otp_debug_suffix($otp)
        ];
    }

    if (!function_exists('curl_init')) {
        write_otp_debug_log('phone', $normalizedPhone, $otp);
        return [
            'ok' => false,
            'message' => 'cURL extension is required for SMS sending.' . otp_debug_suffix($otp)
        ];
    }

    $text = "SportsMate OTP: {$otp}. Valid for 10 minutes.";
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'From' => $from,
        'To' => $normalizedPhone,
        'Body' => $text
    ]));

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError !== '') {
        write_otp_debug_log('phone', $normalizedPhone, $otp);
        return [
            'ok' => false,
            'message' => 'SMS send failed: ' . $curlError . otp_debug_suffix($otp)
        ];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'ok' => true,
            'message' => 'OTP SMS sent successfully.'
        ];
    }

    write_otp_debug_log('phone', $normalizedPhone, $otp);
    return [
        'ok' => false,
        'message' => 'SMS send failed with status ' . $httpCode . '. ' . trim((string)$response) . otp_debug_suffix($otp)
    ];
}

function verification_matches(array $user, string $otpInput, string $channel): bool
{
    if ($channel === 'phone') {
        $hash = (string)($user['phone_otp'] ?? '');
        $expiry = $user['phone_otp_expires_at'] ?? null;
    } else {
        $hash = (string)($user['email_otp'] ?? '');
        $expiry = $user['otp_expires_at'] ?? null;
    }

    if (!preg_match('/^[0-9]{6}$/', $otpInput)) {
        return false;
    }

    if ($hash === '') {
        return false;
    }

    if ($expiry && strtotime((string)$expiry) < time()) {
        return false;
    }

    return password_verify($otpInput, $hash);
}

function clear_verified_channel(mysqli $conn, int $userId, string $channel): bool
{
    if ($channel === 'phone') {
        $stmt = $conn->prepare('UPDATE users SET phone_verified = 1, phone_otp = NULL, phone_otp_expires_at = NULL WHERE id = ?');
    } else {
        $stmt = $conn->prepare('UPDATE users SET email_verified = 1, email_otp = NULL, otp_expires_at = NULL WHERE id = ?');
    }

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $userId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function is_email_verified(array $user): bool
{
    return isset($user['email_verified']) && (int)$user['email_verified'] === 1;
}

function mask_email(string $email): string
{
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return $email;
    }

    $local = $parts[0];
    $domain = $parts[1];

    if (strlen($local) <= 2) {
        $maskedLocal = substr($local, 0, 1) . '*';
    } else {
        $maskedLocal = substr($local, 0, 2) . str_repeat('*', max(2, strlen($local) - 2));
    }

    return $maskedLocal . '@' . $domain;
}

function mask_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (!$digits || strlen($digits) < 4) {
        return $phone;
    }

    return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
}

function normalize_phone_number(string $phone): string
{
    $trimmed = trim($phone);
    if ($trimmed === '') {
        return '';
    }

    if ($trimmed[0] === '+') {
        $digits = preg_replace('/\D+/', '', substr($trimmed, 1));
        return $digits === '' ? '' : '+' . $digits;
    }

    $digits = preg_replace('/\D+/', '', $trimmed);
    if ($digits === '') {
        return '';
    }

    if (strlen($digits) === 10) {
        return '+1' . $digits;
    }

    return '+' . $digits;
}

function otp_debug_suffix(string $otp): string
{
    if (!is_otp_debug_enabled()) {
        return '';
    }

    return ' [DEV OTP: ' . $otp . ']';
}

function is_otp_debug_enabled(): bool
{
    $flag = getenv('SPORTSMATE_OTP_DEBUG');
    if ($flag !== false && trim($flag) === '1') {
        return true;
    }

    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    return $host === 'localhost' || str_starts_with($host, '127.0.0.1');
}

function write_otp_debug_log(string $channel, string $target, string $otp): void
{
    if (!is_otp_debug_enabled()) {
        return;
    }

    $line = date('Y-m-d H:i:s') . " | {$channel} | {$target} | OTP={$otp}" . PHP_EOL;
    @file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'otp_debug.log', $line, FILE_APPEND);
}

?>
