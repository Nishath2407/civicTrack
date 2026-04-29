<?php
/**
 * CivicTrack — includes/otp.php (PHPMailer Integrated)
 */

// 1. IMPORT PHPMailer Classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 2. LOAD PHPMailer Files
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

/**
 * Generates a numeric OTP
 */
function generateOtp(): string {
    $digits = defined('OTP_LENGTH') ? OTP_LENGTH : 6;
    $min    = (int) str_pad('1', $digits, '0');
    $max    = (int) str_pad('', $digits, '9');
    return str_pad(random_int($min, $max), $digits, '0', STR_PAD_LEFT);
}

/**
 * Stores a fresh OTP in the database
 */
function createOtp(string $phone): string {
    DB::exec("DELETE FROM otp_codes WHERE phone = ?", [$phone]);
    $otp = generateOtp();
    $expiry_minutes = defined('OTP_EXPIRY_MINS') ? OTP_EXPIRY_MINS : 10;
    $expiry = date('Y-m-d H:i:s', time() + $expiry_minutes * 60);
    DB::exec("INSERT INTO otp_codes (phone, otp, expires_at) VALUES (?, ?, ?)", [$phone, $otp, $expiry]);
    return $otp;
}

/**
 * Verifies an OTP
 */
function verifyOtp(string $phone, string $otp): bool {
    $row = DB::row(
        "SELECT id FROM otp_codes 
         WHERE phone = ? AND otp = ? AND used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1",
        [$phone, $otp]
    );
    if (!$row) return false;
    DB::exec("UPDATE otp_codes SET used = 1 WHERE id = ?", [$row['id']]);
    return true;
}

/**
 * Sends OTP via PHPMailer
 */
function sendOtp(string $phone, string $otp, ?string $email = null): bool {
    if ($email === null) {
        $email = $_SESSION['pending_email'] ?? null;
    }

    if (defined('OTP_DEMO_MODE') && OTP_DEMO_MODE) {
        $logFile = __DIR__ . '/../otp_debug.log';
        $line    = '[' . date('Y-m-d H:i:s') . '] Phone: ' . $phone 
                 . ($email ? ' Email: ' . $email : '') 
                 . ' OTP: ' . $otp . PHP_EOL;
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        return true;
    }

    if (!$email) return false;

    $mail = new PHPMailer(true);

    try {
        // --- DEBUG SETTINGS ---
        $mail->SMTPDebug = 0; // Set to 2 if you still face issues to see logs
        $mail->Debugoutput = 'html';

        // --- SMTP SERVER SETTINGS ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'civictrackproject@gmail.com'; 
       //$mail->password=   ;
        
        // Use STARTTLS for Port 587
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;

        // --- SSL FIX FOR XAMPP ---
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // --- EMAIL CONTENT ---
        $mail->setFrom('civictrackproject@gmail.com', 'CivicTrack');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "CivicTrack OTP Verification";
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd;'>
                <h2 style='color: #2c3e50;'>CivicTrack Registration</h2>
                <p>Your One-Time Password (OTP) for registration is:</p>
                <h1 style='color: #e74c3c; letter-spacing: 5px;'>$otp</h1>
                <p>Valid for 10 minutes.</p>
            </div>";
        
        $mail->AltBody = "Your OTP for CivicTrack is: $otp. Valid for 10 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
