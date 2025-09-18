<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Services\Masters\SpecialityService;
use App\Models\Speciality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpecialityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $allSpecialitiesCount = Speciality::all()->count();

            if ($request->get('active') == 'true') {
                $specialities = Speciality::where('status', 1)->get();
            } else {
                $specialities = Speciality::orderBy('order_number', 'asc')->skip($request->get('skip'))->take(10)->get();
            }

            $data = [
                'data' => $specialities,
                'totalRecords' => $allSpecialitiesCount,
            ];

            return Common::sendResponse(null, 200, $data, true);
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validateRequest = SpecialityService::validateRequest($request->all());
            if ($validateRequest['status'] === true) {
                SpecialityService::create($request->all());

                return Common::sendResponse('Speciality created successfully.', 200, [], true);
            } else {
                return Common::sendResponse($validateRequest['message'], 400, [], false);
            }
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param [number] $id
     * @return void
     */
    public function show(Request $request, $id)
    {
        try {
            $speciality = SpecialityService::getSpeciality($id);
            if ($speciality) {

                return Common::sendResponse(null, 200, $speciality, true);
            } else {
                return Common::sendResponse('No record found', 200, [], false);
            }
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $speciality = SpecialityService::getSpeciality($id);
            if ($speciality) {
                $validateRequest = SpecialityService::validateRequest($request->all(), $speciality);
                if ($validateRequest['status'] === true || $request->get('name') === $speciality['name']) {
                    SpecialityService::update($request->all(), $speciality);

                    return Common::sendResponse('Updated successfully.', 200, $speciality, true);
                } else {
                    return Common::sendResponse('The name has already been taken', 400, [], false);
                }

            } else {
                return Common::sendResponse('No record found', 200, [], false);
            }
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $speciality = SpecialityService::getSpeciality($id);
            if ($speciality) {
                SpecialityService::delete($speciality);

                return Common::sendResponse('Deleted successfully.', 200, [], true);
            } else {
                return Common::sendResponse('No record found', 200, [], false);
            }
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    /**
     * Delete multipla specialities
     *
     * @param Request $request
     * @return void
     */
    public function deleteMultipleRecord(Request $request)
    {
        try {
            SpecialityService::deleteMultiple($request->get('specialities'));

            return Common::sendResponse('Deleted successfully.', 200, [], true);
        } catch (\Exception $e) {

            return Common::sendResponse($e->getMessage(), 500, [], false);
        }
    }

    public function changespecialityOrder(Request $request)
    {
        $direction = $request->direction;
        $current_order_number = intval($request->order_number);

        if ($direction == 'up') {
            $new_order_number = $current_order_number - 1;
        } else if ($direction == 'down') {
            $new_order_number = $current_order_number + 1;
        } else {
            // Handle invalid direction
            return Common::sendResponse('Invalid direction.', 400, [], false);
        }

        // Ensure that the new order number is within valid bounds
        if ($new_order_number <= 0) {
            return Common::sendResponse('Invalid order number.', 400, [], false);
        }

        // Use a single query to update order numbers
        Speciality::whereIn('order_number', [$current_order_number, $new_order_number])
            ->update([
                'order_number' => DB::raw("CASE WHEN order_number = $new_order_number THEN $current_order_number ELSE $new_order_number END"),
            ]);
        return Common::sendResponse('Order numbers updated successfully.', 200, [], true);
    }
}
