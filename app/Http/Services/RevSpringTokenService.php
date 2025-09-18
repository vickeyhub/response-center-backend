<?php
namespace App\Http\Services;

use App\Helpers\Common;
use App\Models\RevSpring_token;

class RevSpringTokenService
{
    public static function create($tokenRequestData){
        $RevSpring_token = new RevSpring_token;

        $RevSpring_token->appointment_id = $tokenRequestData['last_insert_id_of_appointment'];
        $RevSpring_token->web_token_link = $tokenRequestData['WebTokenLink'];
        $RevSpring_token->web_token_id = $tokenRequestData['WebTokenId'];
        $RevSpring_token->success_status = $tokenRequestData['revspring_status'];
        
        if($RevSpring_token->save()){
            return true;
        }
            
    }
}