<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaceCustomer extends Model
{
    use HasFactory;
    public $table = 'bot_customers';
    protected $casts = [
        'action_datas' => 'array'
    ];
    public function face_messages(){
        return $this->hasMany(FaceMessage::class);
    }
}
