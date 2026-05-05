<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Notifications\Channels\MobilePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskStatusUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Task $task,
        public string $oldStatus,
        public string $newStatus,
        public ?User $changedBy = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast', MobilePushChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $changedBy = $this->changedBy?->name ?? 'un membre de l equipe';
        $statusLabels = Task::statusOptions();

        $from = $statusLabels[$this->oldStatus] ?? ucfirst(str_replace('_', ' ', $this->oldStatus));
        $to = $statusLabels[$this->newStatus] ?? ucfirst(str_replace('_', ' ', $this->newStatus));

        return (new MailMessage)
            ->subject('Mise a jour de statut')
            ->greeting('Bonjour,')
            ->line("Le statut de la tache \"{$this->task->title}\" a ete modifie par {$changedBy}.")
            ->line("Ancien statut : {$from}")
            ->line("Nouveau statut : {$to}")
            ->action('Voir dans l espace client', url("/client/projects/{$this->task->project_id}#board"))
            ->line("Lien admin: ".url("/abba/tasks/{$this->task->id}/edit"))
            ->line('Merci de suivre les avancements.');
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'type' => 'task_status_changed',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMobilePush(object $notifiable): array
    {
        return [
            'title' => 'Statut de tache modifie',
            'body' => $this->task->title,
            'data' => [
                'type' => 'task_status_changed',
                'task_id' => $this->task->id,
                'project_id' => $this->task->project_id,
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
                'url' => '/client/projects/'.$this->task->project_id.'#board',
            ],
        ];
    }
}
