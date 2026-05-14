<?php
require_once __DIR__ . '/../config/config.php';

function verify_recaptcha(string $secret, string $response)
{
  $c = curl_init('https://www.google.com/recaptcha/api/siteverify');
  curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($c, CURLOPT_POST, true);
  curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query([
    'secret'   => $secret,
    'response' => $response,
  ]));
  curl_setopt($c, CURLOPT_TIMEOUT, 10);
  $result = curl_exec($c);
  // PHP 8+ auto-closes curl handles when they go out of scope; explicit
  // curl_close() is deprecated and a no-op.
  return $result ?: false;
}

/**
 * Strip CR/LF (and any other control chars) from a value before it goes into
 * an email header. Without this, attacker input like "Bob\r\nBcc: evil@x.com"
 * would inject extra headers — the classic email-header-injection vuln.
 *
 * Use this on ANY user-supplied value that ends up in a header (From, Reply-To,
 * Subject, Cc, Bcc, etc.). Body fields don't need it.
 */
function header_safe(string $value): string
{
  // Remove all ASCII control chars (0x00–0x1F, 0x7F) which includes \r and \n
  return trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value));
}

/**
 * Send the form email via SMTP using the bundled PHPMailer (lib/PHPMailer/).
 *
 * Called only when SMTP_HOST is set in .env. SMTP credentials, port, and
 * encryption all come from environment variables. Returns true on success,
 * false on any SMTP-side failure (auth rejected, connection refused, etc.).
 *
 * @return bool
 */
function sendViaSmtp(
  string $to,
  string $subject,
  string $html,
  string $no_reply_email,
  string $siteName,
  string $reply_to_email,
  string $reply_to_name,
  string $cc_email,
  string $bcc_email,
  string $messageId
): bool {
  require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
  require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
  require_once __DIR__ . '/../lib/PHPMailer/Exception.php';

  $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

  try {
    // Transport — SMTP with auth
    $mail->isSMTP();
    $mail->Host       = env('SMTP_HOST', '');
    $mail->Port       = (int) env('SMTP_PORT', '587');
    $mail->SMTPAuth   = true;
    $mail->Username   = env('SMTP_USER', '');
    $mail->Password   = env('SMTP_PASS', '');

    // Encryption: tls (STARTTLS, port 587) | ssl/smtps (port 465) | none
    $encryption = strtolower(env('SMTP_ENCRYPTION', 'tls'));
    if ($encryption === 'ssl' || $encryption === 'smtps') {
      $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === '' || $encryption === 'none') {
      $mail->SMTPSecure  = false;
      $mail->SMTPAutoTLS = false;
    } else {
      $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->CharSet  = 'UTF-8';
    $mail->Encoding = '8bit';

    // Addresses
    $fromName = env('SMTP_FROM_NAME', '') ?: $siteName;
    $mail->setFrom($no_reply_email, $fromName);
    $mail->addAddress($to);
    if ($reply_to_email !== '') {
      $mail->addReplyTo($reply_to_email, $reply_to_name);
    }
    foreach (array_filter(array_map('trim', explode(',', $cc_email))) as $cc) {
      $mail->addCC($cc);
    }
    foreach (array_filter(array_map('trim', explode(',', $bcc_email))) as $bcc) {
      $mail->addBCC($bcc);
    }

    // Message
    $mail->MessageID = $messageId;
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;
    // Plain-text alternative for clients that don't render HTML
    $mail->AltBody = trim(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html)));

    return $mail->send();
  } catch (\PHPMailer\PHPMailer\Exception) {
    // ErrorInfo carries the SMTP-side error; surfaces in PHP error log.
    // In dev mode (set in config.php), this also reaches the browser.
    error_log('SMTP send failed: ' . $mail->ErrorInfo);
    return false;
  }
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

  // verify reCAPTCHA (POST — keeps secret out of server logs)
  $verify = verify_recaptcha($recaptcha_server_secret, $_POST['token']);
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

  // 6) Headers — these apply to BOTH transports (mail() and SMTP)
  // RFC 5322 Message-ID with the no-reply domain helps DMARC alignment and
  // spam scoring. Date in proper RFC 2822 format. X-Mailer is transparent
  // identification (some spam filters lightly penalize missing X-Mailer).
  $messageId = '<' . bin2hex(random_bytes(16)) . '@' . $emailDomain . '>';

  // Header-safe versions of any user-supplied values that go into headers.
  // strip_tags() does NOT remove \r\n — without header_safe() an attacker
  // could submit `full_name=Bob\r\nBcc: evil@x.com` and inject extra headers.
  $full_name_h = header_safe($full_name);
  $siteName_h  = header_safe($siteName);

  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
  $headers .= "Content-Transfer-Encoding: 8bit\r\n";
  $headers .= "From: {$siteName_h} <{$no_reply_email}>\r\n";
  $headers .= "Reply-To: {$full_name_h} <{$email_address}>\r\n";
  $headers .= "Return-Path: <{$no_reply_email}>\r\n";
  $headers .= "Message-ID: {$messageId}\r\n";
  $headers .= "Date: " . date('r') . "\r\n";
  $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
  if (!empty($cc_email))  $headers .= "Cc: {$cc_email}\r\n";
  if (!empty($bcc_email)) $headers .= "Bcc: {$bcc_email}\r\n";

  // 7) Choose transport: SMTP (if configured in .env) or mail() (default)
  $smtpHost = env('SMTP_HOST', '');
  $sent = false;

  if ($smtpHost !== '') {
    // SMTP path — uses bundled PHPMailer (see lib/PHPMailer/).
    // PHPMailer escapes header values internally, but pass header-safe inputs
    // anyway so behavior matches the mail() path exactly.
    $sent = sendViaSmtp(
      $to, $subject, $html,
      $no_reply_email, $siteName_h,
      $email_address, $full_name_h,
      $cc_email, $bcc_email,
      $messageId
    );
  } else {
    // mail() path — passes -f to set envelope sender so SPF on $emailDomain
    // has a chance to align (works on any host that whitelists the web user
    // as a trusted MTA user — most shared hosts do)
    $sent = mail($to, $subject, $html, $headers, "-f {$no_reply_email}");
  }

  if ($sent) {
    header('Location: ' . $basePath . 'thankyou');
    exit;
  } else {
    throw new Exception('Failed to send. Please try again later.');
  }
} catch (Exception $e) {
  // Use json_encode (with HTML-safe flags) instead of addslashes — addslashes
  // doesn't escape `</script>` or `<` in error messages, so an exception text
  // containing those would break out of the <script> context (XSS vector).
  $msg = json_encode($e->getMessage(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
  echo '<script>alert(' . $msg . ');history.back();</script>';
  exit;
}
