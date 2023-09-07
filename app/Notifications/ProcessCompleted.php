<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ProcessCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public $queue = 'notifications';

    /**
     * Create a new notification instance.
     */
    public function __construct(public Project $project, public string $processName, public string $output, public string $script)
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject(env('APP_NAME') . ': ' . $this->processName .  ' - Process completed')
                    ->greeting('Hello ' . $notifiable->first_name . '!')
                    ->line("This is a notification about your project: " . $this->project->name)
                    ->line("The process: '$this->processName' has completed")
                    ->action('View Project', route('open-project', ['project' => $this->project]))
                    ->lineIf($notifiable->is_admin && strlen($this->output), 'Process output:')
                    ->lineIf($notifiable->is_admin && strlen($this->output), new HtmlString('<pre>' . $this->output . '</pre>'))
                    ->lineIf($notifiable->is_admin && strlen($this->script), 'Script executed:')
                    ->lineIf($notifiable->is_admin && strlen($this->script), new HtmlString('<pre>Script executed:' . "\n" . $this->script . '</pre>'))
                    ->line('Thanks for using our application.')
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
            'project' => $this->project->id,
            'process' => $this->processName,
            'output' => $this->output
        ];
    }
}
