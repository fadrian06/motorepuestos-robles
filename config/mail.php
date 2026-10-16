<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

return static function (): PHPMailer {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USERNAME'];
    $mail->Password = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int) $_ENV['MAIL_PORT'];
    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->isHTML(true);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    return $mail;
};
