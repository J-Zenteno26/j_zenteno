<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../src/Exception.php';
require __DIR__ . '/../src/PHPMailer.php';
require __DIR__ . '/../src/SMTP.php';

// reCAPTCHA
$secretKey = '6LfSgiMtAAAAAMopLd7H1pn5J4YDaa2emAb7Yfx5';
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

if (empty($recaptchaResponse)) {
  die('Por favor confirma que no eres un robot.');
}

$verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
$response = file_get_contents($verifyUrl . '?secret=' . urlencode($secretKey) . '&response=' . urlencode($recaptchaResponse));
$responseData = json_decode($response, true);

if (empty($responseData['success'])) {
  die('No se pudo validar el reCAPTCHA.');
}

// Datos formulario
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? 'Nueva solicitud ZentDigital');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
  die('Por favor completa todos los campos obligatorios.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  die('El correo ingresado no es válido.');
}
if ($phone === '') {
  die('Por favor ingresa tu número de contacto.');
}
$mail = new PHPMailer(true);

try {
  $mail->isSMTP();
  $mail->Host = 'smtp.hostinger.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'contacto@zentdigital.cl';
  $mail->Password = 'Zent.digital1';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;

  $mail->CharSet = 'UTF-8';

  $mail->setFrom('contacto@zentdigital.cl', 'Zent Digital');
  $mail->addAddress('contacto@zentdigital.cl', 'Zent Digital');
  $mail->addReplyTo($email, $name);

  $mail->isHTML(true);
  $mail->Subject = 'Nueva solicitud Zent Digital: ' . $subject;

  $mail->Body = "
    <h2>Nueva solicitud desde Zent Digital</h2>
    <p><strong>Nombre:</strong> {$name}</p>
    <p><strong>Teléfono / WhatsApp:</strong> {$phone}</p>
    <p><strong>Email:</strong> {$email}</p>
    <p><strong>Asunto:</strong> {$subject}</p>
    <p><strong>Mensaje:</strong></p>
    <p>" . nl2br(htmlspecialchars($message)) . "</p>
  ";

  $mail->AltBody = "
Nueva solicitud desde Zent Digital

Nombre: $name
Email: $email
Asunto: $subject
Teléfono / WhatsApp: $phone
Mensaje:
$message
  ";

  $mail->send();
  echo 'OK';
  exit;

} catch (Exception $e) {
  echo 'No se pudo enviar el mensaje. Error: ' . $mail->ErrorInfo;
}
?>