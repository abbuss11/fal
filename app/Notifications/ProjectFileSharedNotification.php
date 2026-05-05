<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Notifications\Channels\MobilePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectFileSharedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Project $project,
        public readonly ProjectFile $file,
        public readonly ?User $uploadedBy = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast', MobilePushChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actor = $this->uploadedBy?->name ?? 'Un membre de l equipe';

        return (new MailMessage)
            ->subject('Nouveau fichier partage')
            ->greeting('Bonjour,')
            ->line("{$actor} a partage un fichier dans {$this->project->name}.")
            ->line('Fichier: '.$this->file->original_name.' (v'.$this->file->version.')')
            ->action('Ouvrir le projet', url('/client/projects/'.$this->project->id.'#files'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_file_shared',
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'file_id' => $this->file->id,
            'file_name' => $this->file->original_name,
            'version' => $this->file->version,
            'uploaded_by' => $this->uploadedBy?->name,
            'url' => url('/client/projects/'.$this->project->id.'#files'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMobilePush(object $notifiable): array
    {
        return [
            'title' => 'Fichier partage',
            'body' => $this->file->original_name.' (v'.$this->file->version.')',
            'data' => [
                'type' => 'project_file_shared',
                'project_id' => $this->project->id,
                'url' => '/client/projects/'.$this->project->id.'#files',
            ],
        ];
    }
}

