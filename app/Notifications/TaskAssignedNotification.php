<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable, ResolvesNotificationChannels;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Task $task,
        public ?User $assignedBy = null,
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
        $projectName = $this->task->project?->name ?? 'Projet sans nom';
        $assignedBy = $this->assignedBy?->name ?? 'un membre de l equipe';
        $dueDate = $this->task->due_date?->format('d/m/Y H:i') ?? 'Aucune echeance';

        return (new MailMessage)
            ->subject('Nouvelle tache assignee')
            ->greeting('Bonjour,')
            ->line("La tache \"{$this->task->title}\" vous a ete assignee par {$assignedBy}.")
            ->line("Projet : {$projectName}")
            ->line("Echeance : {$dueDate}")
            ->action('Voir dans votre espace', url("/client/projects/{$this->task->project_id}#board"))
            ->line("Lien admin: ".url("/abba/tasks/{$this->task->id}/edit"))
            ->line('Merci de votre collaboration.');
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
            'title' => $this->task->title,
            'type' => 'task_assigned',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMobilePush(object $notifiable): array
    {
        return [
            'title' => 'Nouvelle tache assignee',
            'body' => $this->task->title,
            'data' => [
                'type' => 'task_assigned',
                'task_id' => $this->task->id,
                'project_id' => $this->task->project_id,
                'url' => '/client/projects/'.$this->task->project_id.'#board',
            ],
        ];
    }
}
