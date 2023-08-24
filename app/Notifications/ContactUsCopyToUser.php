<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class ContactUsCopyToUser extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $subject, public string $description, public string $email, public string $first_name, public string $last_name)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        //message for the team
        return (new MailMessage)
                    ->subject(env('APP_NAME') . ' - We got your message')
                    ->greeting('Hello ' . $this->first_name . '!')
                    ->line("We received the following message sent by you:")
                    ->line(new HtmlString('<strong>Name: </strong>' . $this->first_name . ' ' . $this->last_name))
                    ->line(new HtmlString('<strong>Subject: </strong>' . $this->subject))
                    ->line(new HtmlString('<strong>Description: </strong><pre>' . $this->description . '</pre>'))
                    ->line(new HtmlString('<strong>User email address: </strong>' . $this->email))
                    ->line('Thanks for your feedback, please wait to hear back from us.')
                    ->salutation(new HtmlString('Regards, <br />The ' . env('APP_NAME') . ' team!'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'description' => $this->description,
            'email' => $this->email
        ];
    }
}
