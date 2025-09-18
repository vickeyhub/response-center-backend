<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

use Illuminate\Http\Request;
use App\Models\Appointment;

class TestController extends Controller
{
    public function getRevSpringToken()
    {

        $url = 'https://ws-stg.revspringinc.com/OrgMgr/V1/WebAuthentication/Launch';

        $headers = [
            'Accept: application/json',
            'AppName: ClientAdmin',
            'ConnectionId: e4dd5fdc-aafa-4b94-ad99-0b52eb87d64a',
            'Content-Type: application/json',
        ];

        $data = [
            'Payload' => [
                'Username' => '0d22a82f-2592-47c9-b46a-42694c822ea0',
                'Password' => 'e064ee61-235e-4cd8-8775-0fdf11cc81fb',
                'ConsumerNumber' => '24',
                'OrgSiteName' => 'responsivecenter',
                'GroupSiteName' => 'azaleastaging',
                'ExternalUsername' => 'test',
                'UserEmail' => 'sabkasai@gmail.com',
                'UserFirstName' => 'Ravi',
                'UserLastName' => 'Mehra',
                'RoleName' => 'CSR',
                'DeepLink' => 'WalletOnly',
            ],
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        echo '<pre>'; print_r($response);

        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        curl_close($ch);
    }

    public function readRevspringTokensFromDatabase(){
        return Appointment::find(1)->revspring_token;
    }
}
