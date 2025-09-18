<?php

namespace App\Http\Services;

use App\Helpers\Common;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class AppointmentService
{

    /**
     * Undocumented function
     *
     * @param [array] $requestedData
     * @return void
     */
    public static function validateRequest($requestedData)
    {
        $validator = Validator::make($requestedData, [
            'first_name' => 'required|string|min:3',
            'email' => 'required',
            // 'email' => 'required|email',
            'home_phone' => 'required',
            // 'mobile_number' => 'required',
            'dob' => 'required',
            // 'date' => 'required',
            // 'time' => 'required',
            'service_id' => 'required',
            // 'relation_to_patient' => 'required',
            // 'hear_about_us' => 'required|string',
        ]);

        return [
            'message' => $validator->fails() ? $validator->messages()->first() : null,
            'status' => $validator->fails() ? false : true,
        ];
    }

    /**
     * Create clinician
     *
     * @param [type] $requestedData
     * @return void
     */
    public static function create($requestedData)
    {
       return $clinician = Appointment::create($requestedData);
       
        return true;
    }

    /**
     * Undocumented function
     *
     * @param [type] $id
     * @return void
     */
    public static function getAppointment($id)
    {
        $appointment = Appointment::where('id', $id)->orWhere('reference_id', $id)
        ->with('clinician', 'serviceProvided', 'locationData', 'insurance', 'plan', 'referral','revspring_webhook_data')->first();

        return $appointment;
    }
    public static function getAppointmentbyEmail($email)
    {
        $appointment = Appointment::where('email', $email)
        ->with('clinician', 'serviceProvided', 'locationData', 'insurance', 'plan', 'referral','revspring_webhook_data')->first();

        return $appointment;
    }

    /**
     * Undocumented function
     *
     * @param [type] $requestedData
     * @param [type] $appointment
     * @return void
     */
    public static function cancelAppointment($requestedData, $appointment)
    {
        $appointment->update([
            'cancellation_reason' => $requestedData['cancellation_reason'],
            'status' => 'Cancelled',
        ]);

        return $appointment;
    }

    /**
     * Undocumented function
     *
     * @param [type] $patientId
     * @param [type] $accessToken
     * @return void
     */
    public static function getAzaleaAppointment($patientId, $accessToken)
    {
        $url = "https://app.azaleahealth.com/fhir/R4/142114/Appointment?actor=Patient/$patientId";
        $appointments = Common::handleCurlGetRequest($url, $accessToken);
        $appointmentId = null;
        $appointmentData = json_decode($appointments['response']);
        $appointments = $appointmentData->entry;
        if (count($appointments) > 0) {
            $currentAppointment = $appointments[count($appointments) - 1];
            if ($currentAppointment->resource && $currentAppointment->resource->id) {
                $appointmentId = $currentAppointment->resource->id;
            }
        }
        Log::info('Log for getAzaleaAppointment function ');

        return $appointmentId;
    }

    public static function getRevSpringToken($first_name, $last_name, $email, $patient_id)
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
                'ConsumerNumber' => "24",
                'OrgSiteName' => 'responsivecenterx',
                'GroupSiteName' => 'azaleastaging',
                'ExternalUsername' => $email,
                'UserEmail' => $email,
                'UserFirstName' => $first_name,
                'UserLastName' => $last_name,
                'RoleName' => 'CSR',
                'DeepLink' => 'WalletOnly',
            ],
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
