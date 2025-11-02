<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_categories_table.php

// ...
public function up(): void
{
    Schema::create('nx_categories', function (Blueprint $table) {
        $table->id('id'); // 
        $table->string('name', 50)->unique();
        $table->timestamps();
    });
}
// ...

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nx_categories');
    }
};
