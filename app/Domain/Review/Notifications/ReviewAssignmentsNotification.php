<?php

namespace App\Domain\Review\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ReviewAssignmentsNotification extends Notification
{
    use Queueable;

    /**
     * @param  Collection<Project>  $projects
     */
    public function __construct(public Collection $projects) {}

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
        $mailMessage = (new MailMessage)
            ->subject('Nova etapa de avaliação iniciada')
            ->line('A etapa de avaliação do processo seletivo começou.')
            ->line('Você foi atribuído para avaliar os seguintes projetos:')
            ->line('');

        foreach ($this->projects as $project) {
            $mailMessage->line("- {$project->title} (Candidato: {$project->candidate_name})");
        }

        return $mailMessage
            ->line('')
            ->line('Você já pode acessar a plataforma para realizar as avaliações.')
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
            'message' => 'Você tem '.$this->projects->count().' novos projetos para avaliar.',
            'project_ids' => $this->projects->pluck('id')->toArray(),
        ];
    }
}
