<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceMessage extends Model
{
    use HasFactory;
    public $table = 'bot_messages';

    public function face_customer(){
        return $this->belongsTo(FaceCustomer::class);
    }
}
