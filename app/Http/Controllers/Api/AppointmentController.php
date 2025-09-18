<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Services\AppointmentService;
use App\Http\Services\RevSpringTokenService;
use App\Mail\CancelAppointment;
use App\Mail\RescheduleAppointment;
use App\Mail\SendAppointmentConfirmation;
use App\Models\Appointment;
use App\Models\Appointment_payment;
use App\Models\Clinician;
use App\Models\Revspring_webhook_data;
use App\Models\Location;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AppointmentController extends Controller
{

    /**
     * Get list of appointments
     *
     * @return void
     */
    public function index(Request $request)
    {
        try {
            $filter = $request->get('filter');
            $searchQuery = $request->get('searchQuery');
            $todaysAppointments = Appointment::where('date', date('Y-m-d'))->where('status', '!=', 'Cancelled')->where('payment_status', 'confirmed')->count();
            $upcomingAppointments = Appointment::where('date', '>', date('Y-m-d'))
                ->where('status', '!=', 'Cancelled')
                ->where('payment_status', 'confirmed')
                ->count();
            $cancelledAppointments = Appointment::where('status', 'Cancelled')->where('payment_status', 'confirmed')->count();

            $appointments = Appointment::with(['referral', 'clinician'])
                ->where('referral_id', null);

            if (!empty($searchQuery)) {
                $keyword = $searchQuery;
                $appointments = $appointments->where(function ($query) use ($keyword) {
                    $query->where('first_name', 'like', "%$keyword%")
                        ->orWhere('email', 'like', "%$keyword%")
                        ->orWhere('reference_id', 'like', "%$keyword%");
                });
            }
            if ($filter == "0") {
                $appointments = $appointments->where('status', 'Pending')
                    ->where('date', '>=', date('Y-m-d'))
                    ->where('payment_status', 'pending')
                    ->where('referral_id', null);
            }
            if ($filter == "1") {
                // $date = date('Y-m-d');
                $appointments = $appointments->where('date', '>', date('Y-m-d'))
                    ->where('status', '!=', 'Cancelled')
                    // ->where('status', '!=', 'Fulfilled')
                    ->where(['payment_status' => 'confirmed']);
                // ->where('referral_id', null);
                //    dd($appointments->toSql());
            }
            if ($filter == "2") {
                $appointments = $appointments->where('status', 'Cancelled')->where('payment_status', 'confirmed')->where('referral_id', null);
            }
            if ($filter == "3") {
                $appointments = $appointments->where('date', date('Y-m-d'))->where('status', '!=', 'Cancelled')->where('status', '!=', 'Fulfilled')->where('payment_status', 'confirmed')->where('referral_id', null);
            }
            if ($filter == "4") {
                $appointments = $appointments->where('status', 'Cancelled')->where('payment_status', 'pending')->where('referral_id', null);
            }
            if ($filter == "5") {
                $appointments = $appointments->where('payment_status', 'pending')->where('referral_id', null);
            }
            if ($filter == "6") {
                $appointments = $appointments->where('payment_status', 'confirmed')->where('referral_id', null);
            }
            if ($filter == "7") {
                $appointments = $appointments->where('status', 'Fulfilled')->where('referral_id', null);
            }
            if ($filter == "8") {
                $appointments = Appointment::with(['referral', 'clinician'])->where('referral_id', '!=', null);
            }
            if ($filter == "9") {
                $referrals = Referral::where('dr_name', 'like', $searchQuery . '%')->pluck('id')->toArray();
                $appointments = Appointment::with(['referral', 'clinician'])->where('referral_id', '!=', null)->where(function ($query) use ($searchQuery, $referrals) {
                    $query->orWhere('first_name', 'like', $searchQuery . '%')->orWhere('reference_id', 'like', $searchQuery . '%')->orWhereIn('referral_id', $referrals);
                });
            }
            $totalAppointments = $appointments->count();
            $appointments = $appointments->skip($request->get('skip'))->take(10);
            $appointments = $appointments->orderBy('date', 'asc')->get();

            $data = [
                'todaysAppointments' => $todaysAppointments,
                'upcomingAppointments' => $upcomingAppointments,
                'totalAppointments' => $totalAppointments,
                'appointments' => $appointments,
                'cancelledAppointments' => $cancelledAppointments,
            ];

            return Common::sendResponse(null, 200, $data, true);
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    /**
     * Add Appointment
     *
     * @param Request $request
     * @return void
     */
    private function getFileExtensionFromBase64($base64String)
    {
        $data = explode(',', $base64String);

        if (count($data) > 0) {
            $mimeData = explode(';', $data[0]);

            if (count($mimeData) > 0) {
                $mime = explode('/', $mimeData[0]);

                if (count($mime) === 2) {
                    $extensions = [
                        'jpeg' => 'jpeg',
                        'jpg' => 'jpg',
                        'png' => 'png',
                        'gif' => 'gif',
                        // Add more if needed
                    ];

                    // Use the correct file extension or fallback to 'bin' if not recognized
                    return $extensions[$mime[1]] ?? 'bin';
                }
            }
        }

        return null;
    }
    public function store(Request $request)
    {

        if(!empty($request->input('insurance_card_front'))) {
            $insurance_card_front_url = $request->input('insurance_card_front');
            $insurance_card_back_url = $request->input('insurance_card_back');
            $driver_license_front = $request->input('driver_license_front');
            $driver_license_back_url = $request->input('driver_license_back');

            list($mime, $base64String) = explode(';', $insurance_card_front_url);
            list($back_mime, $back_base64String) = explode(';', $insurance_card_back_url);
            list($driver_front_mime, $driver_front_base64String) = explode(';', $driver_license_front);
            list($driver_back_mime, $driver_back_base64String) = explode(';', $driver_license_back_url);

            list(, $base64String) = explode(',', $base64String);
            list(, $back_base64String) = explode(',', $back_base64String);
            list(, $driver_front_base64String) = explode(',', $driver_front_base64String);
            list(, $driver_back_base64String) = explode(',', $driver_back_base64String);

            $imageData = base64_decode($base64String);
            $back_imageData = base64_decode($back_base64String);
            $driver_front_imageData = base64_decode($driver_front_base64String);
            $driver_back_imageData = base64_decode($driver_back_base64String);

            // Generate a unique filename based on the MIME type
            $fileExtension = explode('/', $mime)[1];
            $backfileExtension = explode('/', $back_mime)[1];
            $driverFrontfileExtension = explode('/', $driver_front_mime)[1];
            $driverBackfileExtension = explode('/', $driver_back_mime)[1];

            $filename = uniqid('image_') . '.' . $fileExtension;
            $back_filename = uniqid('back_image_') . '.' . $backfileExtension;
            $driver_front_filename = uniqid('driver_front_') . '.' . $driverFrontfileExtension;
            $driver_back_filename = uniqid('driver_back_') . '.' . $driverBackfileExtension;

            // Save the image to the storage folder
            Storage::disk('public')->put($filename, $imageData);
            Storage::disk('public')->put($back_filename, $back_imageData);
            Storage::disk('public')->put($driver_front_filename, $driver_front_imageData);
            Storage::disk('public')->put($driver_back_filename, $driver_back_imageData);

            // You can also save the filename or perform any additional processing here

            // return response()->json(['message' => 'Image uploaded successfully']);
        } else {
            return response()->json(['error' => 'Error uploading image'], 500);
        }
        // return $request->all();
        // try {

        $validateRequest = AppointmentService::validateRequest($request->all());
        if ($validateRequest['status'] === true) {
            $accessToken = Common::getAzaleaAccessToken();
            $requestedData = $request->all();

            if($filename && $back_filename && $driver_front_filename && $driver_back_filename){
                $requestedData['insurance_card_front'] = '/storage/'.$filename;
                $requestedData['insurance_card_back']  = '/storage/'.$back_filename;
                $requestedData['driver_license_front'] = '/storage/'.$driver_front_filename;
                $requestedData['driver_license_back']  = '/storage/'.$driver_back_filename;
            } else {
                $requestedData['insurance_card_front'] = null;
                $requestedData['insurance_card_back'] = null;
                $requestedData['driver_license_front'] = null;
                $requestedData['driver_license_back'] = null;
            }
            
            $location = Location::where('azalea_id', $request->get('location'))->first();
            $requestedData['location'] = [
                "reference" => "Location/$location->azalea_id",
                "type" => "Location",
                "display" => $location->display_name,
            ];
            $createPatient = $this->createPatient($requestedData, $accessToken->access_token);
            // return $requestedData;
            $formattedData = json_decode($createPatient);
            Log::info('$formattedData => ', (array) $formattedData);
            if ($formattedData != "") {
                $requestedData['patient_id'] = $formattedData ? $formattedData->id : null;
                // if (!isset($requestedData['insurance_id'])) {   
                // } 
                // remove this when you work on revspring data
                // if (isset($requestedData['insurance_id'])) {
                // } else {
                //     $requestedData['status'] = 'booked';
                // }

                $requestedData['status'] = 'pending';
                $createAppointment = $this->createAppointment($requestedData, $accessToken->access_token, null, 'POST');
                if ($createAppointment['response'] != "") {
                    $appointmentData = json_decode($createAppointment['response']);
                    if ($appointmentData && $appointmentData->resourceType == "Appointment") {
                        $azaleaAppointmentId = AppointmentService::getAzaleaAppointment($requestedData['patient_id'], $accessToken->access_token);
                        $totalAppointments = Appointment::count();
                        $requestedData['reason_code'] = isset($appointmentData->reasonCode) ? serialize($appointmentData->reasonCode) : null;
                        $requestedData['start'] = $appointmentData->start;
                        $requestedData['end'] = $appointmentData->end;
                        $requestedData['reference_id'] = rand(100000, 999999) . '' . $totalAppointments;
                        $requestedData['location'] = $location->azalea_id;
                        $requestedData['status'] = 'Pending';
                        // $requestedData['status'] = isset($requestedData['insurance_id']) ? 'Pending' : 'Booked';
                        $requestedData['payment_status'] = 'pending';
                        // $requestedData['payment_status'] = isset($requestedData['insurance_id']) ? 'pending' : 'confirmed';
                        $requestedData['azalea_appointment_id'] = $azaleaAppointmentId;
                        $requestedData['azalea_patient_id'] = $requestedData['patient_id'];

                        $appointment = AppointmentService::create($requestedData); // 15Dec 2023

                        /* 
                                date - 14 dec 2023
                                integrate "RevSpring SSO with deep link to wallet" cURL API
                            */
                        // Now, you can access the last inserted ID
                        $lastInsertedId = $appointment->id;
                        $tokenRequestData = [];
                        // if (!isset($requestedData['insurance_id'])) {
                        $getRevSpringToken = AppointmentService::getRevSpringToken($requestedData['first_name'], $requestedData['last_name'], $requestedData['email'], $requestedData['patient_id']);

                        $getRevSpringToken = json_decode($getRevSpringToken);

                        $tokenRequestData['WebTokenLink'] = $getRevSpringToken->WebTokenLink;
                        $tokenRequestData['WebTokenId'] = $getRevSpringToken->WebTokenId;
                        $tokenRequestData['revspring_status'] = $getRevSpringToken->Success;
                        $tokenRequestData['last_insert_id_of_appointment'] = $lastInsertedId;

                        $revCreate = RevSpringTokenService::create($tokenRequestData);


                        Log::info('$getRevSpringToken => ', (array) $getRevSpringToken);
                        // }
                        $clinician = Clinician::where('id', $requestedData['clinician_id'])->first();
                        $content = [
                            'name' => $requestedData['first_name'] . ' ' . $requestedData['last_name'],
                            'date' => $requestedData['date'],
                            'time' => $requestedData['time'],
                            'charges' => $clinician->charges_per_session,
                            'location' => $location->name,
                            'payment_mode' => $requestedData['payment_mode'],
                            'clinicianImage' => $clinician->image,
                            'clinicianName' => $clinician->name,
                            'email' => $clinician->email,
                            'qualification' => $clinician->qualification,
                            'phone_no' => $clinician->phone_no,
                        ];

                        // //Mail::to($requestedData['email'])->send(new SendAppointmentConfirmation($content));
                        // if (!isset($requestedData['insurance_id'])) {
                        return Common::sendResponse('Appointment has been scheduled successfully.', 200, [
                            'reference_id' => $requestedData['reference_id'],
                            'webtoken_url' => $tokenRequestData['WebTokenLink']
                        ], true);
                        // } 
                        // else {
                        //     return Common::sendResponse('Appointment has been scheduled successfully.', 200, [
                        //         'reference_id' => $requestedData['reference_id']
                        //     ], true);
                        // }
                    } else {
                        Log::info('Else case store function appointment controller');

                        return Common::sendResponse("Slot is already booked. Please try another slot.", 200, [], false);
                    }
                } else {
                    Log::info('Else case store function appointment controller  response is null');

                    return Common::sendResponse("Slot is already booked. Please try with other slot.", 200, [], false);
                }
            } else {
                return Common::sendResponse("Slot is already booked. Please try with other slot.", 200, [], false);
            }
        } else {

            return Common::sendResponse($validateRequest['message'], 200, [], false);
        }
        // } catch (\Exception $e) {
        //     return Common::sendResponse($e->getMessage(), 500, [], false);
        // }
    }
    /**
     * Undocumented function
     *
     * @param Request $request
     * @param [type] $id
     * @return void
     */
    public function show(Request $request, $id)
    {
        try {
            $appointment = AppointmentService::getAppointment($id);
            if ($appointment) {

                return Common::sendResponse(null, 200, $appointment, true);
            } else {
                return Common::sendResponse('No record found', 200, [], false);
            }
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    /**
     * Cancel appointment
     *
     * @param Request $request
     * @return void
     */
    public function cancel(Request $request)
    {

        try {
            $appointment = AppointmentService::getAppointment($request->get('appointment_id'));
            if ($appointment) {
                // $accessToken = Common::getAzaleaAccessToken();
                // $requestedData = [
                //     "patient_id" => $appointment->azalea_patient_id,
                //     "first_name" => $appointment->first_name,
                // ];
                // $payload = [
                //     "resourceType" => "Appointment",
                //     "id" => $appointment->azalea_appointment_id,
                //     "status" => "cancelled",
                //     "cancellationDate" => "2023-10-25",
                // "participant" => [
                //     [
                //         "actor" => [
                //             "reference" => "Patient/388833",
                //             "type" => "Patient",
                //             "display" => "random clinician",
                //         ],
                //         "status" => "needs-action",
                //     ]
                // ],
                // ];

                // $url = "https://app.azaleahealth.com/fhir/R4/142114/Appointment/" . $appointment->azalea_appointment_id;
                // Log::info('Payload for appointment cancel on azalea ==> ', (array) $payload);
                // $appointment = Common::handleCurlPostRequest($url, $accessToken->access_token, $payload, "PATCH");
                // Log::info('Log for cancel function ==> ', $appointment);
                // dd($appointment);
                $requestedData = $request->all();
                AppointmentService::cancelAppointment($requestedData, $appointment);

                return Common::sendResponse('Appointment has been cancelled.', 200, $appointment, true);
            } else {
                return Common::sendResponse('No record found', 200, [], false);
            }
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    /**
     * Function to create patient in azalea
     *
     * @param [type] $requestedData
     * @param [type] $accessToken
     * @return void
     */
    public function createPatient($requestedData, $accessToken)
    {
        $payload = [
            "resourceType" => "Patient",
            "active" => true,
            "name" => [
                [
                    "use" => "usual",
                    "text" => $requestedData['first_name'],
                    "family" => $requestedData['last_name'],
                    "given" => [
                        $requestedData['first_name'],
                    ],
                ],
            ],
            "telecom" => [
                [
                    "system" => "email",
                    "value" => $requestedData['email'],
                    "rank" => 1,
                ],
                [
                    "system" => "phone",
                    "value" => $requestedData['home_phone'],
                    "use" => "home",
                ],
            ],

            "birthDate" => $requestedData['dob'],
        ];

        if ($requestedData['mobile_number']) {
            $payload['telecom'][] = [
                "system" => "phone",
                "value" => $requestedData['mobile_number'],
                "use" => "mobile",
            ];
        }

        $url = "https://app.azaleahealth.com/fhir/R4/142114/Patient";
        $createPatientRequest = Common::handleCurlPostRequest($url, $accessToken, $payload);

        return $createPatientRequest['response'];
    }

    /**
     * Function to create appointment in azalea
     *
     * @param [type] $requestedData
     * @param [type] $accessToken
     * @return void
     */
    public function createAppointment($requestedData, $accessToken, $appointmentId = null, $method = "POST")
    {
        $payload = [
            "resourceType" => "Appointment",
            "status" => $requestedData['status'],
            "start" => $requestedData['start_date_time'],
            "end" => $requestedData['end_date_time'],
            "participant" => [
                [
                    "actor" => $requestedData['location'],
                    "status" => "accepted",
                ],
                [
                    "actor" => [
                        "reference" => "Patient/" . $requestedData['patient_id'],
                        "type" => "Patient",
                        "display" => $requestedData['first_name'],
                    ],
                    "status" => "accepted",
                ],
                [
                    "actor" => [
                        "reference" => "Practitioner/" . $requestedData['practitioner_id'],
                        "type" => "Practitioner",
                        "display" => $requestedData['practitioner_name'],
                    ],
                    "status" => "accepted",
                ],
            ],
        ];
        if (isset($requestedData['appointmentType']) && sizeof($requestedData['appointmentType'])) {
            $payload['reasonCode'] = [$requestedData['appointmentType']];
        }
        if ($appointmentId) {
            $payload['id'] = $appointmentId;
            $url = "https://app.azaleahealth.com/fhir/R4/142114/Appointment/" . $appointmentId;
        } else {
            $url = "https://app.azaleahealth.com/fhir/R4/142114/Appointment";
        }

        Log::info('Log for createAppointment function payload ==> ', $payload);
        $appointment = Common::handleCurlPostRequest($url, $accessToken, $payload, $method);
        Log::info('Log for createAppointment function ==> ', $appointment);
        return $appointment;
    }

    /**
     * Reschedule appointment function
     *
     * @param Request $request
     * @return void
     */
    public function rescheduleAppointment(Request $request)
    {
        try {
            $appointment = AppointmentService::getAppointment($request->get('appointment_id'));
            if ($appointment) {
                $accessToken = Common::getAzaleaAccessToken();
                $location = Location::where('azalea_id', $appointment->location)->first();
                $requestedDataLocation = [
                    "reference" => "Location/$location->azalea_id",
                    "type" => "Location",
                    "display" => $location->display_name,
                ];

                $requestedData = [
                    "start_date_time" => $appointment->start,
                    "end_date_time" => $appointment->end,
                    "location" => $requestedDataLocation,
                    "patient_id" => $appointment->azalea_patient_id,
                    "first_name" => $appointment->first_name,
                    "practitioner_id" => $appointment->clinician->azalea_id,
                    "practitioner_name" => $appointment->clinician->name,
                    "appointmentType" => $request->get('appointmentType'),
                    "status" => "cancelled",
                ];

                $cancelAppointment = $this->createAppointment($requestedData, $accessToken->access_token, $appointment->azalea_appointment_id, 'PUT');

                $requestedData['status'] = "booked";
                $requestedData['start_date_time'] = $request->get('start_date_time');
                $requestedData['end_date_time'] = $request->get('end_date_time');
                $createAppointment = $this->createAppointment($requestedData, $accessToken->access_token, null, 'POST');

                if ($createAppointment['response'] != "") {
                    $appointmentData = json_decode($createAppointment['response']);

                    if ($appointmentData && $appointmentData->resourceType == "Appointment") {
                        $azaleaAppointmentId = AppointmentService::getAzaleaAppointment($appointment->azalea_patient_id, $accessToken->access_token);
                        $appointment->update([
                            'date' => $request->get('date'),
                            'time' => $request->get('time'),
                            'azalea_appointment_id' => $azaleaAppointmentId,
                            'start' => $appointmentData->start,
                            'end' => $appointmentData->end,
                        ]);

                        $content = [
                            'name' => $appointment->first_name . ' ' . $appointment->last_name,
                            'date' => $appointment->date,
                            'time' => $appointment->time,
                            'charges' => $appointment->clinician ? $appointment->clinician->charges_per_session : 0,
                            'location' => $location->name,
                            'payment_mode' => $appointment->payment_mode,
                            'clinicianImage' => $appointment->clinician ? $appointment->clinician->image : null,
                            'clinicianName' => $appointment->clinician ? $appointment->clinician->name : null,
                            'email' => $appointment->clinician ? $appointment->clinician->email : null,
                            'qualification' => $appointment->clinician ? $appointment->clinician->qualification : null,
                            'phone_no' => $appointment->clinician ? $appointment->clinician->phone_no : null,
                        ];

                        //Mail::to($appointment->email)->send(new RescheduleAppointment($content));

                        return Common::sendResponse('Re-scheduled successfully.', 200, [], true);
                    } else {

                        return Common::sendResponse("Slot is already booked. Please try with other slot.", 200, [], false);
                    }
                } else {
                    Log::info('Else case rescheduleAppointment function');

                    return Common::sendResponse("Slot is already booked. Please try with other slot.", 200, [], false);
                }
            } else {

                return Common::sendResponse("Appointment not found.", 200, [], false);
            }
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @return void
     */
    public function updateStatus(Request $request)
    {
        try {
            $appointment = AppointmentService::getAppointment($request->get('appointment_id'));
            if ($appointment) {
                $accessToken = Common::getAzaleaAccessToken();
                $location = Location::where('azalea_id', $appointment->location)->first();
                $requestedDataLocation = [
                    "reference" => "Location/$location->azalea_id",
                    "type" => "Location",
                    "display" => $location->display_name,
                ];

                $requestedData = [
                    "start_date_time" => $appointment->start,
                    "end_date_time" => $appointment->end,
                    "location" => $requestedDataLocation,
                    "patient_id" => $appointment->azalea_patient_id,
                    "first_name" => $appointment->first_name,
                    "practitioner_id" => $appointment->clinician->azalea_id,
                    "practitioner_name" => $appointment->clinician->name,
                    "status" => lcfirst($appointment->status),
                ];

                if ($appointment->reason_code) {
                    $requestedData["appointmentType"] = unserialize($appointment->reason_code);
                }

                $updateAppointment = $this->createAppointment($requestedData, $accessToken->access_token, $appointment->azalea_appointment_id, 'PUT');

                $appointment->update([
                    'status' => $request->get('status'),
                    'payment_status' => $request->get('status') == "booked" ? "confirmed" : $appointment->payment_status,
                    'cancellation_reason' => $request->get('cancellation_reason'),
                    'payment_method' => $request->get('payment_mode') != "" && $request->get('payment_mode') != "null" ? $request->get('payment_mode') : null,
                ]);

                $content = [
                    'clinicianName' => $appointment->clinician ? $appointment->clinician->name : null,
                    'date' => $appointment->date,
                    'time' => $appointment->time,
                    'name' => $appointment->first_name . ' ' . $appointment->last_name,
                    'reference_no' => $appointment->reference_id,
                    'reason' => $appointment->cancellation_reason,
                ];

                //Mail::to($appointment->email)->send(new CancelAppointment($content));

                return Common::sendResponse('Status updated successfully.', 200, [], true);
            } else {

                return Common::sendResponse("Appointment not found.", 200, [], false);
            }
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @return void
     */
    public function getPatientAppointment(Request $request)
    {
        try {
            $last_name = '';
            $nameArray = explode(' ', $request->get('name'));

            $nameArrayCount = count($nameArray);
            if ($nameArrayCount > 1) {
                $last_name = $nameArray[1];
            } else {
                $last_name = '';
            }
            $currentDate = Carbon::now()->toDateString();
            
            $appointment = Appointment::where([
                'email' => $request->get('email'),
                'first_name' => $nameArray[0],
                'last_name' => $last_name,
                'status' => 'Pending',
                'payment_status' => 'pending',
            ])
            ->where('date', '>=', date('Y-m-d'))
            ->with('clinician', 'serviceProvided', 'locationData', 'insurance', 'plan', 'referral')->orderBy('created_at', 'desc')->first();
            
            if ($appointment) {

                return Common::sendResponse('', 200, $appointment, true);
            } else {

                return Common::sendResponse("Appointment not found.", 200, [], false);
            }
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }


    public function cardChargedApi(Request $request)
    {
        $appointment = AppointmentService::getAppointment($request->reference_id);
        $appointment['email'];
        if ($appointment) {
            $accessToken = Common::getAzaleaAccessToken();
            $location = Location::where('azalea_id', $appointment->location)->first();

            $revspring_webhook_data = Revspring_webhook_data::where('created_by', $appointment['email'])->first();

            $requestedDataLocation = [
                "reference" => "Location/$location->azalea_id",
                "type" => "Location",
                "display" => $location->display_name,
            ];

            // return $request->all();
            $requestedData = [
                "start_date_time" => $appointment->start,
                "end_date_time" => $appointment->end,
                'location' =>  $requestedDataLocation,
                "patient_id" => $appointment->azalea_patient_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'reference_id' => $request->reference_id,
                'status' => $request->status,
                'appointment_id' => $request->appointment_id,
                'token_id' => $request->token_id,
                "practitioner_id" => $appointment->clinician->azalea_id,
                "practitioner_name" => $appointment->clinician->name,
                "status" => lcfirst($appointment->status),
            ];
        }
        // return $appointment;

        /*======== Run the cURL API for charged the payment 
            @param: 
            Date: 22 Dec, 2023
         ==========*/
        $transactionId = $appointment['azalea_appointment_id'];
        // take payment amount from pending booked data
        $totalAmount = number_format($request->amount, 2, '.', '');

        if ($revspring_webhook_data->PaymentMethod) {
            $PaymentMethod = $revspring_webhook_data->PaymentMethod;
        } else {
            $PaymentMethod = "card";
        }

        $arrayData = [
            'tokenId' => $revspring_webhook_data->revspring_token_id,
            'transactionType' => 'sale',
            'transactionId' => $transactionId,
            'paymentMethod' => $PaymentMethod,
            'referenceNumber' => $request->reference_id,
            'referenceName' => $requestedData['first_name'] . ' ' . $requestedData['last_name'],
            'totalAmount' =>  $totalAmount,
            'fullName' => $requestedData['first_name'] . ' ' . $requestedData['last_name'],
            'firstName' => $requestedData['first_name'],
            'lastName' => $requestedData['last_name'],
            'address1' =>  $revspring_webhook_data->address1,
            'city' => $revspring_webhook_data->city,
            'state' => $revspring_webhook_data->state,
            'zip' => $revspring_webhook_data->zip,
            'gatewayRoutingCode' => '550183',
        ];

        // return $arrayData;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-staging.revspringinc.com/api/rest/services/v1/payment',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($arrayData),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer CC5ED146-AC3D-49FA-AF73-ECDB01922B6A',
                'clientIdType: EVOKE',
                'clientId: 1914',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // echo $response;
        $response = json_decode($response);

        if ($response->status == 'approved') {

            $request->status = 'Booked';
            $request->payment_status = 'confirmed';

            $accessToken =  (object) Common::getAzaleaAccessToken();

            $updateAppointment = $this->createAppointment($requestedData, $accessToken->access_token, null, 'PUT');

            $payment_data = [
                'appointment_id' => $appointment->id,
                'transaction_id' => $transactionId,
                'total_amount' => $totalAmount,
                'status' => $response->status,
                'message' => $response->message,
                'gateway_transaction_id' => $response->gatewayTransactionId,
                'authorization_code' => $response->authorizationCode,
                'gateway_name' => $response->gatewayName,
                'payment_date_time' => $response->paymentDateTime,
                'network' => $response->network,
                'card_holder_name' => $response->cardHolderName,
                'payment_status' => 1,
            ];
            $appointment_payment_query = Appointment_payment::create($payment_data);

            $appointment->update([
                'status' => $request->status,
                'payment_status' => $request->payment_status,
                'cancellation_reason' => $request->cancellation_reason,
                'payment_method' => $request->payment_mode != "" && $request->payment_mode != "null" ? $request->payment_mode : null,
            ]);

            $content = [
                'clinicianName' => $appointment->clinician ? $appointment->clinician->name : null,
                'date' => $appointment->date,
                'time' => $appointment->time,
                'name' => $appointment->first_name . ' ' . $appointment->last_name,
                'reference_no' => $appointment->reference_id,
                'reason' => $appointment->cancellation_reason,
            ];

            return Common::sendResponse('Appointment has been booked.', 200, [], true);

            $clinician = Clinician::where('id', $requestedData['clinician_id'])->first();
            $content = [
                'name' => $requestedData['first_name'] . ' ' . $requestedData['last_name'],
                'date' => $requestedData['date'],
                'time' => $requestedData['time'],
                'charges' => $clinician->charges_per_session,
                'location' => $location->name,
                'payment_mode' => $requestedData['payment_mode'],
                'clinicianImage' => $clinician->image,
                'clinicianName' => $clinician->name,
                'email' => $clinician->email,
                'qualification' => $clinician->qualification,
                'phone_no' => $clinician->phone_no,
            ];

            //Mail::to($requestedData['email'])->send(new SendAppointmentConfirmation($content));
        }
    }

    public function check_card_added_or_not(Request $request)
    {


        $checkPayentToken = Revspring_webhook_data::where('created_by', $request->email)->first();

        if ($checkPayentToken) {
            return response()->json([
                'status' => '200',
                'message' => 'Your Card has added'
            ], 200);
        } else {
            // try {
            $email = $request->email;
            $cancellation_reason = "Card Not found in appointent";
            $status = "cancelled";
            $payment_mode = null;

            $appointment = AppointmentService::getAppointmentbyEmail($email);
            if ($appointment) {
                $accessToken = Common::getAzaleaAccessToken();
                $location = Location::where('azalea_id', $appointment->location)->first();
                $requestedDataLocation = [
                    "reference" => "Location/$location->azalea_id",
                    "type" => "Location",
                    "display" => $location->display_name,
                ];

                $requestedData = [
                    "start_date_time" => $appointment->start,
                    "end_date_time" => $appointment->end,
                    "location" => $requestedDataLocation,
                    "patient_id" => $appointment->azalea_patient_id,
                    "first_name" => $appointment->first_name,
                    "practitioner_id" => $appointment->clinician->azalea_id,
                    "practitioner_name" => $appointment->clinician->name,
                    "status" => lcfirst($appointment->status),
                ];

                if ($appointment->reason_code) {
                    $requestedData["appointmentType"] = unserialize($appointment->reason_code);
                }

                $updateAppointment = $this->createAppointment($requestedData, $accessToken->access_token, $appointment->azalea_appointment_id, 'PUT');

                $appointment->update([
                    'status' => $status,
                    'payment_status' => $status == "booked" ? "confirmed" : $appointment->payment_status,
                    'cancellation_reason' => $cancellation_reason,
                    'payment_method' => $payment_mode != "" && $request->get('payment_mode') != "null" ? $request->get('payment_mode') : null,
                ]);

                // $content = [
                //     'clinicianName' => $appointment->clinician ? $appointment->clinician->name : null,
                //     'date' => $appointment->date,
                //     'time' => $appointment->time,
                //     'name' => $appointment->first_name . ' ' . $appointment->last_name,
                //     'reference_no' => $appointment->reference_id,
                //     'reason' => $appointment->cancellation_reason,
                // ];

                //Mail::to($appointment->email)->send(new CancelAppointment($content));
                Appointment::where('email', $request->email)->delete();
                return response()->json([
                    'status' => '201',
                    'message' => 'Status updated successfully'
                ], 201);
            } else {
                return response()->json([
                    'status' => '201',
                    'message' => 'Appointment not found.'
                ], 201);
            }
            // } catch (\Exception $e) {

            //     return Common::sendResponse($e->getMessage(), 500, [], false);
            // }
        }
    }
    public function check_card_added_or_not_only(Request $request)
    {


        $checkPayentToken = Revspring_webhook_data::where('created_by', $request->email)->first();

        if ($checkPayentToken) {
            return response()->json([
                'status' => '200',
                'message' => 'Your Card has added'
            ], 200);
        } else {
            return response()->json([
                'status' => '201',
                'message' => 'Not found.'
            ], 201);
        }
    }
}
