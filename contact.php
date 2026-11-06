<?php
// require ReCaptcha class
require('recaptcha-master/src/autoload.php');

// configure
$contactConfigFile = __DIR__ . '/config/contact.php';
$contactConfig = is_file($contactConfigFile) ? require $contactConfigFile : [];
$from = isset($contactConfig['from']) ? $contactConfig['from'] : 'Poruka sa sajta:';
$sendTo = isset($contactConfig['send_to']) ? $contactConfig['send_to'] : '';
$subject = isset($contactConfig['subject']) ? $contactConfig['subject'] : 'Nova poruka sa kontakt forme';
$fields = array('name' => 'Ime', 'phone' => 'telefon', 'email' => 'Email', 'message' => 'Poruka'); // array variable name => Text to appear in the email
$okMessage = 'Poruka je uspešno prosleđena. Hvala Vam, kontaktiraćemo vas uskoro.';
$errorMessage = 'Došlo je do greške prilikom slanja poruke. Molimo vas pokušajte ponovo.';
$recaptchaSecret = isset($contactConfig['recaptcha_secret']) ? $contactConfig['recaptcha_secret'] : '';

// let's do the sending

try
{
    if (!empty($_POST)) {

        // validate the ReCaptcha, if something is wrong, we throw an Exception, 
        // i.e. code stops executing and goes to catch() block
        
        if (!isset($_POST['g-recaptcha-response'])) {
            throw new \Exception('ReCaptcha is not set.');
        }

        // do not forget to enter your secret key in the config above 
        // from https://www.google.com/recaptcha/admin
        
        $recaptcha = new \ReCaptcha\ReCaptcha($recaptchaSecret, new \ReCaptcha\RequestMethod\CurlPost());
        
        // we validate the ReCaptcha field together with the user's IP address
        
        $response = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);


        if (!$response->isSuccess()) {
            throw new \Exception('ReCaptcha was not validated.');
        }
        
        // everything went well, we can compose the message, as usually
        
        $emailText = "Nova Poruka sa kontakt forme\n=============================\n";

        $i=0;
        foreach ($_POST as $key => $value) {
            
            $i++;
            if (isset($fields[$key])) {
                $emailText .= "$fields[$key]: $value\n";
                
                if($i == 2){
                    $mail = $value;
                }
            }
        }
        

        $headers = array('Content-Type: text/plain; charset="UTF-8";',
            'From: ' . $mail,
            'Reply-To: ' . $from,
            'Return-Path: ' . $from,
        );

        mail($sendTo, $subject, $emailText, implode("\n", $headers));

        $responseArray = array('type' => 'success', 'message' => $okMessage);
    }
}
catch (\Exception $e)
{
    $responseArray = array('type' => 'danger', 'message' => $errorMessage);
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $encoded = json_encode($responseArray);

    header('Content-Type: application/json');

    echo $encoded;
}
else {
    echo $responseArray['message'];
}
