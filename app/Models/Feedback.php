<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = ['plan_id', 'dureza', 'comentario'];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
