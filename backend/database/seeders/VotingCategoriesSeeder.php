<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Enums\VoteCategoryType;
use App\Models\VotingCategory;
use Illuminate\Database\Seeder;

class VotingCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['craque',        'Craque da Pelada', 'Melhor jogador do dia (MVP)',      VoteCategoryType::POSITIVE,  1,  3],
            ['goleador',      'Goleador',         'Quem fez mais gols',               VoteCategoryType::POSITIVE,  1,  3],
            ['garcom',        'Garçom',           'Quem mais deu assistências',       VoteCategoryType::POSITIVE,  1,  3],
            ['muralha',       'Muralha',          'Melhor defensor',                  VoteCategoryType::POSITIVE,  1,  3],
            ['paredao',       'Paredão',          'Melhor goleiro',                   VoteCategoryType::POSITIVE,  1,  3],
            ['muleke',        'Muleke da Pelada', 'Revelação/destaque jovem',         VoteCategoryType::POSITIVE,  1,  3],
            ['pior',          'Pior da Pelada',   'Pior desempenho do dia',           VoteCategoryType::HUMOR,    -1,  0],
            ['pernas_de_pau', 'Pernas de Pau',    'Quem mais errou passes/chutes',    VoteCategoryType::HUMOR,    -1,  0],
        ];

        foreach ($categories as [$code, $name, $description, $type, $points, $bonus]) {
            VotingCategory::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'type' => $type,
                    'points_per_vote' => $points,
                    'bonus_for_winner' => $bonus,
                ],
            );
        }
    }
}
