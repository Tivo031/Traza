<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecuperacionContrasena extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Host tomado de APP_URL, nunca del encabezado Host de una peticion externa.
        $ruta = route('password.reset', [
            'token' => $this->token, 'correo' => $notifiable->getEmailForPasswordReset(),
        ], false);
        $enlace = rtrim(config('app.url'), '/').$ruta;
        return (new MailMessage)
            ->subject('Restablecer contraseña | Traza')
            ->greeting('Hola, '.$notifiable->nombre.'.')
            ->line('Recibimos una solicitud para restablecer tu contraseña.')
            ->action('Restablecer contraseña', $enlace)
            ->line('Este enlace vence en 60 minutos y solo puede utilizarse una vez.')
            ->line('Si no solicitaste el cambio, puedes ignorar este mensaje.')
            ->salutation('Equipo Traza');
    }
}
