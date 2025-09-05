<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientAccountChange extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $id;
    public $type;
    public $name;
    public $reason;

    public function __construct(array $data)
    {
        $this->email = $data['email'];
        $this->name = $data['name'];
        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->reason = $data['reason'];
    }

    public function build()
    {
        return $this->replyTo($this->email, $this->name)
            ->subject('Solicitud para el cambio de cuenta')
            ->with([
                'name' => $this->name,
                'id' => $this->id,
                'type' => $this->type,
                'email' => $this->email,
            ])
            ->view('emails.client_account_change');
    }

}