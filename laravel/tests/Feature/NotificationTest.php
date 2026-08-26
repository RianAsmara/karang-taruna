<?php

namespace Tests\Feature;

use App\Models\FinancialReport;
use App\Models\User;
use App\Notifications\FinancialReportPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_see_their_own_notifications()
    {
        $user = User::factory()->create();
        $user->notify(new FinancialReportPublished(FinancialReport::factory()->create()));

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('notifications', 1));
    }

    public function test_a_user_can_mark_their_own_notification_as_read()
    {
        $user = User::factory()->create();
        $user->notify(new FinancialReportPublished(FinancialReport::factory()->create()));
        $notification = $user->notifications()->first();

        $this->actingAs($user)
            ->post("/notifications/{$notification->id}/read")
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $other->notify(new FinancialReportPublished(FinancialReport::factory()->create()));
        $notification = $other->notifications()->first();

        $this->actingAs($user)
            ->post("/notifications/{$notification->id}/read")
            ->assertNotFound();
    }
}
