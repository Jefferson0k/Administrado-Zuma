<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Alias;
use Illuminate\Support\Str;

class AliasSeeder extends Seeder{
    public function run(): void{
        $prohibitedAliases = [
            'admin',
            'administrator',
            'root',
            'system',
            'soporte',
            'support',
            'moderador',
            'moderator',
            'staff',
            'team',
            'usuario',
            'user',
            'guest',
            'invitado',
            'developer',
            'dev',
            'superuser',
            'null',
            'undefined',
            'owner',
            'dueño',
            'master'
        ];
        foreach ($prohibitedAliases as $alias) {
            Alias::create([
                'name' => $alias,
                'slug' => Str::slug($alias),
            ]);
        }
    }
}
