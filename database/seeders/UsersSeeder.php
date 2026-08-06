<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('admin');

        $reviewerLastYear = collect([
            ['André Jobim Martins', 'andrejmartins@gmail.com'],
            ['Andréa Casa Nova Maia', 'andreacn.bh@gmail.com'],
            ['Andrea Daher', 'andreadaher@gmail.com'],
            ['Antonio Carlos Jucá de Sampaio', 'acjuca@gmail.com'],
            ['Beatriz Catão Cruz Santos', 'biacatao@gmail.com'],
            ['Carlos Ziller Camenietzki', 'carloszillercamenietzki@gmail.com'],
            ['Claudio Costa Pinheiro', 'c.pinheiro.ufrj@gmail.com'],
            ['Clarissa Mattos', 'clamfarias@gmail.com'],
            ['Deivid Valério Gaia', 'dvgaia@hotmail.com'],
            ['Felipe Charbel Teixeira', 'fcharbel@gmail.com'],
            ['Fernando Luiz Vale Castro', 'valecastroufrj@gmail.com'],
            ['Flávio Gomes', 'escravonovo@gmail.com; escravo@prolink.com.br'],
            ['Gabriel Aladrén', 'gabriel.aladren@gmail.com'],
            ['Gabriel de Carvalho Godoy Castanho', 'gabriel.castanho@historia.ufrj.br; gabrielcgcastanho@gmail.com'],
            ['Hanna Sonkajarvi', 'hannasonkajarvi@direito.ufrj.br; hhsonkaj@gmail.com'],
            ['Henrique Buarque de Gusmão', 'henriquebgusmao@gmail.com'],
            ['Isabele de Matos Pereira de Mello', 'isabelemello.historiaufrj@gmail.com; isabelemello@gmail.com'],
            ['Jacqueline Hermann', 'jacquehermann@uol.com.br; jacquehermann18@gmail.com'],
            ['João Luís Ribeiro Fragoso', 'jl.fragoso@uol.com.br'],
            ['João Paulo Coelho de Souza Rodrigues', 'jprodrigues@historia.ufrj.br'],
            ['João Rodolfo Munhoz Ohara', 'oharajrm@pm.me'],
            ['Jorge Victor de Araújo Souza', 'jvictoraraujos@gmail.com'],
            ['José Augusto Pádua', 'jpadua@terra.com.br'],
            ['Lise Sedrez', 'lise@sedrez.com; lsedrez@gmail.com; lise@historia.ufrj.br'],
            ['Lorena Lopes da Costa', 'lorenalopes85@gmail.com'],
            ['Luiza Larangeira da Silva Mello', 'luizalarangeira34@gmail.com'],
            ['Marcos Bretas', 'mlbretas@gmail.com'],
            ['Maria Paula Nascimento Araújo', 'mapaula.nascimento55@gmail.com'],
            ['Marieta de Moraes Ferreira', 'marieta@fgv.br; marietamoraes48@gmail.com'],
            ['Marta Mega de Andrade', 'martamega@gmail.com'],
            ['Michel Gherman', 'michelgherman@gmail.com; michel.gherman@gmail.com'],
            ['Monica Grin', 'monica.grin@gmail.com'],
            ['Mônica Lima e Souza', 'monicalimaesouza@gmail.com'],
            ['Murilo Sebe Bon Meihy', 'meihy1@yahoo.com.br'],
            ['Nuno de Fragoso Vidal', 'nuno.fragoso@yahoo.com; nunofragosovidal@gmail.com'],
            ['Paulo Fontes', 'paulofontes@historia.ufrj.br; pfontes@mandic.com.br'],
            ['Renato Luís do Couto Neto e Lemos', 'renato.lemos@globo.com; renatoluislemos@gmail.com'],
            ['Roberto Guedes', 'robertoguedesferreira@gmail.com'],
            ['Silvia Correia', 'sabcorreia@gmail.com'],
            ['Silvia Regina Liebel', 'liebel.seiziemiste@gmail.com'],
            ['Vinícius Liebel', 'v.liebel@ufrj.br; v_liebel@yahoo.de'],
            ['Vitor Izecksohn', 'vizecksohn@gmail.com'],
            ['William de Souza Martins', 'williamsmartins@uol.com.br; wsmartins2@gmail.com'],
        ]);
        $reviewerLastYear->each(fn ($item) => User::factory()->create([
            'name' => $item[0],
            'email' => explode(';', $item[1])[0],
        ])->assignRole('reviewer'));
        //        $reviewers = User::factory(30)->create();
        //        $reviewers->each(function ($reviewer) {
        //            $reviewer->assignRole('reviewer');
        //        });

        $masterCommittee = User::factory()->create([
            'name' => 'Master Committee',
            'email' => 'master@example.com',
            'password' => bcrypt('password'),
        ]);
        $masterCommittee->assignRole('master_committee');

        $doctorateCommittee = User::factory()->create([
            'name' => 'Doctorate Committee',
            'email' => 'doctorate@example.com',
            'password' => bcrypt('password'),
        ]);
        $doctorateCommittee->assignRole('doctorate_committee');
    }
}
