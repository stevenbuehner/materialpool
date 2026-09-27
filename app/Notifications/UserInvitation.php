<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification {
	use Queueable;

	public function __construct(private readonly string $token) {
	}

	public function via(object $notifiable): array {
		return ['mail'];
	}

	public function toMail(object $notifiable): MailMessage {
		$url = route('password.reset', [
			'token' => $this->token,
			'email' => $notifiable->getEmailForPasswordReset(),
		]);

		return (new MailMessage)
			->subject(__('admin.invitation_subject'))
			->greeting(__('admin.invitation_greeting', ['name' => $notifiable->name]))
			->line(__('admin.invitation_text'))
			->action(__('admin.invitation_action'), $url)
			->line(__('admin.invitation_expiry', ['minutes' => config('auth.passwords.users.expire', 60)]));
	}
}
