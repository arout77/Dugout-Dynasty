<?php

namespace App\Middleware;

use Core\BaseController;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Twig\Environment;

class CustomEmailService extends BaseController
{
    /**
     * @var mixed
     */
    private $fromEmail;

    /**
     * Constructor to set up the Twig environment.
     *
     * @param Environment $twig The configured Twig environment object.
     * @param string $fromEmail The email address that will appear in the "From" header.
     */
    public function __construct( array $config )
    {
        $this->fromEmail = $config['from_email'];
    }

    /**
     * Renders a Twig template and sends the email using PHP's mail() function.
     *
     * @param string $to Recipient's email address.
     * @param string $subject The email subject.
     * @param string $body The email <body> if using HTML email instead of template.
     * @param string $template The path to the Twig template file.
     * @param array $templateData Data to pass to the template.
     * @return bool True on success, false on failure.
     */
    public function send( string $to, string $subject, string $body = '', string $template, array $templateData = [] ): bool
    {
        try {
            // 1. Render the Twig template to get the HTML content
            $message = $this->template->render( $template, $templateData );

            // 2. Set the required headers for an HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: IWF Wrestling <' . $this->fromEmail . '>' . "\r\n";

            // 3. Use the mail() function to send the email
            // The '@' symbol suppresses default PHP errors to allow for custom error handling.
            return @mail( $to, $subject, $message, $headers );

        } catch ( \Exception $e ) {
            // Log the error for debugging purposes
            error_log( "NativeEmailService Error: " . $e->getMessage() );
            return false;
        }
    }
}
