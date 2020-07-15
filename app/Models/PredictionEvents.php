<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PredictionEvents extends Model
{
    protected $table = 'prediction_events';

    protected $fillable = [
        'predictionId',
        'eventId',
        'marketId',
        'outcome',
        'odds'
    ];

    public function predictions (){
        return $this->belongsTo('App\Models\Predictions', 'predictionId',  'id' );
    }
}
