<?php

namespace Tests\Feature;

use App\Models\Reply;
use App\Models\User;
use App\Policies\ReplyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_reply(): void
    {
        $owner = User::factory()->create();
        $reply = Reply::factory()->for($owner)->create();

        $this->assertTrue((new ReplyPolicy)->delete($owner, $reply));
    }

    public function test_non_owner_cannot_delete_reply(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $reply = Reply::factory()->for($owner)->create();

        $this->assertFalse((new ReplyPolicy)->delete($otherUser, $reply));
    }
}
