<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantMember> */
class TenantMemberFactory extends Factory
{
    public function definition(): array
    {
        return ['idtenant' => Tenant::factory(), 'iduser' => User::factory()->state(['role' => 'owner']), 'role' => 'kasir', 'accepted_at' => now()];
    }
}
