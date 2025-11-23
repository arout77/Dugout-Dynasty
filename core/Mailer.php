<?php

namespace Core;

use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class Mailer
{
    protected ?SymfonyMailer $mailer = null;
    protected array $config;

    public function __construct()
    {
        // Load config, but DO NOT create the transport yet.
        $this->config = require dirname( __DIR__ ) . '/config.php';
    }

    /**
     * Lazy loads the Symfony Mailer instance.
     */
    protected function getMailer(): SymfonyMailer
    {
        if ( $this->mailer === null ) {
            $mailConfig = $this->config['mailer'];

            // Construct the DSN dynamically based on available config
            // This prevents "smtp://:@:" errors if fields are empty
            $scheme = $mailConfig['transport'] ?? 'null';

            if ( $scheme === 'null' ) {
                $dsn = 'null://null';
            } else {
                $userPart = '';
                if ( !empty( $mailConfig['username'] ) ) {
                    $userPart = $mailConfig['username'];
                    if ( !empty( $mailConfig['password'] ) ) {
                        $userPart .= ':' . $mailConfig['password'];
                    }
                    $userPart .= '@';
                }

                $host = $mailConfig['host'] ?? 'localhost';
                $port = $mailConfig['port'] ?? 25;

                $dsn = "{$scheme}://{$userPart}{$host}:{$port}";
            }

            try {
                $transport    = Transport::fromDsn( $dsn );
                $this->mailer = new SymfonyMailer( $transport );
            } catch ( \Exception $e ) {
                // If config is still bad, fallback to null transport so app doesn't crash
                // unless we are actively trying to send mail
                $this->mailer = new SymfonyMailer( Transport::fromDsn( 'null://null' ) );
            }
        }

        return $this->mailer;
    }

    /**
     * Sends an email.
     */
    public function send( string $to, string $subject, string $htmlBody, ?string $plainTextBody = null ): void
    {
        $fromAddress = $this->config['mailer']['from_address'];
        $fromName    = $this->config['mailer']['from_name'];

        $email = ( new Email() )
            ->from( "{$fromName} <{$fromAddress}>" )
            ->to( $to )
            ->subject( $subject )
            ->html( $htmlBody );

        if ( $plainTextBody ) {
            $email->text( $plainTextBody );
        }

        // Use the lazy-loaded getter
        $this->getMailer()->send( $email );
    }
}
