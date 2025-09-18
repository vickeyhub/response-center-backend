<?php

namespace App\Http\Controllers\Api\Azalea;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    /**
     * Undocumented function
     *
     * @param Request $request
     * @return void
     */
    public function getPatientData(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => [
                    'required',
                    Rule::unique('appointments')->where(function ($query) {
                        // Include soft-deleted rows in the unique check
                        $query->whereNull('deleted_at');
                    }),
                ],
            ]);
            
            $accessToken = Common::getAzaleaAccessToken();
            $name = urlencode($request->get('name'));
            $email = $request->get('email');
            $patientData = null;

            $url = "https://app.azaleahealth.com/fhir/R4/142114/Patient?name=$name&email=$email";

            $curlRequest = Common::handleCurlGetRequest($url, $accessToken->access_token);
            $decodedData = json_decode($curlRequest['response'], true);
            // echo '<pre>'; print_r($decodedData);
            if (isset($decodedData['error'])) {
                $patientData = null;
            } else {
                if (sizeof($decodedData) && isset($decodedData['entry'])) {
                    $patientData = $decodedData['entry'][0]['resource'];
                    $patientId = isset($patientData['id']) ? $patientData['id'] : null;
                    if ($patientId) {
                        //Check if appointment exists with booked status
                        $bookedUrl = "https://app.azaleahealth.com/fhir/R4/142114/Appointment?patient=Patient/$patientId&status=booked";
                        $bookedCurlRequest = Common::handleCurlGetRequest($bookedUrl, $accessToken->access_token);
                        $bookedData = json_decode($bookedCurlRequest['response'], true);

                        //Check if appointment exists with arrived status
                        $arrivedUrl = "https://app.azaleahealth.com/fhir/R4/142114/Appointment?patient=Patient/$patientId&status=arrived";
                        $arrivedCurlRequest = Common::handleCurlGetRequest($arrivedUrl, $accessToken->access_token);
                        $arrivedData = json_decode($arrivedCurlRequest['response'], true);

                        //Check if appointment exists with fulfilled status
                        $fulfilledUrl = "https://app.azaleahealth.com/fhir/R4/142114/Appointment?patient=Patient/$patientId&status=fulfilled";
                        $fulfilledCurlRequest = Common::handleCurlGetRequest($fulfilledUrl, $accessToken->access_token);
                        $fulfilledData = json_decode($fulfilledCurlRequest['response'], true);

                        if (isset($bookedData['error']) || isset($arrivedData['error']) || isset($fulfilledData['error']) || !isset($bookedData['entry']) || !isset($arrivedData['entry']) || !isset($fulfilledData['entry'])) {
                            $patientData = null;
                        }
                    } else {
                        $patientData = null;
                    }
                }
            }

            return Common::sendResponse(null, 200, $patientData, true);
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 200, [], false);
        }
    }
}
