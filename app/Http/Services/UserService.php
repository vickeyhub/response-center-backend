<?php

namespace App\Http\Services;

use App\Mail\SendPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UserService
{

    /**
     * Undocumented function
     *
     * @param [array] $requestedData
     * @return void
     */
    public static function validateRequest($requestedData, $id = null)
    {
        $validator = Validator::make($requestedData, [
            'name' => 'required|string|min:3',
            // 'email' => 'required|unique:users,email,NULL,NULL,deleted_at,NULL',
            'email' => [
                'required',
                Rule::unique('users')->where(function ($query) {
                    // Include soft-deleted rows in the unique check
                    $query->whereNull('deleted_at');
                }),
            ],
            // 'password' => 'required|min:6',
            'role_id' => 'required',
        ]);
        return [
            'message' => $validator->fails() ? $validator->messages()->first() : null,
            'status' => $validator->fails() ? false : true,
        ];
    }

    /**
     * Undocumented function
     *
     * @param [type] $requestedData
     * @return void
     */
    public static function create($requestedData)
    {

        $plainPassword = Str::random(8);
        $requestedData['password'] = Hash::make($plainPassword);
        User::create($requestedData);
        $content = [
            'subject' => 'Account created successfully!',
            'body' => 'This is the email body of how to send email from laravel 10 with mailtrap.',
            'password' => $plainPassword,
            'email' => $requestedData['email'],
            'name' => $requestedData['name'],
        ];

        //Mail::to($requestedData['email'])->send(new SendPassword($content));

        Log::info('email has been to sent to specific user');
        
        return true;
    }

    /**
     * Undocumented function
     *
     * @param [type] $id
     * @return void
     */
    public static function getUser($id)
    {
        $user = User::where('id', $id)->first();

        return $user;
    }

    /**
     * Undocumented function
     *
     * @param [type] $requestedData
     * @param [type] $type
     * @return void
     */
    public static function update($requestedData, $user)
    {

        $validator = Validator::make($requestedData, [
            'name' => 'required|string|min:3',
            'email' => 'required|email|unique:users,email,' . $user->id . '',
            'role_id' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'message' => $validator->messages()->first(),
                'status' => false,
            ];
        }

        $user->update($requestedData);

        return [
            'message' => 'Updated successfully',
            'status' => true,
        ];
    }

    /**
     * Undocumented function
     *
     * @param [type] $user
     * @return void
     */
    public static function delete($user)
    {
        $user->delete();

        return true;
    }

    /**
     * Undocumented function
     *
     * @param [type] $ids
     * @return void
     */
    public static function deleteMultiple($ids)
    {
        User::whereIn('id', $ids)->delete();

        return true;
    }
}
