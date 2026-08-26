<?php

namespace Tests\Feature\Api;

use App\Models\FinancialReport;
use App\Models\User;
use App\Notifications\FinancialReportPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_list_their_own_notifications_via_the_api()
    {
        $user = User::factory()->create();
        $user->notify(new FinancialReportPublished(FinancialReport::factory()->create()));

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_user_can_mark_their_own_notification_as_read_via_the_api()
    {
        $user = User::factory()->create();
        $user->notify(new FinancialReportPublished(FinancialReport::factory()->create()));
        $notification = $user->notifications()->first();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertNoContent();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read_via_the_api()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $other->notify(new FinancialReportPublished(FinancialReport::factory()->create()));
        $notification = $other->notifications()->first();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertNotFound();
    }
}
