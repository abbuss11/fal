<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskUpdatedNotification extends Notification
{
    use Queueable, ResolvesNotificationChannels;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Task $task,
        public array $changes,
        public ?User $updatedBy = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->resolveChannels($notifiable);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actor = $this->updatedBy?->name ?? 'un membre de l equipe';
        $changesSummary = collect($this->changes)
            ->map(function (array $change, string $field): string {
                $from = $change['from'] ?? '-';
                $to = $change['to'] ?? '-';

                return "{$field}: {$from} -> {$to}";
            })
            ->take(6)
            ->implode(', ');

        return (new MailMessage)
            ->subject('Mise a jour de tache')
            ->greeting('Bonjour,')
            ->line("La tache \"{$this->task->title}\" a ete modifiee par {$actor}.")
            ->line('Changements: '.$changesSummary)
            ->action('Voir dans le portail client', url("/client/projects/{$this->task->project_id}#board"))
            ->line('Vous recevez cet email pour rester synchronise sur le projet.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'changes' => $this->changes,
            'type' => 'task_updated',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMobilePush(object $notifiable): array
    {
        return [
            'title' => 'Tache mise a jour',
            'body' => $this->task->title,
            'data' => [
                'type' => 'task_updated',
                'task_id' => $this->task->id,
                'project_id' => $this->task->project_id,
                'url' => '/client/projects/'.$this->task->project_id.'#board',
            ],
        ];
    }
}
