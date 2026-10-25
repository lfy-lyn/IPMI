<?php

namespace Tests\Concerns;

use App\Models\Division;
use App\Models\Member;
use App\Models\Position;
use App\Models\User;

/**
 * Helper pembuat pengguna dengan jabatan tertentu untuk test.
 */
trait CreatesIpmiUsers
{
    protected function makeUser(?string $position = null, ?Division $division = null, array $memberAttrs = []): User
    {
        $division ??= Division::factory()->create();
        $user = User::factory()->create(['status' => 'active']);
        $member = Member::factory()->create(array_merge([
            'user_id' => $user->id,
            'division_id' => $division->id,
            'status' => 'active',
        ], $memberAttrs));

        if ($position) {
            $member->positions()->attach(Position::firstOrCreate(['name' => $position])->id);
        }

        return $user->fresh();
    }
}
