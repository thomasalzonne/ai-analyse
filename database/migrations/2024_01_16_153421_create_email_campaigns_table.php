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
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_client_id')->constrained('email_clients');
            $table->string('remote_id')->nullable();
            $table->string('name')->default('');
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('subject')->nullable();
            $table->string('sent_date')->nullable()->index();
            $table->json('tags')->nullable();
            $table->integer('recipients')->nullable();
            $table->integer('total_opened')->nullable();
            $table->integer('clicks')->nullable();
            $table->integer('unsubscribed')->nullable();
            $table->integer('bounced')->nullable();
            $table->integer('unique_opened')->nullable();
            $table->integer('spam_complaints')->nullable();
            $table->string('webversion_url')->nullable();
            $table->string('worldview_url')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
