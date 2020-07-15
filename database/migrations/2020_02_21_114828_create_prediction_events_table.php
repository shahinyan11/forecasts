<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePredictionEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prediction_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('predictionId');
            $table->foreign('predictionId')
                ->references('id')
                ->on('predictions')
              ->onDelete('cascade');
            $table->integer('eventId');
            $table->unique(['predictionId', 'eventId']);
            $table->text('marketId');
            $table->text('outcome');
            $table->decimal('odds');
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
        Schema::dropIfExists('prediction_events');
    }
}
