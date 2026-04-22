<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_competitor_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('domainmanagement_id');
            $table->unsignedBigInteger('client_property_id');
            $table->unsignedBigInteger('user_id');
            $table->string('location');
            $table->json('keywords');          // array of keyword strings
            $table->string('client_domain')->nullable();
            $table->string('competitor_option')->nullable(); // 'auto' | 'define' | null
            $table->json('results_json');      // final [{keyword, data:{organic_results:[]}}] array
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->index(['domainmanagement_id', 'client_property_id'], 'rcr_domain_client_idx');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_competitor_reports');
    }
};