<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Core\Events\ListenerInterface;
use Core\Mailer;

class SendWelcomeEmail implements ListenerInterface
{
    protected Mailer $mailer;

    /**
     * @param Mailer $mailer
     */
    public function __construct( Mailer $mailer )
    {
        $this->mailer = $mailer;
    }

    /**
     * @param $event
     */
    public function handle( $event ): void
    {
        if ( $event instanceof UserRegistered ) {
            $user = $event->user;

            // Extract email and name safely
            $email = is_array( $user ) ? $user['email'] : $user->email;
            $name  = is_array( $user ) ? $user['name'] : $user->name;

            $subject  = "Welcome to Dugout Dynasty!";
            $htmlBody = "
                <h1>Welcome, {$name}!</h1>
                <p>Thank you for registering for Dugout Dynasty. Get ready to build your team!</p>
                <p><a href='" . $_ENV['APP_URL'] . "/login'>Click here to login</a></p>
            ";

            // Use the safe send method (which lazy loads the transport)
            try {
                $this->mailer->send( $email, $subject, $htmlBody );
            } catch ( \Exception $e ) {
                // Log error but don't crash the registration process
                error_log( "Failed to send welcome email: " . $e->getMessage() );
            }
        }
    }
}
