<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\Revspring_webhook_data;
use PDO;

class WebhookController extends Controller
{
    public function paymentTokenWebhook(Request $request)
    {
        // Define your secret key
        $secretKey = 'ac54cdfac23a34d3416ff758db1f6aa3';

        // Get the headers from the incoming request
        $headers = $request->header();
        // return $headers['x-security-token'][0];

        // Check if the 'X-Security-Token' header is present
        if (!isset($headers['x-security-token'][0]) || $headers['x-security-token'][0] !== $secretKey) {
            // If the token is not present or incorrect, return an unauthorized response
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $jsonData = $request->json()->all();


        // Store the JSON data in a file
        $filePath = storage_path(date("Ymdhis") . '-webhook_data.json');

        // put that data into a json file
        file_put_contents($filePath, json_encode($jsonData));
        // You can perform other actions with the $jsonData as needed

        // Convert this intp object format for more understanding
        $content = (object) $jsonData;

        $revspring_webhook_data = new Revspring_webhook_data;
        $revspring_webhook_data->is_in_use = $content->IsInUse;
        $revspring_webhook_data->add_to_wallet = $content->AddToWallet;
        $revspring_webhook_data->token_data_source = $content->TokenDataSource;
        $revspring_webhook_data->revspring_id = $content->Id;
        $revspring_webhook_data->revspring_token_id = $content->TokenId;
        $revspring_webhook_data->masked_last_number = $content->MaskedLast4Number;
        $revspring_webhook_data->created_by = $content->CreatedBy;
        $revspring_webhook_data->first_name = $content->FirstName;
        $revspring_webhook_data->last_name = $content->LastName;
        $revspring_webhook_data->address1 = $content->Address1;
        $revspring_webhook_data->city = $content->City;
        $revspring_webhook_data->state = $content->State;
        $revspring_webhook_data->zip = $content->Zip;
        if ($revspring_webhook_data->save()) {
            return response()->json(
                [
                    'stauts' => 200,
                    'message' => "Payment token stored in our record."
                ],
                200
            );
        }

        // Return a response
        return response()->json(
            [
                'status' => 'success',
                'message' => 'Webhook data received and stored.'
            ],
            200
        );
    }

    public function redaWebhookData()
    {
        // echo Storage::get("20231215045153-webhook_data.json");
        $filePath = storage_path('20231227093842-webhook_data.json');

        try {

            $content = json_decode(File::get($filePath));
            // echo '<pre>';
            // print_r($content->Zip);

            $revspring_webhook_data = new Revspring_webhook_data;
            $revspring_webhook_data->is_in_use = $content->IsInUse;
            $revspring_webhook_data->add_to_wallet = $content->AddToWallet;
            $revspring_webhook_data->token_data_source = $content->TokenDataSource;
            $revspring_webhook_data->revspring_id = $content->Id;
            $revspring_webhook_data->revspring_token_id = $content->TokenId;
            $revspring_webhook_data->masked_last_number = $content->MaskedLast4Number;
            $revspring_webhook_data->created_by = $content->CreatedBy;
            $revspring_webhook_data->first_name = $content->FirstName;
            $revspring_webhook_data->last_name = $content->LastName;
            $revspring_webhook_data->address1 = $content->Address1;
            $revspring_webhook_data->city = $content->City;
            $revspring_webhook_data->state = $content->State;
            $revspring_webhook_data->zip = $content->Zip;
            if ($revspring_webhook_data->save()) {
                return response()->json(
                    [
                        'stauts' => 200,
                        'message' => "Payment token stored in our record."
                    ],
                    200
                );
            }
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
            echo 'File not found: ' . $filePath;
        }
    }

    
}
