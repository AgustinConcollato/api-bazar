<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;


    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }
    
    public function build()
    {
        return $this->subject('Restablecer contraseña')
            ->with([
                'token' => $this->token,
                'email' => $this->email,
            ])
            ->view('emails.client_reset_password');
    }

}