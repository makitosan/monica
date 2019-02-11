<?php

namespace Tests\Unit\Services\Account\Activity;

use Tests\TestCase;
use App\Models\Account\Activity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Account\Activity\Activity\DestroyActivity;

class DestroyActivityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_activity()
    {
        $activity = factory(Activity::class)->create([]);

        $request = [
            'account_id' => $activity->account_id,
            'activity_id' => $activity->id,
        ];

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
        ]);

        (new DestroyActivity)->execute($request);

        $this->assertDatabaseMissing('activities', [
            'id' => $activity->id,
        ]);
    }
}