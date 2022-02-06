<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// подключаем phpmailer
require_once('phpmailer/src/Exception.php');
require_once('phpmailer/src/PHPMailer.php');
require_once('phpmailer/src/SMTP.php');

// подключаем файл настроек
require 'settings.php';

// получаем данные формы
$site_name = filter_var($_POST['site_name'], FILTER_SANITIZE_STRING);
$mail_subject = filter_var($_POST['mail_subject'], FILTER_SANITIZE_STRING);
$page = filter_var($_POST['page'], FILTER_SANITIZE_STRING);
$user_email = filter_var($_POST['user_email'], FILTER_SANITIZE_STRING);

// получаем остальные данные, формируем сообщение администратору
$line = true;

foreach ( $_POST as $key => $value ) {

    $key = filter_var($key, FILTER_SANITIZE_STRING);
    $value = filter_var($value, FILTER_SANITIZE_STRING);

    if ( $value != "" && $key != "site_name" && $key != "mail_subject" && $key != "page" ) {
        $message .= "
        " . ( ($line = !$line) ? '<tr>':'<tr style="background-color: #f8f8f8;">' ) . "
            <td style='padding: 10px; border: #e9e9e9 1px solid;'><b>$key</b></td>
            <td style='padding: 10px; border: #e9e9e9 1px solid;'>$value</td>
        </tr>
        ";
    }
}

// формируем сообщение администратору
$message = "
    <h3>" . $site_name . "</h3>
    <h2>" . $page . "</h2>
    <table style='width: 100%;'>
        " . $message . "
    </table>
    ";

// пoдгoтoвим мaссив oтвeтa
$json = array();
$json['result'] = 'success';
$json['error'] = '';

// проверка email
// if(!$user_email == '') {
//     if( filter_var($user_email, FILTER_VALIDATE_EMAIL) === false){
//         $json['result'] = 'error';
//         $json['error'] = 'Формат email неправильный';
//     }
// }

// проверяем файлы
$totalFileSize = 0;

if (isset($_FILES['attachment'])) {

    $countFiles = count($_FILES['attachment']['tmp_name']);
    
	if($countFiles <= COUNT_FILES){
	
        for ($i = 0; $i < $countFiles; $i++) {
        
            $fileName = $_FILES['attachment']['name'][$i];
            $fileExtension = mb_strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $fileSize = $_FILES['attachment']['size'][$i];
            
            // проверяем расширение загруженного файла
            if (!in_array($fileExtension, $allowedExtensions)) {
                $json['result'] = 'error';
                $json['error'] .= 'Тип файла "' . $fileName . '" не соответствует разрешенному';
            }
            // проверяем размер файлов
            $totalFileSize = $totalFileSize + $fileSize;
            if ($totalFileSize > MAX_FILES_SIZE) {
                $json['result'] = 'error';
                $json['error'] = 'Размер файлов  превышает ' . round(MAX_FILES_SIZE/1024/1024, 2) .  ' Мбайт. ' . 'Общий размер файлов: ' . round($totalFileSize/1024/1024, 2) . ' Мбайт';
            }
        }
        } else {
            $json['result'] = 'error';
            $json['error'] = 'Общее колличество файлов больше ' . COUNT_FILES;
        }
         
}

// отправляем письмо администратору
if ($json['result'] == 'success') {

    // new PHPMailer
    $mail = new PHPMailer(true);

    try {

        // SMTP
        if (SMTP_ON) {
            //$mail->SMTPDebug = 2;
            $mail->isSMTP();
            $mail->Host = SMTP_SERVER;
            $mail->SMTPAuth = true;
            $mail->Username = USER_NAME;
            $mail->Password = PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
        }

        // получатель
        $mail->CharSet = 'utf-8';
        $mail->setFrom(MAIL_FROM, $site_name);
        $mail->addAddress(MAIL_ADDRESS);
        if (!empty(MAIL_ADDRESS_TWO)) {
            $mail->addAddress(MAIL_ADDRESS_TWO);
        }

        // прикрепление файлов к письму
        if (isset($_FILES['attachment'])) {
            for ($ct = 0; $ct < count($_FILES['attachment']['tmp_name']); $ct++) {
                    if($_FILES['attachment']['error'][$ct] == 0){ 
                        $mail->AddAttachment($_FILES['attachment']['tmp_name'][$ct], $_FILES['attachment']['name'][$ct]); 
                    } else {
                        $json['result'] = 'error';
                        $json['error'] = 'Ошибка загрузки файлов';
                    }
            }
        }

        // контент 
        $mail->isHTML(true);
        $mail->Subject = $mail_subject;
        $mail->Body    = $message;
        $mail->AltBody = 'Откройте это письмо в браузере';

        // отпрака
        $mail->send();
        //echo 'Сообщение отправлено';
    } catch (Exception $e) {
        //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        $json['result'] = 'error';
        $json['error'] = 'Ошибка отправки' . $mail->ErrorInfo;
    }

    $mail->clearAddresses();
    $mail->clearAttachments();
}

// отправляем письмо посетителю
if ($json['result'] == 'success' && !$user_email=='' && MAIL_CLIENT) {

    // new PHPMailer
    $mail_client = new PHPMailer(true);

    try {
        
        // SMTP
        if (SMTP_ON) {
            //$mail_client->SMTPDebug = 2;
            $mail_client->isSMTP();
            $mail_client->Host = SMTP_SERVER;
            $mail_client->SMTPAuth = true;
            $mail_client->Username = USER_NAME;
            $mail_client->Password = PASSWORD;
            $mail_client->SMTPSecure = SMTP_SECURE;
            $mail_client->Port = SMTP_PORT;
        }

        // получатель
        $mail_client->CharSet = 'utf-8';
        $mail_client->setFrom(MAIL_FROM_CLIENT, $site_name);
        $mail_client->addAddress($user_email);

        // контент 
        $mail_client->isHTML(true);
        $mail_client->Subject = SUBJECT_MAIL_CLIENT;
        $messageClient = file_get_contents('email_client.tpl');
        $mail_client->Body    = $messageClient;
        $mail_client->AltBody = 'Откройте это письмо в браузере';

        // отправка
        $mail_client->send();
        //echo 'Сообщение отправлено';
    } catch (Exception $e) {
        //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        //$json['result'] = 'error';
        //$json['error'] = 'Ошибка отправки' . $mail_client->ErrorInfo;
    }

    $mail_client->clearAddresses();
}

// отправляем ответ json
echo json_encode($json);