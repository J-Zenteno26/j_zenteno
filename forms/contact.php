<?php
  //re CAPTCHA
$secretKey = '6LfSgiMtAAAAAMopLd7H1pn5J4YDaa2emAb7Yfx5';
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

if (empty($recaptchaResponse)) {
  die('Por favor confirma que no eres un robot.');
}

$verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

$response = file_get_contents($verifyUrl . '?secret=' . urlencode($secretKey) . '&response=' . urlencode($recaptchaResponse));
$responseData = json_decode($response, true);

if (!$responseData['success']) {
  die('No se pudo validar el reCAPTCHA.');
}
  $receiving_email_address = 'contacto@zentdigital.cl';

  if( file_exists($php_email_form = '../assets/vendor/php-email-form/php-email-form.php' )) {
    include( $php_email_form );
  } else {
    die( 'Unable to load the "PHP Email Form" Library!');
  }

  $contact = new PHP_Email_Form;
  $contact->ajax = true;
  
  $contact->to = $receiving_email_address;
  $contact->from_name = $_POST['name'];
  $contact->from_email = $_POST['email'];
  $contact->subject = $_POST['subject'];

  // Uncomment below code if you want to use SMTP to send emails. You need to enter your correct SMTP credentials
  /*
  $contact->smtp = array(
    'host' => 'example.com',
    'username' => 'example',
    'password' => 'pass',
    'port' => '587'
  );
  */

  $contact->add_message( $_POST['name'], 'From');
  $contact->add_message( $_POST['email'], 'Email');
  $contact->add_message( $_POST['message'], 'Message', 10);

  echo $contact->send();
?>
