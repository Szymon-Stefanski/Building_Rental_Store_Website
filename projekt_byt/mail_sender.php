<?php
$to = 's22043@pjwstk.edu.pl';
$subject = 'Testowy e-mail';
$body = 'To jest testowy e-mail.';
$headers = 'From: budexgdansk@gmail.com' . "\r\n" .
    'Reply-To: budexgdansk@gmail.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $body, $headers)) {
    echo 'E-mail wysłany!';
} else {
    echo 'E-mail nie został wysłany!';
}
?>
