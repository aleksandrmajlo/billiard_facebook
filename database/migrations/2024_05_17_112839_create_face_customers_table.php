<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFaceCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_customers', function (Blueprint $table) {
            $table->id();
            $table->string('face_id')->index();
            $table->string('type',50)->default('face')->index()->comment("C какой сети попал");
            $table->bigInteger('customer_id')->nullable()->index();
            $table->text('next_action')->nullable()->comment('какой следующий шаг при  бронировании');
            $table->json('action_datas')->nullable()->comment('данные по шагам');
            $table->string('conversation_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bot_customers');
    }
}
