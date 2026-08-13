<?php

namespace Database\Seeders;

use App\Models\ReviewForm;
use App\Models\SelectionProcess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SelectionProcessSeeder extends Seeder
{
    public function run(): void
    {
        SelectionProcess::factory()->create([
            'name' => 'PSMD',
            'description' => 'Processo de Seleção de Projetos (Mestrado e Doutorado)',
            'year' => 2026,
            'review_form_id' => ReviewForm::first()->id,
        ]);

        $disk = Storage::disk('local');
        $filename = 'MESTRADO-DOUTORADO-PPGHIS2027-ID147925.zip';

        $inboxPath = "inbox/{$filename}";
        $outboxPath = "outbox/{$filename}";
        $reviewsPath = 'reviews';

        if ($disk->exists($outboxPath)) {
            $disk->move($outboxPath, $inboxPath);
        }

        if ($disk->exists($reviewsPath)) {
            $disk->deleteDirectory($reviewsPath);
        }
    }
}
