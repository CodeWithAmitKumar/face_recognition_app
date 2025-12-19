<?php
// Dynamic Email Configuration - Reads from Database
require_once __DIR__ . '/config.php';

// Get email settings from database
$email_query = "SELECT * FROM email_settings LIMIT 1";
$email_result = mysqli_query($conn, $email_query);

if ($email_result && mysqli_num_rows($email_result) > 0) {
    // Settings found in database
    $email_settings = mysqli_fetch_assoc($email_result);
    
    define('SMTP_HOST', $email_settings['smtp_host']);
    define('SMTP_PORT', $email_settings['smtp_port']);
    define('SMTP_USERNAME', $email_settings['smtp_username']);
    define('SMTP_PASSWORD', $email_settings['smtp_password']);
    define('SMTP_FROM_EMAIL', $email_settings['smtp_from_email']);
    define('SMTP_FROM_NAME', $email_settings['smtp_from_name']);
} else {
    // Fallback to default values if no settings in database
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_USERNAME', ''); // Empty - needs to be configured
    define('SMTP_PASSWORD', ''); // Empty - needs to be configured
    define('SMTP_FROM_EMAIL', 'noreply@photoalbum.com');
    define('SMTP_FROM_NAME', 'Photo Album System');
}
?>
