<?php

// ============================================================
// TourAsset.php — Model for the 'tour_assets' Table
// ============================================================
// Represents a 360° panoramic image used in the virtual resort tour.
// Each asset has a title, description, panorama image path,
// and an array of interactive hotspots (info points and scene links).
// Used by the Pannellum.js viewer on the front-end.
// ============================================================

namespace App\Models;

// Import Fillable attribute for mass-assignment protection
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for test/seed factory support
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import base Eloquent Model
use Illuminate\Database\Eloquent\Model;

// Declare mass-assignable columns for this model
#[Fillable([
    'title',         // Scene title displayed in the virtual tour (e.g., 'Main Beach Entrance')
    'description',   // Descriptive text shown below the panorama scene title
    'panorama_path', // Storage path to the equirectangular panorama image (JPG/PNG)
    'type',          // Asset type: 'image' (equirectangular) or 'video' (360° video)
    'hotspots',      // JSON array of clickable hotspot definitions (pitch, yaw, text, type)
    'sort_order',    // Integer display order — lower numbers appear first in the tour
    'is_active',     // Whether this scene is visible in the public virtual tour viewer
])]

/**
 * Class TourAsset
 *
 * Represents one scene/location in the 360° virtual resort tour.
 * Hotspots are defined as arrays with keys: pitch, yaw, text, type, and sceneId.
 * The Pannellum.js viewer reads these to render interactive navigation points.
 *
 * Example hotspot structure:
 * ['pitch' => 10, 'yaw' => -30, 'text' => 'Reception Hall', 'type' => 'info']
 * ['pitch' => -5, 'yaw' => 45, 'text' => 'Cottages', 'type' => 'scene', 'sceneId' => 'cottage-area']
 *
 * @property int         $id            Auto-incrementing primary key
 * @property string      $title         Scene display title
 * @property string      $description   Descriptive text for this scene
 * @property string      $panorama_path Storage path to the panorama image
 * @property string      $type          'image' or 'video'
 * @property array|null  $hotspots      Array of hotspot definition objects
 * @property int         $sort_order    Display order in the tour sequence
 * @property bool        $is_active     Whether this scene is publicly visible
 */
class TourAsset extends Model
{
    // Enable factory support for test data generation
    use HasFactory;

    /**
     * Define type casts for automatic data transformation.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast hotspots from JSON string to PHP array
            // Allows: foreach ($asset->hotspots as $hotspot) { ... }
            'hotspots'  => 'array',

            // Cast is_active from integer (0/1) to boolean (true/false)
            'is_active' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ──────────────────────────────────────────────────────────

    /**
     * Scope: Filter to only active tour scenes, ordered by sort_order.
     * Use this when loading scenes for the public-facing virtual tour viewer.
     * Inactive scenes are hidden from public but still manageable in admin panel.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        // Filter for active scenes AND sort them by the sort_order column (ascending)
        // This ensures scenes appear in the intended tour sequence order
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Same-origin URL for the uploaded panorama or video.
     * A root-relative path keeps the 360 viewer on the site the guest is browsing.
     */
    public function mediaUrl(): string
    {
        if (! $this->panorama_path) {
            return '';
        }

        if (str_starts_with($this->panorama_path, 'http://') || str_starts_with($this->panorama_path, 'https://')) {
            return $this->panorama_path;
        }

        return '/storage/'.ltrim($this->panorama_path, '/');
    }

    /**
     * True when the image is a 2:1 equirectangular panorama the 360 viewer can wrap.
     * Any other image is shown to guests as a flat photo.
     */
    public function isPanorama(): bool
    {
        if ($this->type === 'video' || ! $this->panorama_path) {
            return false;
        }

        $file = storage_path('app/public/'.ltrim($this->panorama_path, '/'));
        $info = is_file($file) ? @getimagesize($file) : null;
        if (! $info || ($info[1] ?? 0) <= 0) {
            return false;
        }

        $ratio = $info[0] / $info[1];

        return $ratio >= 1.7 && $ratio <= 2.3 && $info[0] >= 800;
    }
}
