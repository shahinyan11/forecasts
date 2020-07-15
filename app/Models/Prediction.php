<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Prediction extends Model
{
    protected $table = 'predictions';

    protected $fillable = [
        'userId',
        'moderatorId',
        'onModeration',
        'betPercentage',
        'betAmount',
        'isApproved',
        'isSuccessful',
        'description',
        'odds',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $user = Auth::user();
        if ($user->type !== 'admin' && $user->type !== 'moderator') {
           $this.$this->setHidden([
               'isApproved',
               'moderatorId',
               'onModeration'
           ]);
        }
    }

    public function predictionEvents (){
        return $this->hasMany('App\Models\PredictionEvents', 'predictionId',  'id' );
    }
}
