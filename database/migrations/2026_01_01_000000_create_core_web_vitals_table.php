<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_web_vitals', function (Blueprint $table) {
            $table->id();

            // ── Foreign references ──────────────────────────────────────
            $table->unsignedBigInteger('domainmanagement_id');
            $table->unsignedBigInteger('client_property_id');
            $table->unsignedBigInteger('user_id');

            // ── Analysed URL ────────────────────────────────────────────
            $table->string('url');

            // ── Mobile metrics ──────────────────────────────────────────
            $table->unsignedTinyInteger('mobile_performance_score')->nullable();

            $table->string('mobile_lcp_display',  30)->nullable();
            $table->float('mobile_lcp_value')->nullable();
            $table->float('mobile_lcp_score')->nullable();

            $table->string('mobile_cls_display',  30)->nullable();
            $table->float('mobile_cls_value')->nullable();
            $table->float('mobile_cls_score')->nullable();

            $table->string('mobile_tbt_display',  30)->nullable();
            $table->float('mobile_tbt_value')->nullable();
            $table->float('mobile_tbt_score')->nullable();

            $table->string('mobile_inp_display',  30)->nullable();
            $table->float('mobile_inp_value')->nullable();
            $table->float('mobile_inp_score')->nullable();

            $table->string('mobile_fcp_display',  30)->nullable();
            $table->float('mobile_fcp_value')->nullable();
            $table->float('mobile_fcp_score')->nullable();

            $table->string('mobile_ttfb_display', 30)->nullable();
            $table->float('mobile_ttfb_value')->nullable();
            $table->float('mobile_ttfb_score')->nullable();

            $table->string('mobile_si_display',   30)->nullable();
            $table->float('mobile_si_value')->nullable();
            $table->float('mobile_si_score')->nullable();

            $table->string('mobile_tti_display',  30)->nullable();
            $table->float('mobile_tti_value')->nullable();
            $table->float('mobile_tti_score')->nullable();

            // ── Desktop metrics ─────────────────────────────────────────
            $table->unsignedTinyInteger('desktop_performance_score')->nullable();

            $table->string('desktop_lcp_display',  30)->nullable();
            $table->float('desktop_lcp_value')->nullable();
            $table->float('desktop_lcp_score')->nullable();

            $table->string('desktop_cls_display',  30)->nullable();
            $table->float('desktop_cls_value')->nullable();
            $table->float('desktop_cls_score')->nullable();

            $table->string('desktop_tbt_display',  30)->nullable();
            $table->float('desktop_tbt_value')->nullable();
            $table->float('desktop_tbt_score')->nullable();

            $table->string('desktop_inp_display',  30)->nullable();
            $table->float('desktop_inp_value')->nullable();
            $table->float('desktop_inp_score')->nullable();

            $table->string('desktop_fcp_display',  30)->nullable();
            $table->float('desktop_fcp_value')->nullable();
            $table->float('desktop_fcp_score')->nullable();

            $table->string('desktop_ttfb_display', 30)->nullable();
            $table->float('desktop_ttfb_value')->nullable();
            $table->float('desktop_ttfb_score')->nullable();

            $table->string('desktop_si_display',   30)->nullable();
            $table->float('desktop_si_value')->nullable();
            $table->float('desktop_si_score')->nullable();

            $table->string('desktop_tti_display',  30)->nullable();
            $table->float('desktop_tti_value')->nullable();
            $table->float('desktop_tti_score')->nullable();

            $table->timestamps();

            // ── Indexes ─────────────────────────────────────────────────
            $table->index('domainmanagement_id');
            $table->index('client_property_id');
            $table->index('user_id');
            $table->index('url');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_web_vitals');
    }
};