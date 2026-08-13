<?php

namespace Database\Seeders;

use App\Domain\Shared\Types\UserRoles;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Giuliano',
            'email' => 'gil@nibilabs.com',
            'password' => bcrypt('N3i0b1i0@'),
        ]);
        $user->assignRole([UserRoles::ADMIN->value]);

        $user = User::factory()->create([
            'name' => 'Marcelo',
            'email' => 'marcelo.torrico@gmail.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole([UserRoles::ADMIN->value]);

        $reviewerLastYear = collect([
            ['André Jobim Martins', 'andrejmartins@gmail.com', [UserRoles::ADMIN->value, UserRoles::REVIEWER->value, UserRoles::MASTER_COMMITTEE->value]],
            ['Andréa Casa Nova Maia', 'andreacn.bh@gmail.com', [UserRoles::REVIEWER->value]],
            ['Andrea Daher', 'andreadaher@gmail.com', [UserRoles::REVIEWER->value]],
            ['Antonio Carlos Jucá de Sampaio', 'acjuca@gmail.com', [UserRoles::REVIEWER->value]],
            ['Beatriz Catão Cruz Santos', 'biacatao@gmail.com', [UserRoles::REVIEWER->value]],
            ['Carlos Ziller Camenietzki', 'carloszillercamenietzki@gmail.com', [UserRoles::REVIEWER->value]],
            ['Claudio Costa Pinheiro', 'c.pinheiro.ufrj@gmail.com', [UserRoles::REVIEWER->value]],
            ['Clarissa Mattos', 'clamfarias@gmail.com', [UserRoles::REVIEWER->value]],
            ['Deivid Valério Gaia', 'dvgaia@hotmail.com', [UserRoles::REVIEWER->value]],
            ['Felipe Charbel Teixeira', 'fcharbel@gmail.com', [UserRoles::REVIEWER->value]],
            ['Fernando Luiz Vale Castro', 'valecastroufrj@gmail.com', [UserRoles::REVIEWER->value]],
            ['Flávio Gomes', 'escravonovo@gmail.com; escravo@prolink.com.br', [UserRoles::REVIEWER->value]],
            ['Gabriel Aladrén', 'gabriel.aladren@gmail.com', [UserRoles::REVIEWER->value]],
            ['Gabriel de Carvalho Godoy Castanho', 'gabriel.castanho@historia.ufrj.br; gabrielcgcastanho@gmail.com', [UserRoles::REVIEWER->value]],
            ['Hanna Sonkajarvi', 'hannasonkajarvi@direito.ufrj.br; hhsonkaj@gmail.com', [UserRoles::REVIEWER->value]],
            ['Henrique Buarque de Gusmão', 'henriquebgusmao@gmail.com', [UserRoles::REVIEWER->value]],
            ['Isabele de Matos Pereira de Mello', 'isabelemello.historiaufrj@gmail.com; isabelemello@gmail.com', [UserRoles::REVIEWER->value]],
            ['Jacqueline Hermann', 'jacquehermann@uol.com.br; jacquehermann18@gmail.com', [UserRoles::REVIEWER->value]],
            ['João Luís Ribeiro Fragoso', 'jl.fragoso@uol.com.br', [UserRoles::REVIEWER->value]],
            ['João Paulo Coelho de Souza Rodrigues', 'jprodrigues@historia.ufrj.br', [UserRoles::REVIEWER->value]],
            ['João Rodolfo Munhoz Ohara', 'oharajrm@pm.me', [UserRoles::REVIEWER->value]],
            ['Jorge Victor de Araújo Souza', 'jvictoraraujos@gmail.com', [UserRoles::REVIEWER->value]],
            ['José Augusto Pádua', 'jpadua@terra.com.br', [UserRoles::REVIEWER->value]],
            ['Lise Sedrez', 'lise@sedrez.com; lsedrez@gmail.com; lise@historia.ufrj.br', [UserRoles::REVIEWER->value]],
            ['Lorena Lopes da Costa', 'lorenalopes85@gmail.com', [UserRoles::REVIEWER->value]],
            ['Luiza Larangeira da Silva Mello', 'luizalarangeira34@gmail.com', [UserRoles::ADMIN->value, UserRoles::REVIEWER->value]],
            ['Marcos Bretas', 'mlbretas@gmail.com', [UserRoles::REVIEWER->value]],
            ['Maria Paula Nascimento Araújo', 'mapaula.nascimento55@gmail.com', [UserRoles::REVIEWER->value]],
            ['Marieta de Moraes Ferreira', 'marieta@fgv.br; marietamoraes48@gmail.com', [UserRoles::REVIEWER->value]],
            ['Marta Mega de Andrade', 'martamega@gmail.com', [UserRoles::REVIEWER->value]],
            ['Michel Gherman', 'michelgherman@gmail.com; michel.gherman@gmail.com', [UserRoles::REVIEWER->value]],
            ['Monica Grin', 'monica.grin@gmail.com', [UserRoles::REVIEWER->value]],
            ['Mônica Lima e Souza', 'monicalimaesouza@gmail.com', [UserRoles::REVIEWER->value]],
            ['Murilo Sebe Bon Meihy', 'meihy1@yahoo.com.br', [UserRoles::REVIEWER->value]],
            ['Nuno de Fragoso Vidal', 'nuno.fragoso@yahoo.com; nunofragosovidal@gmail.com', [UserRoles::REVIEWER->value]],
            ['Paulo Fontes', 'paulofontes@historia.ufrj.br; pfontes@mandic.com.br', [UserRoles::REVIEWER->value]],
            ['Renato Luís do Couto Neto e Lemos', 'renato.lemos@globo.com; renatoluislemos@gmail.com', [UserRoles::REVIEWER->value]],
            ['Roberto Guedes', 'robertoguedesferreira@gmail.com', [UserRoles::REVIEWER->value]],
            ['Silvia Correia', 'sabcorreia@gmail.com', [UserRoles::REVIEWER->value]],
            ['Silvia Regina Liebel', 'liebel.seiziemiste@gmail.com', [UserRoles::REVIEWER->value]],
            ['Vinícius Liebel', 'v.liebel@ufrj.br; v_liebel@yahoo.de', [UserRoles::REVIEWER->value]],
            ['Vitor Izecksohn', 'vizecksohn@gmail.com', [UserRoles::REVIEWER->value]],
            ['William de Souza Martins', 'williamsmartins@uol.com.br; wsmartins2@gmail.com', [UserRoles::REVIEWER->value]],
        ]);

        $reviewerLastYear->each(function ($item) {
            $firstName = explode(' ', $item[0])[0];
            User::factory()->create([
                'name' => $item[0],
                'email' => explode(';', $item[1])[0],
                'password' => bcrypt('ppghis'.Str::snake($firstName)),
            ])->assignRole($item[2]);
        });
    }
}
