<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revspring_webhook_data extends Model
{
    use HasFactory;

    protected $table = "revspring_webhook_data";

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'email','created_by');
    }
}
