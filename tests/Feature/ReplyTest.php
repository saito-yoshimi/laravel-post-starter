<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Reply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_show_page_displays_post_and_its_replies(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $reply = Reply::factory()->for($post)->create();

        $response = $this->actingAs($user)->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSee($post->title);
        $response->assertSee($reply->content);
    }

    public function test_user_can_create_reply_with_valid_content(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->post(route('replies.store', $post), [
            'content' => 'とても良い投稿ですね。',
        ]);

        $response->assertRedirect(route('posts.show', $post));
        $this->assertDatabaseHas('replies', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'とても良い投稿ですね。',
        ]);
    }

    public function test_reply_creation_fails_when_content_is_empty(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->post(route('replies.store', $post), [
            'content' => '',
        ]);

        $response->assertSessionHasErrors('content');
        $this->assertDatabaseCount('replies', 0);
    }

    public function test_reply_creation_fails_when_content_exceeds_140_characters(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->post(route('replies.store', $post), [
            'content' => str_repeat('あ', 141),
        ]);

        $response->assertSessionHasErrors('content');
        $this->assertDatabaseCount('replies', 0);
    }

    public function test_reply_owner_can_delete_their_own_reply(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $reply = Reply::factory()->for($post)->for($user)->create();

        $response = $this->actingAs($user)->delete(route('replies.destroy', [$post, $reply]));

        $response->assertRedirect(route('posts.show', $post));
        $this->assertDatabaseMissing('replies', ['id' => $reply->id]);
    }

    public function test_non_owner_cannot_delete_another_users_reply(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();
        $reply = Reply::factory()->for($post)->for($owner)->create();

        $response = $this->actingAs($otherUser)->delete(route('replies.destroy', [$post, $reply]));

        $response->assertForbidden();
        $this->assertDatabaseHas('replies', ['id' => $reply->id]);
    }

    public function test_guest_is_redirected_to_login_when_viewing_post_show(): void
    {
        $post = Post::factory()->create();

        $response = $this->get(route('posts.show', $post));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_creating_reply(): void
    {
        $post = Post::factory()->create();

        $response = $this->post(route('replies.store', $post), [
            'content' => 'ゲストからのリプライ',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('replies', 0);
    }

    public function test_guest_is_redirected_to_login_when_deleting_reply(): void
    {
        $post = Post::factory()->create();
        $reply = Reply::factory()->for($post)->create();

        $response = $this->delete(route('replies.destroy', [$post, $reply]));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('replies', ['id' => $reply->id]);
    }

    public function test_deleting_reply_that_does_not_belong_to_post_returns_404(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $otherPost = Post::factory()->create();
        $reply = Reply::factory()->for($otherPost)->for($user)->create();

        $response = $this->actingAs($user)->delete(route('replies.destroy', [$post, $reply]));

        $response->assertNotFound();
        $this->assertDatabaseHas('replies', ['id' => $reply->id]);
    }

    public function test_deleting_post_also_deletes_its_replies(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();
        $reply = Reply::factory()->for($post)->create();

        $post->delete();

        $this->assertDatabaseMissing('replies', ['id' => $reply->id]);
    }
}
