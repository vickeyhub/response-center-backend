<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinician_counseling extends Model
{
    use HasFactory;

    protected $table = "clinician_counseling";

    protected $fillable = ['clinician_id','clinical_service'];

    public function clinician_counseling(){
        return $this->belongsTo(Clinical_service::class);
    }
}
