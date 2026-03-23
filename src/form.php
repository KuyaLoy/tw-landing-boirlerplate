<?php
require_once __DIR__ . '/../includes/config.php';

function curl_get_file_contents(string $URL)
{
  $c = curl_init();
  curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($c, CURLOPT_URL, $URL);
  $contents = curl_exec($c);
  curl_close($c);
  return $contents ?: false;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

try {
  // 1) Honeypot
  if (!empty($_POST['honeypot'] ?? '')) {
    throw new Exception('Bot detected');
  }
  // 2) reCAPTCHA token
  if (empty($_POST['token'] ?? '')) {
    throw new Exception('No reCAPTCHA token');
  }

  // verify reCAPTCHA
  $recaptcha_url    = 'https://www.google.com/recaptcha/api/siteverify';
  $recaptcha_secret = $recaptcha_server_secret;
  $recaptcha_resp   = $_POST['token'];
  $verify = curl_get_file_contents(
    $recaptcha_url
      . '?secret=' . urlencode($recaptcha_secret)
      . '&response=' . urlencode($recaptcha_resp)
  );
  $recap = json_decode($verify);
  if (!$recap || empty($recap->success) || $recap->success !== true) {
    throw new Exception('reCAPTCHA verification failed.');
  }
  if ($recap->score < 0.5) {
    throw new Exception('Low reCAPTCHA score. Please try again.');
  }

  // 3) Sanitize & assign form fields
  $loan_option     = strip_tags(trim($_POST['loanOption']    ?? ''));
  $amount          = strip_tags(trim($_POST['amount']        ?? ''));
  $full_name       = strip_tags(trim($_POST['full_name']     ?? ''));
  $contact_number  = preg_replace('/\D+/', '', trim($_POST['contact_number'] ?? ''));
  $email_address   = filter_var(trim($_POST['email']         ?? ''), FILTER_SANITIZE_EMAIL);
  $notes           = strip_tags(trim($_POST['notes'] ?? ''));

  // 4) Required checks
  if (!$loan_option || !$amount || !$full_name || !$contact_number || !$email_address) {
    throw new Exception('Please complete all required fields.');
  }
  if (!preg_match('/^0[2|3|4|7|8]\d{8}$/', $contact_number)) {
    throw new Exception('Invalid phone number. Enter a 10-digit number starting with 0.');
  }
  if (!filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Invalid email address.');
  }

  // 5) Build email
  $to       = $admin_email;
  $subject  = "New Loan Request from $full_name";
  $year     = date('Y');
  $logoUrl  = $baseUrl . 'assets/images/logo.png';
  $siteName = $site;

  $html = <<<HTML
<!DOCTYPE html>
<html>
  <body style="margin:0; padding:0; background-color:#f4f4f4;">
    <!-- Preheader Text -->
    <span style="display:none !important; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
      A new loan request has been submitted. See details below.
    </span>

    <!-- Outer Container -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" align="center"
      style="background-color:#f4f4f4; padding:20px 0; margin:0;">
      <tr>
        <td align="center">
          <!-- Inner 600px Table -->
          <table width="600" cellpadding="0" cellspacing="0" border="0"
            style="background-color:#ffffff; border-collapse:collapse; font-family:Arial,sans-serif; color:#333333; margin:0 auto;">

            <!-- Dark Header with Logo -->
            <tr>
              <td align="center"
                style="padding:20px; background-color:#CAB644; border-top-left-radius:4px; border-top-right-radius:4px;">
                <a href="{$baseUrl}" target="_blank" style="text-decoration:none;">
                  <img src="{$logoUrl}"
                       alt="{$siteName} Logo"
                       width="175"
                       style="display:block; border:0; outline:none; text-decoration:none;">
                </a>
              </td>
            </tr>

            <!-- Divider -->
            <tr>
              <td style="border-bottom:1px solid #dddddd; height:1px; line-height:1px; font-size:0;">&nbsp;</td>
            </tr>

            <!-- Greeting & Intro -->
            <tr>
              <td style="padding:20px;">
                <p style="margin:0 0 15px 0; font-size:16px; line-height:1.5;">
                  <strong>Dear Team,</strong>
                </p>
                <p style="margin:0 0 20px 0; font-size:14px; line-height:1.5;">
                  A new loan request has arrived. Below are the submitted details:
                </p>
              </td>
            </tr>

            <!-- Details Table -->
            <tr>
              <td style="padding:0 20px 20px 20px;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0"
                  style="border-collapse:collapse; font-size:14px;">

                  <tr>
                    <td width="35%"
                      style="padding:10px; background-color:#fafafa; border:1px solid #dddddd; font-weight:bold;">
                      Loan Type:
                    </td>
                    <td style="padding:10px; background-color:#ffffff; border:1px solid #dddddd;">
                      {$loan_option}
                    </td>
                  </tr>

                  <tr>
                    <td style="padding:10px; background-color:#fafafa; border:1px solid #dddddd; font-weight:bold;">
                      Amount:
                    </td>
                    <td style="padding:10px; background-color:#ffffff; border:1px solid #dddddd;">
                      \${$amount}
                    </td>
                  </tr>

                  <tr>
                    <td style="padding:10px; background-color:#fafafa; border:1px solid #dddddd; font-weight:bold;">
                      Full Name:
                    </td>
                    <td style="padding:10px; background-color:#ffffff; border:1px solid #dddddd;">
                      {$full_name}
                    </td>
                  </tr>

                  <tr>
                    <td style="padding:10px; background-color:#fafafa; border:1px solid #dddddd; font-weight:bold;">
                      Email Address:
                    </td>
                    <td style="padding:10px; background-color:#ffffff; border:1px solid #dddddd;">
                      {$email_address}
                    </td>
                  </tr>

                  <tr>
                    <td style="padding:10px; background-color:#fafafa; border:1px solid #dddddd; font-weight:bold;">
                      Contact Number:
                    </td>
                    <td style="padding:10px; background-color:#ffffff; border:1px solid #dddddd;">
                      {$contact_number}
                    </td>
                  </tr>
HTML;

  if ($notes !== '') {
    $html .= <<<HTML
                  <tr>
                    <td style="padding:10px; background-color:#fafafa; border:1px solid #dddddd; font-weight:bold;">
                      Notes:
                    </td>
                    <td style="padding:10px; background-color:#ffffff; border:1px solid #dddddd;">
                      {$notes}
                    </td>
                  </tr>
HTML;
  }

  $html .= <<<HTML
                </table>
              </td>
            </tr>

            <!-- Closing Paragraph -->
            <tr>
              <td style="padding:0 20px 20px 20px;">
                <p style="margin:0 0 15px 0; font-size:14px; line-height:1.5;">
                  Please follow up with this prospect at your earliest convenience.
                </p>
                <p style="margin:0; font-size:14px; line-height:1.5;">
                  Kind regards,<br>
                  <strong>{$siteName}</strong><br>
                  <em>Customer Success Team</em>
                </p>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td align="center"
                style="padding:20px; background-color:#f9f9f9; border-bottom-left-radius:4px; border-bottom-right-radius:4px; font-size:12px; color:#777777;">
                &copy; {$year} {$siteName}. All rights reserved.
              </td>
            </tr>

          </table>
          <!-- End Inner Table -->

        </td>
      </tr>
    </table>
    <!-- End Outer Container -->
  </body>
</html>
HTML;

  // 6) Headers and send
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
  $headers .= "From: {$siteName} <{$no_reply_email}>\r\n";
  $headers .= "Reply-To: {$full_name} <{$email_address}>\r\n";
  if (!empty($cc_email))  $headers .= "Cc: {$cc_email}\r\n";
  if (!empty($bcc_email)) $headers .= "Bcc: {$bcc_email}\r\n";

  if (mail($to, $subject, $html, $headers)) {
    header('Location: ' . $basePath . 'thankyou.php');
    exit;
  } else {
    throw new Exception('Failed to send. Please try again later.');
  }
} catch (Exception $e) {
  echo '<script>alert("' . addslashes($e->getMessage()) . '");history.back();</script>';
  exit;
}
