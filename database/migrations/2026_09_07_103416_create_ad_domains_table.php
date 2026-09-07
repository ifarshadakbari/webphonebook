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
        Schema::create('ad_domains', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Domain 1"
            $table->string('slug')->unique(); // e.g. "domain-1"
            $table->string('hosts'); // e.g. "192.168.1.1,192.168.1.2"
            $table->string('base_dn'); // e.g. "dc=local,dc=com"
            $table->string('username'); // Service account username
            $table->string('password'); // Service account password
            $table->integer('port')->default(389);
            $table->boolean('use_ssl')->default(false);
            $table->boolean('use_tls')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_domains');
    }
};
