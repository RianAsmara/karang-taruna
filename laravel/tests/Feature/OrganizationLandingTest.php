<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_is_visible_without_logging_in()
    {
        $organization = Organization::factory()->create(['name' => 'Karang Taruna Melati']);

        $this->get("/org/{$organization->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('organization.name', 'Karang Taruna Melati'));
    }

    public function test_it_shows_only_upcoming_planned_or_ongoing_events()
    {
        $organization = Organization::factory()->create();

        $upcoming = Event::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Rapat Bulanan',
            'status' => EventStatus::Planned,
            'start_at' => now()->addWeek(),
        ]);
        Event::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Kegiatan Lalu',
            'status' => EventStatus::Completed,
            'start_at' => now()->subWeek(),
        ]);
        Event::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Kegiatan Dibatalkan',
            'status' => EventStatus::Cancelled,
            'start_at' => now()->addWeek(),
        ]);

        $response = $this->get("/org/{$organization->slug}");

        $response->assertInertia(fn ($page) => $page
            ->has('upcomingEvents', 1)
            ->where('upcomingEvents.0.title', $upcoming->title)
        );
    }

    public function test_it_shows_only_published_announcements()
    {
        $organization = Organization::factory()->create();

        Announcement::factory()->create([
            'organization_id' => $organization->id,
            'title' => 'Sudah Terbit',
            'published_at' => now()->subDay(),
        ]);
        Announcement::factory()->draft()->create([
            'organization_id' => $organization->id,
            'title' => 'Masih Draf',
        ]);

        $response = $this->get("/org/{$organization->slug}");

        $response->assertInertia(fn ($page) => $page
            ->has('announcements', 1)
            ->where('announcements.0.title', 'Sudah Terbit')
        );
    }

    public function test_it_does_not_expose_financial_or_membership_details()
    {
        $organization = Organization::factory()->create();

        $response = $this->get("/org/{$organization->slug}");

        $response->assertInertia(fn ($page) => $page
            ->missing('organization.id')
            ->missing('organization.require_transaction_approval')
            ->missing('organization.settings')
        );
    }

    public function test_an_unknown_slug_returns_a_404()
    {
        $this->get('/org/tidak-ada')->assertNotFound();
    }
}
