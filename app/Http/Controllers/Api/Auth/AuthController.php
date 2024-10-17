<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enum\ResponseMethodEnum;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{

    public function login(Request $request)
    {
         $request->validate([
            'email' => 'required|email',
            'password' => 'required',

        ]);

         $user=User::whereEmail($request->email)->first();



        if (!$user || !Hash::check($request->password, $user->password)) {
            return generalApiResponse(
                method: ResponseMethodEnum::CUSTOM,
                custom_message: __('Invalid credentials'),
                custom_status: 422
                , custom_status_msg: 'error'
            );
        }

        // generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        data_set($user, 'token', $token);

        return generalApiResponse(
            method: ResponseMethodEnum::CUSTOM,
            data_passed: ['user' => $user],
            custom_message: __('We have sent you an OTP to your phone number')
        );
    }




}


