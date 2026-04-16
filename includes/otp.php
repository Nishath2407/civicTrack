<?php
/**
 * CivicTrack — includes/otp.php (PHPMailer Integrated)
 */

// 1. IMPORT PHPMailer Classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 2. LOAD PHPMailer Files (Ensure the folder name is 'PHPMailer')
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

/**
 * Generates a numeric OTP of OTP_LENGTH digits.
 */
function generateOtp(): string {
    $digits = defined('OTP_LENGTH') ? OTP_LENGTH : 6;
    $min    = (int) str_pad('1', $digits, '0');
    $max    = (int) str_pad('', $digits, '9');
    return str_pad(random_int($min, $max), $digits, '0', STR_PAD_LEFT);
}

/**
 * Stores a fresh OTP in the database.
 * FIX: Uses DELETE to prevent "Duplicate entry" Fatal Errors.
 */
function createOtp(string $phone): string {
    // Clean up any old attempts first
    DB::exec("DELETE FROM otp_codes WHERE phone = ?", [$phone]);

    $otp = generateOtp();
    $expiry_minutes = defined('OTP_EXPIRY_MINS') ? OTP_EXPIRY_MINS : 10;
    $expiry = date('Y-m-d H:i:s', time() + $expiry_minutes * 60);

    // This is now safe because the old record is gone
    DB::exec("INSERT INTO otp_codes (phone, otp, expires_at) VALUES (?, ?, ?)", [$phone, $otp, $expiry]);
    return $otp;
}

/**
 * Verifies an OTP for a given phone number.
 */
function verifyOtp(string $phone, string $otp): bool {
    $row = DB::row(
        "SELECT id FROM otp_codes 
         WHERE phone = ? AND otp = ? AND used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1",
        [$phone, $otp]
    );
    
    if (!$row) return false;
    
    // Mark as used so it cannot be reused
    DB::exec("UPDATE otp_codes SET used = 1 WHERE id = ?", [$row['id']]);
    return true;
}

/**
 * Sends OTP via PHPMailer (Gmail SMTP)
 */
function sendOtp(string $phone, string $otp, ?string $email = null): bool {
    if ($email === null) {
        $email = $_SESSION['pending_email'] ?? null;
    }

    // FALLBACK: If Demo Mode is ON, just log it to file
    if (defined('OTP_DEMO_MODE') && OTP_DEMO_MODE) {
        $logFile = __DIR__ . '/../otp_debug.log';
        $line    = '[' . date('Y-m-d H:i:s') . '] Phone: ' . $phone 
                 . ($email ? ' Email: ' . $email : '') 
                 . ' OTP: ' . $otp . PHP_EOL;
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        return true;
    }

    if (!$email) return false;

    // 1. CREATE the object FIRST (Fixed the order here)
    $mail = new PHPMailer(true);

    try {
        // 2. NOW set debug settings
       // $mail->SMTPDebug = 2; 
       // $mail->Debugoutput = 'html';

        // --- SMTP SERVER SETTINGS ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'civictrackproject@gmail.com'; 
        $mail->Password   = 'rmfxrcnmllhztnrl'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465;

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
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}