<?php

namespace Tests\Feature;

use App\Models\TourAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TourPreviewTmpTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_preview(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\SeparatePortals::class);
        URL::forceRootUrl('http://127.0.0.1:8001');

        $rows = [
            ['Main Beach Entrance', 'Welcome to Talisay Beach Resort! This panoramic view shows our main entrance and the reception area.', 'tour-assets/B1UNeJDQo6HR3HJEW1Yek1oVc1fYnkY7TOmguYZk.mp4', 'video', 1, true],
            ['Beachfront Cottage Area', 'Our cozy beachfront cottages line the shore, offering direct access to the sand and sea.', 'tour-assets/panorama-2.jpg', 'image', 2, true],
            ['Restaurant and Bar', 'Our beachfront restaurant serves fresh seafood and local Filipino dishes.', 'tour-assets/panorama-3.jpg', 'image', 3, true],
            ['Premium Cabin Suite', 'Step inside our premium Beachfront Cabin Suite.', 'tour-assets/panorama-4.jpg', 'image', 4, true],
            ['Snorkeling and Water Sports Area', '', 'tour-assets/panorama-5.jpg', 'image', 5, false],
            ['Lobby photo', 'A normal photo of the lobby.', 'tour-assets/lobby-photo.jpg', 'image', 6, true],
        ];
        foreach ($rows as [$title, $desc, $path, $type, $order, $active]) {
            TourAsset::create(['title' => $title, 'description' => $desc, 'panorama_path' => $path, 'type' => $type, 'hotspots' => [], 'sort_order' => $order, 'is_active' => $active]);
        }

        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);
        $html = $this->actingAs($admin)->get('/tour-manage')->assertOk()->getContent();
        $html = str_replace('https://127.0.0.1:8001', 'http://127.0.0.1:8001', $html);
        $html = str_replace('src="/storage/', 'src="http://127.0.0.1:8001/storage/', $html);

        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tour-preview';
        @mkdir($dir);
        file_put_contents($dir.DIRECTORY_SEPARATOR.'tour.html', $html);
        $this->assertFileExists($dir.DIRECTORY_SEPARATOR.'tour.html');
    }
}
