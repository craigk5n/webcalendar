<?php

include "class.phpmailer.php";
include "class.smtp.php";

$mail             = new PHPMailer();

$mail->IsSMTP(); // telling the class to use SMTP
$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
                                           // 1 = errors and messages
                                           // 2 = messages only
$mail->SMTPAuth   = true;                  // enable SMTP authentication
$mail->SMTPSecure = "tls";                 
$mail->Host       = "smtp.gmail.com";      // SMTP server
$mail->Port       = 587;                   // SMTP port
$mail->Username   = "craigk5n@gmail.com";  // username
$mail->Password   = "CEK@gm123";            // password

$mail->SetFrom('craigk5n@gmail.com', 'Craig Knudsen');

$mail->Subject    = "I hope this works!";

$mail->MsgHTML('Blah');

$address = "craig@k5n.us";
$mail->AddAddress($address, "Craig Knudsen");

if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;
} else {
  echo "Message sent!";
}

?>
