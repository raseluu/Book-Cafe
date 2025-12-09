<?php
class Mail {
    // Mailtrap credentials
    private static $host = 'sandbox.smtp.mailtrap.io';
    private static $port = 2525;
    private static $username = '16386386f2a8c8';
    private static $password = '614e9b493a2118';

    public static function send($to, $subject, $body) {
        $socket = fsockopen(self::$host, self::$port, $errno, $errstr, 15);
        if (!$socket) {
            return false;
        }

        self::read($socket); // banner

        self::cmd($socket, "EHLO " . gethostname());
        self::cmd($socket, "AUTH LOGIN");
        self::cmd($socket, base64_encode(self::$username));
        self::cmd($socket, base64_encode(self::$password));

        self::cmd($socket, "MAIL FROM: <noreply@aesthetebookcafe.com>");
        self::cmd($socket, "RCPT TO: <$to>");
        self::cmd($socket, "DATA");

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Aesthete Book Cafe <noreply@aesthetebookcafe.com>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        fputs($socket, "$headers\r\n$body\r\n.\r\n");
        self::read($socket);

        self::cmd($socket, "QUIT");
        fclose($socket);
        return true;
    }

    private static function cmd($socket, $cmd) {
        fputs($socket, $cmd . "\r\n");
        return self::read($socket);
    }

    private static function read($socket) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        return $response;
    }
}
