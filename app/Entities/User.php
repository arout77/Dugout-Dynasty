<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table( name: 'users' )]
class User
{
    #[ORM\Id]
    #[ORM\Column( type: 'string', length: 255 )]
    private string $user_id;

    #[ORM\Column( type: 'string', length: 50 )]
    private string $name;

    #[ORM\Column( type: 'string', length: 100, unique: true )]
    private string $email;

    #[ORM\Column( type: 'string', length: 255 )]
    private string $password;

    #[ORM\Column( type: 'string', length: 255, nullable: true )]
    private ?string $prospect_id;

    #[ORM\Column( type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'] )]
    private \DateTime $created_at;

    public function __construct()
    {
        $this->user_id    = bin2hex( random_bytes( 16 ) );
        $this->created_at = new \DateTime();
    }

    // --- Getters and Setters ---

    /**
     * @return mixed
     */
    public function getUserId(): string
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName( string $name ): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail( string $email ): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword( string $password ): void
    {
        $this->password = password_hash( $password, PASSWORD_BCRYPT );
    }

    /**
     * @return mixed
     */
    public function getProspectId(): ?string
    {
        return $this->prospect_id;
    }

    /**
     * @param string $prospect_id
     */
    public function setProspectId( ?string $prospect_id ): void
    {
        $this->prospect_id = $prospect_id;
    }
}
