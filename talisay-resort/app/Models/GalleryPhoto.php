<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'caption', 'image_path', 'sort_order'])]

/**
 * A photo of a resort view or attraction, shown on the public About gallery.
 * Room and cottage photos stay on their own pages.
 */
class GalleryPhoto extends Model
{
    /**
     * Root-relative URL so the photo loads on the site the guest is browsing.
     */
    public function imageUrl(): string
    {
        return '/storage/'.ltrim((string) $this->image_path, '/');
    }
}
