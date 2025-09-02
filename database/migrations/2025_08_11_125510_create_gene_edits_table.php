<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gene_edits', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('gene_id')->constrained('genes')->onDelete('cascade');
            $table->string('field'); // instructor or room
            $table->integer('old_value_id');
            $table->integer('new_value_id');
            $table->integer('changed_by'); // user_id
            $table->timestamp('changed_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gene_edits');
    }
};
