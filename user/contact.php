<?php
session_start();

include("../database/db.php"); // db connection line

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

// Security: Validate session and sanitize output
$user_id = $_SESSION['user_id'];

$userQuery = "SELECT * FROM users WHERE id='$user_id'";
$userResult = $conn->query($userQuery);
$user = $userResult->fetch_assoc();

$userName = htmlspecialchars($user['name']);
$userEmail = htmlspecialchars($user['email'] ?? '');

$userInitial = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $userName), 0, 1));
$userInitial = !empty($userInitial) ? $userInitial : 'A';

// Photo path
$userPhoto = null;
if (!empty($user['photo'])) {
    if (filter_var($user['photo'], FILTER_VALIDATE_URL)) {
        $userPhoto = $user['photo'];
    } else {
        $userPhoto = '../assets/images/users/' . $user['photo'];
    }
}

// Contact information
$companyEmail = "support@hirex.com";
$companyPhone = "+91 1800-123-4567";
$companyAddress = "123 Tech Park, Bangalore, Karnataka 560001";
$supportHours = "24/7";

// Form submission handling
$formSubmitted = false;
$formError = "";
$submissionId = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars(trim($_POST['email']), ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars(trim($_POST['phone']), ENT_QUOTES, 'UTF-8');
    $subject = htmlspecialchars(trim($_POST['subject']), ENT_QUOTES, 'UTF-8');
    $category = htmlspecialchars($_POST['category'], ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8');
    
    // Validation
    if (empty($name) || empty($email) || empty($message)) {
        $formError = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = "Please enter a valid email address.";
    } elseif (strlen($message) < 10) {
        $formError = "Message must be at least 10 characters long.";
    } else {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO contacts (user_id, name, email, phone, subject, category, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("issssss", $user_id, $name, $email, $phone, $subject, $category, $message);
        
        if ($stmt->execute()) {
            $submissionId = $stmt->insert_id;
            $formSubmitted = true;
            
            // Send email notification
            sendContactEmail($name, $email, $subject, $message, $category, $submissionId);
        } else {
            $formError = "Failed to submit. Please try again later.";
        }
        $stmt->close();
    }
}

// Email notification function
function sendContactEmail($name, $email, $subject, $message, $category, $submissionId) {
    $to = "support@hirex.com";
    $emailSubject = "New Contact Form Submission #{$submissionId}";
    
    $emailBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Inter', sans-serif; background: #f8faf9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 30px; border: 1px solid #d1e8dd; }
            .header { background: linear-gradient(135deg, #22c55e, #14b8a6); padding: 24px; border-radius: 12px; margin-bottom: 24px; text-align: center; }
            .header h1 { color: white; margin: 0; font-size: 24px; }
            .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #d1e8dd; }
            .info-label { font-weight: 600; color: #4a5d55; }
            .info-value { color: #1a2f24; }
            .message-box { background: #f0fdf7; padding: 16px; border-radius: 10px; margin: 20px 0; }
            .footer { text-align: center; padding-top: 20px; color: #789085; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📬 New Contact Submission</h1>
            </div>
            <div class='info-row'>
                <span class='info-label'>Submission ID:</span>
                <span class='info-value'>#{$submissionId}</span>
            </div>
            <div class='info-row'>
                <span class='info-label'>Name:</span>
                <span class='info-value'>{$name}</span>
            </div>
            <div class='info-row'>
                <span class='info-label'>Email:</span>
                <span class='info-value'>{$email}</span>
            </div>
            <div class='info-row'>
                <span class='info-label'>Category:</span>
                <span class='info-value'>{$category}</span>
            </div>
            <div class='info-row'>
                <span class='info-label'>Subject:</span>
                <span class='info-value'>{$subject}</span>
            </div>
            <div class='message-box'>
                <strong>Message:</strong><br>
                {$message}
            </div>
            <div class='footer'>
                <p>This is an automated notification from HireX Contact Form</p>
                <p>© 2024 HireX. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: HireX Contact Form <noreply@hirex.com>\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    
    // Send email (requires proper mail server configuration)
    @mail($to, $emailSubject, $emailBody, $headers);
    
    // Optional: Send confirmation email to user
    $userSubject = "Thank you for contacting HireX - Submission #{$submissionId}";
    $userBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Inter', sans-serif; background: #f8faf9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 30px; border: 1px solid #d1e8dd; }
            .header { background: linear-gradient(135deg, #22c55e, #14b8a6); padding: 24px; border-radius: 12px; margin-bottom: 24px; text-align: center; }
            .header h1 { color: white; margin: 0; font-size: 24px; }
            .content { color: #4a5d55; line-height: 1.8; }
            .btn { display: inline-block; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none; margin-top: 16px; font-weight: 600; }
            .footer { text-align: center; padding-top: 20px; color: #789085; font-size: 12px; border-top: 1px dashed #d1e8dd; margin-top: 24px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ Thank You!</h1>
            </div>
            <div class='content'>
                <p>Dear {$name},</p>
                <p>Thank you for contacting HireX. We have received your message and our support team will get back to you within <strong>24-48 hours</strong>.</p>
                <p><strong>Submission Details:</strong></p>
                <ul>
                    <li>Reference ID: #{$submissionId}</li>
                    <li>Subject: {$subject}</li>
                    <li>Category: {$category}</li>
                </ul>
                <p>If you need immediate assistance, please call us at <strong>+91 1800-123-4567</strong>.</p>
                <center>
                    <a href='https://hirex.com/help.php' class='btn'>Visit Help Center</a>
                </center>
            </div>
            <div class='footer'>
                <p>© 2024 HireX. All rights reserved.</p>
                <p>This is an automated confirmation email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $userHeaders = "MIME-Version: 1.0\r\n";
    $userHeaders .= "Content-type: text/html; charset=UTF-8\r\n";
    $userHeaders .= "From: HireX Support <support@hirex.com>\r\n";
    
    @mail($email, $userSubject, $userBody, $userHeaders);
}

// Get user's previous submissions
$previousContacts = [];
$contactHistory = $conn->query("SELECT * FROM contacts WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");
if ($contactHistory && $contactHistory->num_rows > 0) {
    while($row = $contactHistory->fetch_assoc()) {
        $previousContacts[] = $row;
    }
}

// SVG Icon Function
function getIcon($name, $size = 20, $class = '') {
    $icons = [
        'grid' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'bolt' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
        'drop' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>',
        'hammer' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12l-8.5 8.5c-.83.83-2.17.83-3 0 0 0 0 0 0a2.12 2.12 0 0 1 0-3L12 9"/><path d="M17.64 15L22 10.64"/><path d="M20.91 11.26a2 2 0 0 0 2.83-.25l.5-.5a2 2 0 0 0-.25-2.83L22 5.64l-6.36-6.36a2 2 0 0 0-2.83.25l-.5.5a2 2 0 0 0 .25 2.83L14.36 5"/></svg>',
        'brush' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.37 2.63L14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9 1.59-1.59a2 2 0 0 0 0-2.82L17 10l4.37-4.37a2.12 2.12 0 1 0-3-3z"/><path d="M9 8c-2 3-4 3.5-7 4l8 10c2-1 6-5 6-7"/><path d="M14.5 17.5L4.5 15"/></svg>',
        'snowflake' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/><path d="M20 16l-4-4 4-4"/><path d="M4 8l4 4-4 4"/><path d="M16 4l-4 4-4-4"/><path d="M8 20l4-4 4 4"/></svg>',
        'wrench' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'dashboard' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>',
        'user' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'message' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
        'calendar' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'bookmark' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>',
        'card' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'settings' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'help' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'phone' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'logout' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'search' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        'bell' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'moon' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
        'sun' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
        'star' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'clock' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'briefcase' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
        'check' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'x' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'menu' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'workers' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'arrow-right' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
        'location' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'mail' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>',
        'send' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
        'map-pin' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'hours' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'copy' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
        'external-link' => '<svg class="'.$class.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
    ];
    return $icons[$name] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="HireX - Contact Us">
    <title>Contact Us — HireX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Light Green / Mint Theme */
            --mint-50: #f0fdf7;
            --mint-100: #dcfce7;
            --mint-200: #bbf7d0;
            --mint-300: #86efac;
            --mint-400: #4ade80;
            --mint-500: #22c55e;
            --mint-600: #16a34a;
            
            --teal-50: #f0fdfa;
            --teal-100: #ccfbf1;
            --teal-500: #14b8a6;
            --teal-600: #0d9488;
            
            /* Theme Variables */
            --bg: #f8faf9;
            --bg-secondary: #ffffff;
            --sidebar-width: 250px;
            --primary: var(--mint-600);
            --primary-hover: #15803d;
            --primary-light: var(--mint-100);
            --secondary: var(--teal-500);
            --secondary-hover: #0f766e;
            --text-primary: #1a2f24;
            --text-secondary: #4a5d55;
            --text-gray: #789085;
            --border: #d1e8dd;
            --shadow: rgba(22, 163, 74, 0.08);
            --shadow-lg: rgba(22, 163, 74, 0.15);
            --danger: #ef4444;
            --success: var(--mint-500);
            --warning: #f59e0b;
            --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --bg: #0d1411;
            --bg-secondary: #141c18;
            --text-primary: #e0f2e8;
            --text-secondary: #9dbfa8;
            --text-gray: #789085;
            --border: #2d3d33;
            --shadow: rgba(0,0,0,0.4);
            --shadow-lg: rgba(0,0,0,0.6);
            --primary: var(--mint-500);
            --primary-hover: var(--mint-400);
            --primary-light: rgba(34, 197, 94, 0.15);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            display: flex;
            color: var(--text-primary);
            transition: var(--transition);
            overflow-x: hidden;
            line-height: 1.6;
            background-image: 
                radial-gradient(ellipse at top right, rgba(34, 197, 94, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(20, 184, 166, 0.08) 0%, transparent 50%);
        }

        svg { display: block; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-secondary);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            position: fixed;
            border-right: 1px solid var(--border);
            z-index: 1000;
            transition: var(--transition);
        }

        .logo {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 32px;
            padding-left: 14px;
            letter-spacing: -0.5px;
            color: var(--text-primary);
        }

        .logo .x { 
            color: var(--primary);
        }

        .nav-group { margin-bottom: 24px; }

        .nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-gray);
            margin-bottom: 12px;
            padding-left: 14px;
            font-weight: 700;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 11px 14px;
            text-decoration: none;
            color: var(--text-secondary);
            border-radius: 10px;
            margin-bottom: 4px;
            transition: var(--transition);
            font-weight: 500;
            cursor: pointer;
            font-size: 13px;
            gap: 12px;
        }

        .nav-item:hover {
            background: var(--primary-light);
            color: var(--primary);
            transform: translateX(4px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            box-shadow: 0 4px 15px var(--shadow-lg);
        }

        .nav-item svg { width: 18px; height: 18px; }

        .badge {
            background: var(--danger);
            color: white;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 6px;
            margin-left: auto;
            font-weight: 700;
        }

        /* Sign Out - Bottom Left */
        .signout-container {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .signout-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            width: 100%;
            text-decoration: none;
            color: var(--danger);
            background: #fef2f2;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
            justify-content: flex-start;
        }

        [data-theme="dark"] .signout-btn { background: rgba(239, 68, 68, 0.15); }

        .signout-btn:hover {
            background: var(--danger);
            color: white;
            transform: translateX(4px);
        }

        .signout-btn svg { width: 18px; height: 18px; }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 0 32px 32px 32px;
            transition: var(--transition);
        }

        .mobile-toggle {
            display: none;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            cursor: pointer;
            color: var(--text-primary);
            padding: 10px 12px;
            border-radius: 10px;
            transition: var(--transition);
        }

        .mobile-toggle:hover { background: var(--primary-light); border-color: var(--primary); }

        /* --- HEADER --- */
        header {
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 13px;
            flex: 1;
            min-width: 270px;
        }

        .search-bar {
            background: var(--bg-secondary);
            padding: 11px 15px;
            border-radius: 11px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            flex: 1;
            max-width: 480px;
            transition: var(--transition);
        }

        .search-bar:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.12);
        }

        .search-bar input {
            border: none;
            outline: none;
            width: 100%;
            margin-left: 11px;
            background: transparent;
            color: var(--text-primary);
            font-size: 13px;
            font-weight: 500;
        }

        .search-bar input::placeholder { color: var(--text-gray); }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .icon-btn {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 11px;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .icon-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .icon-btn .notification-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: var(--danger);
            border-radius: 50%;
            border: 2px solid var(--bg-secondary);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg-secondary);
            padding: 5px 5px 5px 5px;
            border-radius: 30px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
            padding-right: 15px;
        }

        .user-pill:hover { border-color: var(--primary); box-shadow: 0 4px 14px var(--shadow); }

        .avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--mint-500), var(--teal-500)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: white; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .theme-toggle {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 10px 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-secondary);
            transition: var(--transition);
            font-weight: 500;
        }

        .theme-toggle:hover { border-color: var(--primary); background: var(--primary-light); }

        /* --- PAGE TITLE --- */
        .page-title { 
            margin: 8px 0 20px 0;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        
        .page-title h2 { 
            margin: 0; 
            font-size: 23px; 
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-primary);
        }
        
        .page-title p { 
            color: var(--text-gray); 
            margin-top: 6px; 
            font-size: 13px;
        }

        /* --- CONTACT PAGE SPECIFIC STYLES --- */

        /* Contact Hero */
        .contact-hero {
            background: linear-gradient(135deg, var(--mint-500), var(--teal-500));
            border-radius: 20px;
            padding: 48px;
            margin-bottom: 32px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .contact-hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }

        .contact-hero h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 12px;
            position: relative;
        }

        .contact-hero p {
            font-size: 16px;
            opacity: 0.95;
            max-width: 550px;
            margin: 0 auto;
            position: relative;
        }

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 24px;
            align-items: stretch;
        }

        /* Section Card */
        .section-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 0;
            flex: 1;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .section-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .section-icon.green { background: linear-gradient(135deg, var(--mint-100), var(--mint-200)); color: var(--mint-600); }
        .section-icon.teal { background: linear-gradient(135deg, var(--teal-100), #99f6e4); color: var(--teal-600); }
        .section-icon.blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }

        .section-header h3 {
            font-size: 18px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-primary);
        }

        /* Contact Form */
        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-group label .required {
            color: var(--danger);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            color: var(--text-primary);
            background: var(--bg);
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.12);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }

        .char-count {
            font-size: 11px;
            color: var(--text-gray);
            text-align: right;
            margin-top: -4px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--mint-500), var(--mint-600));
            color: white;
            border: none;
            padding: 16px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, var(--primary-hover), var(--mint-500));
            transform: translateY(-3px);
            box-shadow: 0 10px 30px var(--shadow-lg);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-submit svg { width: 18px; height: 18px; }

        /* Success Banner */
        .success-banner {
            background: linear-gradient(135deg, var(--mint-100), var(--mint-200));
            border: 1px solid var(--mint-300);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .success-banner-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: var(--success);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .success-banner-content h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--mint-600);
            margin-bottom: 6px;
        }

        .success-banner-content p {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .success-banner-content .ref-id {
            font-weight: 700;
            color: var(--primary);
        }

        /* Error Banner */
        .error-banner {
            background: linear-gradient(135deg, #fef2f2, #fecaca);
            border: 1px solid #fca5a5;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .error-banner-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: var(--danger);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .error-banner-content h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--danger);
            margin-bottom: 6px;
        }

        .error-banner-content p {
            font-size: 13px;
            color: var(--text-secondary);
        }


        .main-column {
            display: flex;
            flex-direction: column;
        }

        /* Contact Info Sidebar */
        .contact-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
            height: 100%;
        }

        /* Contact Cards */
        .contact-info-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            transition: var(--transition);
            flex:1;
        }

        .contact-info-card:last-child {
            flex: 2;
        }

        .contact-info-card:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px var(--shadow);
        }

        .contact-info-card .card-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .contact-info-card .card-icon.green { background: linear-gradient(135deg, var(--mint-100), var(--mint-200)); color: var(--mint-600); }
        .contact-info-card .card-icon.teal { background: linear-gradient(135deg, var(--teal-100), #99f6e4); color: var(--teal-600); }
        .contact-info-card .card-icon.blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
        .contact-info-card .card-icon.yellow { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }

        .contact-info-card h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .contact-info-card .value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
            word-break: break-word;
        }

        .contact-info-card .subtext {
            font-size: 12px;
            color: var(--text-gray);
        }

        .contact-info-card .copy-btn {
            background: var(--primary-light);
            color: var(--primary);
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .contact-info-card .copy-btn:hover {
            background: var(--primary);
            color: white;
        }

        .contact-info-card .copy-btn svg { width: 14px; height: 14px; }

        /* Map Placeholder */
        .map-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .map-placeholder {
            height: 200px;
            background: linear-gradient(135deg, var(--mint-100), var(--teal-100));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-gray);
            font-size: 13px;
        }

        .map-placeholder svg {
            width: 48px;
            height: 48px;
            margin-bottom: 12px;
            opacity: 0.6;
        }

        /* Previous Submissions */
        .submissions-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .submission-item {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            transition: var(--transition);
            cursor: pointer;
        }

        .submission-item:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .submission-id {
            font-size: 12px;
            font-weight: 700;
            color: var(--primary);
            background: var(--mint-50);
            padding: 4px 10px;
            border-radius: 8px;
        }

        .submission-status {
            font-size: 10px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            text-transform: uppercase;
        }

        .submission-status.pending { background: #fef3c7; color: #92400e; }
        .submission-status.replied { background: var(--mint-100); color: var(--mint-600); }
        .submission-status.resolved { background: #dbeafe; color: #2563eb; }

        .submission-subject {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .submission-date {
            font-size: 11px;
            color: var(--text-gray);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .submission-date svg { width: 12px; height: 12px; }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }

        .quick-action-btn {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text-primary);
        }

        .quick-action-btn:hover {
            border-color: var(--primary);
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .quick-action-btn svg {
            width: 22px;
            height: 22px;
            margin: 0 auto 8px auto;
            color: var(--primary);
        }

        .quick-action-btn span {
            font-size: 12px;
            font-weight: 600;
        }

        /* --- TOAST --- */
        .toast {
            position: fixed;
            bottom: 26px;
            right: 26px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-left: 5px solid var(--success);
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 14px 45px var(--shadow-lg);
            transform: translateX(150%);
            transition: var(--transition);
            z-index: 2000;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 280px;
        }

        .toast.show { transform: translateX(0); }
        .toast.error { border-left-color: var(--danger); }
        .toast.warning { border-left-color: var(--warning); }

        .toast-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .toast.success .toast-icon { background: rgba(34, 197, 94, 0.15); color: var(--success); }
        .toast.error .toast-icon { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
        .toast-icon svg { width: 17px; height: 17px; }

        .toast-content { flex: 1; }
        .toast-title { font-weight: 600; color: var(--text-primary); font-size: 13px; }
        .toast-message { font-size: 12px; color: var(--text-gray); margin-top: 2px; }

        /* --- OVERLAY --- */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(13, 20, 17, 0.65);
            backdrop-filter: blur(4px);
            z-index: 999;
            opacity: 0;
            transition: var(--transition);
        }

        .overlay.active { display: block; opacity: 1; }

        /* --- RESPONSIVE --- */
        @media (max-width: 1200px) {
            .contact-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 1024px) {
            .main-content { padding: 0 26px 26px 26px; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 18px 18px 18px; }
            .mobile-toggle { display: flex; }
            .header-left { width: 100%; justify-content: space-between; }
            .search-bar { order: 3; width: 100%; max-width: none; margin-top: 13px; }
            .contact-hero { padding: 32px 24px; }
            .contact-hero h1 { font-size: 26px; }
            .form-row, .form-row-3 { grid-template-columns: 1fr; }
            .toast { left: 18px; right: 18px; bottom: 18px; min-width: auto; }
            .page-title h2 { font-size: 20px; }
        }

        @media (max-width: 480px) {
            .user-name { display: none; }
            .theme-toggle span:last-child { display: none; }
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--mint-300); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--mint-500); }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="logo">Hire<span class="x">X</span></div>

    <nav>
        <div class="nav-group">
            <div class="nav-label">Main Menu</div>
            <a href="dashboard.php" class="nav-item">
                <?php echo getIcon('dashboard', 18); ?> Dashboard
            </a>
            <a href="profile.php" class="nav-item">
                <?php echo getIcon('user', 18); ?> My Profile
            </a>
            <a href="messages.php" class="nav-item">
                <?php echo getIcon('message', 18); ?> Messages
            </a>
            <a href="booking.php" class="nav-item">
                <?php echo getIcon('calendar', 18); ?> My Bookings
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Preferences</div>
            <a href="saved-worker.php" class="nav-item">
                <?php echo getIcon('bookmark', 18); ?> Saved Workers
            </a>
            <a href="payment.php" class="nav-item">
                <?php echo getIcon('card', 18); ?> Payments
            </a>
            <a href="settings.php" class="nav-item">
                <?php echo getIcon('settings', 18); ?> Settings
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Support</div>
            <a href="help.php" class="nav-item">
                <?php echo getIcon('help', 18); ?> Help Center
            </a>
            <a href="contact.php" class="nav-item active">
                <?php echo getIcon('phone', 18); ?> Contact Us
            </a>
        </div>
    </nav>

    <!-- Sign Out - Bottom Left -->
    <div class="signout-container">
        <a href="logout.php" class="signout-btn">
            <?php echo getIcon('logout', 18); ?> Sign Out
        </a>
    </div>
</aside>

<main class="main-content" id="mainContent">
    <header>
        <div class="header-left">
            <button class="mobile-toggle" onclick="toggleSidebar()" aria-label="Toggle Menu">
                <?php echo getIcon('menu', 20); ?>
            </button>
            <form class="search-bar" method="GET" action="dashboard.php">
                <?php echo getIcon('search', 18); ?>
                <input type="text" name="search" placeholder="Search workers..." aria-label="Search workers">
                <button type="submit" style="background:none; border:none; cursor:pointer; color: var(--primary); font-weight: 600; font-size: 13px;">Search</button>
            </form>
        </div>

        <div class="header-actions">
            <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle Dark Mode">
                <span id="themeIcon"><?php echo getIcon('moon', 16); ?></span>
                <span id="themeText">Dark</span>
            </button>
            
            <button class="icon-btn" aria-label="Notifications" onclick="showToast('Notifications', 'You have 3 new messages', true)">
                <?php echo getIcon('bell', 18); ?>
                <span class="notification-dot"></span>
            </button>
            
            <div class="user-pill" aria-label="User Profile">
                <div class="avatar">
                    <?php if ($userPhoto): ?>
                        <img src="<?php echo $userPhoto; ?>" alt="<?php echo $userName; ?>">
                    <?php else: ?>
                        <?php echo $userInitial; ?>
                    <?php endif; ?>
                </div>
                <span class="user-name"><?php echo $userName; ?></span>
            </div>
        </div>
    </header>

    <div class="page-title">
        <h2>Contact Us</h2>
        <p>Get in touch with our support team. We're here to help!</p>
    </div>

    <!-- Contact Hero -->
    <div class="contact-hero">
        <h1>📬 We'd Love to Hear From You</h1>
        <p>Have questions, feedback, or need assistance? Fill out the form below and our team will get back to you within 24-48 hours.</p>
    </div>

    <!-- Contact Grid -->
    <div class="contact-grid">
        <!-- Left Column - Form -->
        <div class="main-column">
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon green">
                        <?php echo getIcon('message', 24); ?>
                    </div>
                    <h3>Send us a Message</h3>
                </div>

                <?php if ($formSubmitted): ?>
                    <div class="success-banner">
                        <div class="success-banner-icon">
                            <?php echo getIcon('check', 24); ?>
                        </div>
                        <div class="success-banner-content">
                            <h4>Message Sent Successfully!</h4>
                            <p>Thank you for contacting us. Your reference ID is <span class="ref-id">#<?php echo $submissionId; ?></span>. We'll respond to <strong><?php echo htmlspecialchars($_POST['email'] ?? $userEmail); ?></strong> within 24-48 hours.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($formError): ?>
                    <div class="error-banner">
                        <div class="error-banner-icon">
                            <?php echo getIcon('alert-circle', 24); ?>
                        </div>
                        <div class="error-banner-content">
                            <h4>Submission Failed</h4>
                            <p><?php echo $formError; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="contact-form" method="POST" action="" id="contactForm" onsubmit="return validateForm()">
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <?php echo getIcon('user', 16); ?>
                                Full Name <span class="required">*</span>
                            </label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" placeholder="John Doe" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <?php echo getIcon('mail', 16); ?>
                                Email Address <span class="required">*</span>
                            </label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" placeholder="john@example.com" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <?php echo getIcon('phone', 16); ?>
                                Phone Number
                            </label>
                            <input type="tel" name="phone" placeholder="+91 98765 43210" pattern="[0-9+\-\s()]{10,15}">
                        </div>
                        <div class="form-group">
                            <label>
                                <?php echo getIcon('help', 16); ?>
                                Category
                            </label>
                            <select name="category">
                                <option value="general">General Inquiry</option>
                                <option value="support">Technical Support</option>
                                <option value="billing">Billing & Payments</option>
                                <option value="booking">Booking Issue</option>
                                <option value="feedback">Feedback & Suggestions</option>
                                <option value="partnership">Partnership</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <?php echo getIcon('mail', 16); ?>
                            Subject <span class="required">*</span>
                        </label>
                        <input type="text" name="subject" placeholder="Brief summary of your message" required>
                    </div>

                    <div class="form-group">
                        <label>
                            <?php echo getIcon('message', 16); ?>
                            Message <span class="required">*</span>
                        </label>
                        <textarea name="message" id="message" placeholder="Describe your query in detail..." required oninput="updateCharCount()"></textarea>
                        <span class="char-count"><span id="charCount">0</span> / 1000 characters</span>
                    </div>

                    <button type="submit" name="submit_contact" class="btn-submit" id="submitBtn">
                        <?php echo getIcon('send', 18); ?> Send Message
                    </button>
                </form>
            </div>

            <!-- Previous Submissions -->
            <?php if (!empty($previousContacts)): ?>
            <div class="section-card">
                <div class="section-header">
                    <div class="section-icon teal">
                        <?php echo getIcon('clock', 24); ?>
                    </div>
                    <h3>Previous Submissions</h3>
                </div>
                <div class="submissions-list">
                    <?php foreach($previousContacts as $contact): ?>
                        <div class="submission-item">
                            <div class="submission-header">
                                <span class="submission-id">#<?php echo $contact['id']; ?></span>
                                <span class="submission-status <?php echo $contact['status']; ?>">
                                    <?php echo $contact['status']; ?>
                                </span>
                            </div>
                            <div class="submission-subject"><?php echo htmlspecialchars($contact['subject']); ?></div>
                            <div class="submission-date">
                                <?php echo getIcon('clock', 12); ?>
                                <?php echo date('M d, Y', strtotime($contact['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - Contact Info -->
        <div class="contact-sidebar">
            <!-- Email Card -->
            <div class="contact-info-card">
                <div class="card-icon green">
                    <?php echo getIcon('mail', 26); ?>
                </div>
                <h4>Email Us</h4>
                <div class="value"><?php echo $companyEmail; ?></div>
                <div class="subtext">Response within 24 hours</div>
                <button class="copy-btn" onclick="copyToClipboard('<?php echo $companyEmail; ?>')">
                    <?php echo getIcon('copy', 14); ?> Copy
                </button>
            </div>

            <!-- Phone Card -->
            <div class="contact-info-card">
                <div class="card-icon teal">
                    <?php echo getIcon('phone', 26); ?>
                </div>
                <h4>Call Us</h4>
                <div class="value"><?php echo $companyPhone; ?></div>
                <div class="subtext">Available <?php echo $supportHours; ?></div>
                <button class="copy-btn" onclick="copyToClipboard('<?php echo $companyPhone; ?>')">
                    <?php echo getIcon('copy', 14); ?> Copy
                </button>
            </div>

            <!-- Address Card -->
            <div class="contact-info-card">
                <div class="card-icon blue">
                    <?php echo getIcon('map-pin', 26); ?>
                </div>
                <h4>Visit Us</h4>
                <div class="value"><?php echo $companyAddress; ?></div>
                <div class="subtext">Bangalore, Karnataka</div>
                <a href="https://maps.google.com/?q=<?php echo urlencode($companyAddress); ?>" target="_blank" class="copy-btn" style="text-decoration: none;">
                    <?php echo getIcon('external-link', 14); ?> Open Maps
                </a>
            </div>
         
        </div>
    </div>
</main>

<div class="toast" id="toast">
    <div class="toast-icon" id="toastIconBox"><?php echo getIcon('check', 17); ?></div>
    <div class="toast-content">
        <div class="toast-title" id="toastTitle">Success</div>
        <div class="toast-message" id="toastMessage">Action completed!</div>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    function toggleTheme() {
        const html = document.documentElement;
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        
        if (html.getAttribute('data-theme') === 'dark') {
            html.removeAttribute('data-theme');
            themeIcon.innerHTML = '<?php echo getIcon("moon", 16); ?>';
            themeText.textContent = 'Dark';
            localStorage.setItem('theme', 'light');
        } else {
            html.setAttribute('data-theme', 'dark');
            themeIcon.innerHTML = '<?php echo getIcon("sun", 16); ?>';
            themeText.textContent = 'Light';
            localStorage.setItem('theme', 'dark');
        }
    }

    (function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('themeIcon').innerHTML = '<?php echo getIcon("sun", 16); ?>';
            document.getElementById('themeText').textContent = 'Light';
        }
    })();

    function updateCharCount() {
        const message = document.getElementById('message');
        const count = document.getElementById('charCount');
        const maxLength = 1000;
        
        count.textContent = message.value.length;
        
        if (message.value.length > maxLength) {
            count.style.color = 'var(--danger)';
            message.value = message.value.substring(0, maxLength);
        } else {
            count.style.color = 'var(--text-gray)';
        }
    }

    function validateForm() {
        const message = document.getElementById('message');
        const submitBtn = document.getElementById('submitBtn');
        
        if (message.value.trim().length < 10) {
            showToast('Validation Error', 'Message must be at least 10 characters', false);
            return false;
        }
        
        // Disable button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<?php echo getIcon("clock", 18); ?> Sending...';
        
        return true;
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied!', text + ' copied to clipboard', true);
        }).catch(() => {
            showToast('Error', 'Failed to copy to clipboard', false);
        });
    }

    function showToast(title, message, success = true) {
        const toast = document.getElementById('toast');
        const toastIconBox = document.getElementById('toastIconBox');
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');
        
        toastIconBox.innerHTML = success ? '<?php echo getIcon("check", 17); ?>' : '<?php echo getIcon("x", 17); ?>';
        toastTitle.textContent = title;
        toastMessage.textContent = message;
        toast.className = 'toast' + (success ? ' success' : ' warning') + ' show';
        
        setTimeout(() => { toast.classList.remove('show'); }, 3000);
    }

    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.mobile-toggle');
        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target) && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('sidebar').classList.contains('active')) toggleSidebar();
    });

    // Auto-hide success banner after 8 seconds
    <?php if ($formSubmitted): ?>
    setTimeout(() => {
        const banner = document.querySelector('.success-banner');
        if (banner) {
            banner.style.transition = 'opacity 0.5s';
            banner.style.opacity = '0';
            setTimeout(() => banner.remove(), 500);
        }
        // Reset form
        document.getElementById('contactForm').reset();
        document.getElementById('charCount').textContent = '0';
    }, 8000);
    <?php endif; ?>

    // Initialize character count if there's existing value
    document.addEventListener('DOMContentLoaded', function() {
        const message = document.getElementById('message');
        if (message) {
            document.getElementById('charCount').textContent = message.value.length;
        }
    });
</script>

</body>
</html>
