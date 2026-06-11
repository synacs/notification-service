<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('batch_id')->nullable();
            $table->string('channel');
            $table->string('contact')
                ->index();
            $table->text('message');
            $table->enum('status', ['pending', 'processing', 'sent', 'delivered', 'failed'])
                ->default('pending')
                ->index();
            $table->tinyInteger('priority')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->foreign('batch_id')
                ->references('id')
                ->on('notification_batches')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
