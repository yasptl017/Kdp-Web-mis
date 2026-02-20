<?php
// Include database connection
include_once "Admin/dbconfig.php";

// Set JSON response header
header('Content-Type: application/json');

// Initialize response array
$response = array('success' => false, 'message' => '');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Sanitize and validate input
    $name = isset($_POST['name']) ? trim(htmlspecialchars($_POST['name'])) : '';
    $email = isset($_POST['email']) ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
    $phone = isset($_POST['phone']) ? trim(htmlspecialchars($_POST['phone'])) : '';
    $subject = isset($_POST['subject']) ? trim(htmlspecialchars($_POST['subject'])) : '';
    $message = isset($_POST['message']) ? trim(htmlspecialchars($_POST['message'])) : '';
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($subject) || empty($message)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email address.';
        echo json_encode($response);
        exit();
    }
    
    // Get college email from database
    $college_email = $college['email'];
    $college_name = $college['college_name'];
    
    // Email configuration
    $to = $college_email;
    $email_subject = "Contact Form: " . $subject;
    
    // Email body
    $email_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
            .header { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 20px; text-align: center; }
            .content { background: white; padding: 30px; margin-top: 20px; border-radius: 5px; }
            .field { margin-bottom: 20px; }
            .field-label { font-weight: bold; color: #1e3a8a; margin-bottom: 5px; }
            .field-value { color: #555; padding: 10px; background: #f5f5f5; border-left: 3px solid #f97316; }
            .footer { text-align: center; margin-top: 20px; color: #777; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Form Submission</h2>
                <p>$college_name</p>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='field-label'>Name:</div>
                    <div class='field-value'>$name</div>
                </div>
                <div class='field'>
                    <div class='field-label'>Email:</div>
                    <div class='field-value'>$email</div>
                </div>
                <div class='field'>
                    <div class='field-label'>Phone:</div>
                    <div class='field-value'>$phone</div>
                </div>
                <div class='field'>
                    <div class='field-label'>Subject:</div>
                    <div class='field-value'>$subject</div>
                </div>
                <div class='field'>
                    <div class='field-label'>Message:</div>
                    <div class='field-value'>$message</div>
                </div>
            </div>
            <div class='footer'>
                <p>This email was sent from the contact form on your website.</p>
                <p>Received on: " . date('F d, Y h:i A') . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    
    // Send email
    if (mail($to, $email_subject, $email_body, $headers)) {
        $response['success'] = true;
        $response['message'] = 'Thank you for contacting us! We will get back to you soon.';
    } else {
        $response['message'] = 'Sorry, there was an error sending your message. Please try again later.';
    }
    
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();
?>