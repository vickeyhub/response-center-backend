<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment_payment extends Model
{
    use HasFactory;
    
    protected $table = "appointment_payment";

    protected $guarded = [];
}
