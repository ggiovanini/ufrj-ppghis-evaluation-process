<?php

namespace App\Domain\Review\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewPdfReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public Review $review) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->review->reviewAssignment->project;

        return (new MailMessage)
            ->subject('Sua avaliação foi enviada')
            ->line('A avaliação do projeto "'.$project->title.'" foi confirmada e o PDF está disponível.')
            ->action('Baixar avaliação em PDF', route('selection.reviews.pdf', [
                'selection' => $project->selection_process_id,
                'review' => $this->review->id,
            ]))
            ->line('Guarde este arquivo para seus registros.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Sua avaliação foi confirmada e o PDF está disponível para download.',
            'review_id' => $this->review->id,
        ];
    }
}
