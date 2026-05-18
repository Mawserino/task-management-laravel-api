<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->foreignId('assigned_to')->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('team_id')->constrained();
            $table->dateTime('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'priority']);
            $table->index('assigned_to');
            $table->index('team_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};