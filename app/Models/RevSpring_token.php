<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RevSpring_token extends Model
{
    use HasFactory;

    protected $table = "revspring_tokens";

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

}
