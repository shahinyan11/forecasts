<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePredictionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('userId');
            $table->foreign('userId')
                ->references('id')
                ->on('users');
//              ->onDelete('cascade');
            $table->integer('betPercentage');
            $table->integer('betAmount');
            $table->decimal('odds', 8, 2);
            $table->text('description');
            $table->boolean('isSuccessful')->nullable();
            $table->boolean('isApproved')->default(false);
            $table->timestamp('onModeration')->nullable();
            $table->integer('moderatorId')->nullable();
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
        Schema::dropIfExists('predictions');
    }
}
