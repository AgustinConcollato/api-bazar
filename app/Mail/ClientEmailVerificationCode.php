<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientEmailVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $name;

    public function __construct(array $data)
    {
        $this->code = $data['code'];
        $this->name = $data['name'];
    }

    public function build()
    {
        return $this->subject('Tu código de verificación')
            ->with([
                'code' => $this->code,
                'name' => $this->name,
            ])
            ->view('emails.client_verification_code');
    }

}