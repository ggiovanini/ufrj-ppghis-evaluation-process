<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewAssignmentReassignedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Project $project,
        public bool $wasRemoved,
    ) {}

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
        return (new MailMessage)
            ->subject($this->wasRemoved ? 'Atribuição de avaliação removida' : 'Nova atribuição de avaliação')
            ->line($this->wasRemoved
                ? 'A atribuição do projeto abaixo foi removida da sua lista de avaliações.'
                : 'Você recebeu uma nova atribuição para avaliar o projeto abaixo.')
            ->line("{$this->project->title} (Candidato: {$this->project->candidate_name})")
            ->action('Acessar Plataforma', url('/'))
            ->line('Obrigado por sua colaboração!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->wasRemoved
                ? 'Uma atribuição de avaliação foi removida da sua lista.'
                : 'Você recebeu uma nova atribuição de avaliação.',
            'project_id' => $this->project->id,
            'was_removed' => $this->wasRemoved,
        ];
    }
}
