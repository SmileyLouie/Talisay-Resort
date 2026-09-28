<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('emergencies');
    }

    public function down(): void
    {
        // Feature removed permanently
    }
};
