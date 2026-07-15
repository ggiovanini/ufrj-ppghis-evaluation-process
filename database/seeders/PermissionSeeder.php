<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            # ============================================================================
            # Projects
            # Responsável pelo gerenciamento dos projetos submetidos ao processo seletivo.
            # Papel principal: Administrator
            # ============================================================================
            'projects.import', //              # Importar projetos para o sistema.
            'projects.view', //                # Visualizar todos os projetos.
            'projects.manage', //              # Editar informações dos projetos.


            # ============================================================================
            # Review
            # Primeira etapa do processo. Os projetos são distribuídos aos reviewers para
            # avaliação individual e sigilosa.
            # Papéis: Administrator e Reviewer
            # ============================================================================
            'review.assign', //                # Atribuir reviewers aos projetos. (Administrator)
            'projects.distribute', //          # Distribuir projetos aos reviewers. (Administrator)

            'review.evaluate', //              # Preencher a avaliação de um projeto atribuído. (Reviewer)
            'review.submit', //                # Enviar definitivamente a avaliação. (Reviewer)
            'review.update', //                # Alterar uma avaliação antes do envio ou encerramento. (Reviewer)
            'review.view-own', //              # Visualizar apenas as próprias avaliações. (Reviewer)

            'review.results.view', //          # Visualizar pareceres e notas consolidadas das avaliações. (Administrator, Examiner)
            'review.results.calculate', //     # Calcular a média final das avaliações dos reviewers. (Administrator)


            # ============================================================================
            # Written Examination
            # Etapa exclusiva para candidatos ao Mestrado aprovados na avaliação do projeto.
            # Papel principal: Administrator
            # ============================================================================
            'written-exam.record', //          # Registrar ou atualizar a nota da prova escrita.


            # ============================================================================
            # Committee
            # Etapa da banca examinadora, realizada após a conclusão das avaliações e,
            # para o Mestrado, após o registro da prova escrita.
            # Papéis: Administrator e Examiner
            # ============================================================================
            'committee.evaluate', //           # Registrar a avaliação da banca. (Examiner)
            'committee.submit', //             # Enviar definitivamente a avaliação da banca. (Examiner)
            'committee.update', //             # Alterar a avaliação antes do encerramento. (Examiner)

            'committee.results.view', //       # Visualizar notas e resultados da etapa da banca. (Administrator, Examiner)


            # ============================================================================
            # Results
            # Consolidação e publicação do resultado final do processo seletivo.
            # Papel principal: Administrator
            # ============================================================================
            'results.view', //                # Consultar o resultado final dos candidatos.
            'results.publish', //             # Publicar oficialmente o resultado final.


            # ============================================================================
            # Users
            # Administração de usuários, perfis e permissões.
            # Papel principal: Administrator
            # ============================================================================
            'users.manage', //                # Cadastrar, editar, remover usuários e gerenciar seus perfis.
        ]);

        $permissions->each(function ($permission) {
            Permission::firstOrCreate(['name' => $permission]);
        });
    }
}
