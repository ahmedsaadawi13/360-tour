<?php
/**
 * Mailer Service
 *
 * Minimal mail abstraction. Drivers:
 *  - log:  append messages to storage/logs/mail.log (development)
 *  - mail: PHP mail() (configure sendmail/SMTP relay on the server)
 */

class Mailer
{
    /**
     * Send a plain-text email. Returns true on success.
     */
    public static function send($to, $subject, $body)
    {
        if (!is_valid_email($to)) {
            return false;
        }

        // Prevent header injection through subject lines
        $subject = str_replace(["\r", "\n"], ' ', $subject);
        $fromAddress = config('mail.from_address');
        $fromName = str_replace(["\r", "\n", '"'], '', config('mail.from_name'));

        if (config('mail.driver') === 'mail') {
            $headers = [
                'From: "' . $fromName . '" <' . $fromAddress . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
            ];
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $sent = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
            if (!$sent) {
                error_log("Mailer: failed to send '{$subject}' to {$to}");
            }
            return $sent;
        }

        $entry = sprintf(
            "==== %s ====\nTo: %s\nFrom: %s <%s>\nSubject: %s\n\n%s\n\n",
            date('Y-m-d H:i:s'), $to, $fromName, $fromAddress, $subject, $body
        );
        return file_put_contents(BASE_PATH . '/storage/logs/mail.log', $entry, FILE_APPEND | LOCK_EX) !== false;
    }
}
