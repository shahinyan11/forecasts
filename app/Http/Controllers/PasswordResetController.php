<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */


    /**
     * @OA\Post(
     *      path="/account/reset",
     *      operationId="resetQuery",
     *      tags={"Account"},
     *      summary="query reset user",
     *      description=" query reset user",
     *      @OA\Parameter(
     *          name="email",
     *          description="user email",
     *          required=true,
     *          in="query",
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="status",
     *                      type="string",
     *                      enum={"success"}
     *                  ),
     *                  @OA\Property(
     *                      property="message",
     *                      type="string",
     *                  ),
     *              )
     *          )
     *       ),
     *       @OA\Response(
     *          response=400,
     *          description="request error",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="status",
     *                      type="string",
     *                      enum={"fail"}
     *                  ),
     *                  @OA\Property(
     *                      property="errorCode",
     *                      type="string"
     *                  ),
     *              )
     *          )
     *      ),
     *     )
     *
     * Returns list of users
     */
    public function create(Request $request)
    {

        $request->validate([
            'email' => 'required|string|email',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user)
            return response()->json([
                'status' => 'fail',
                'message' => 'emailNotFound'
            ], 400);
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Str::random(60)
             ]
        );
        if ($user && $passwordReset)
            $user->notify(
                new PasswordResetRequest($passwordReset->token)
            );
        return response()->json([
            'status'=> "success",
            'token' => $passwordReset->token,
            'message' => 'We have e - mailed your password reset link!'
        ]);
    }
    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */

    /**
     * @OA\Get(
     *      path="/account/reset/{token}",
     *      operationId="getUserByToken",
     *      tags={"Account"},
     *      summary="get user by token",
     *      description=" get user by token",
     *      @OA\Parameter(
     *          name="token",
     *          description="authentification token",
     *          required=true,
     *          in="path",
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="status",
     *                      type="string",
     *                      enum={"success"}
     *                  ),
     *                  @OA\Property(
     *                      property="user",
     *                      type="object",
     *                  ),
     *              )
     *          )
     *       ),
     *       @OA\Response(
     *          response=400,
     *          description="request error",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="status",
     *                      type="string",
     *                      enum={"fail"}
     *                  ),
     *                  @OA\Property(
     *                      property="errorCode",
     *                      type="string"
     *                  ),
     *              )
     *          )
     *      ),
     *     )
     *
     * Returns list of users
     */
    public function find($token)
    {
        $passwordReset = PasswordReset::where('token', $token)
            ->first();
        if (!$passwordReset)
            return response()->json([
                'status' => 'fail',
                'errorCode' => 'invalidToken'
            ], 400);
        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'status' => 'fail',
                'errorCode' => 'invalidToken'
            ], 400);
        }
        return response()->json($passwordReset);
    }
     /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */


    /**
     * @OA\Post(
     *      path="/account/resetProcess",
     *      operationId="resetPassword",
     *      tags={"Account"},
     *      summary="reset password",
     *      description="reset password",
     *      @OA\Parameter(
     *          name="token",
     *          description="authtentification tolen",
     *          required=true,
     *          in="query",
     *      ),
     *      @OA\Parameter(
     *          name="password",
     *          description="new password",
     *          required=true,
     *          in="query",
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="status",
     *                      type="string",
     *                      enum={"success"}
     *                  ),
     *                  @OA\Property(
     *                      property="user",
     *                      type="object",
     *                  ),
     *              )
     *          )
     *       ),
     *       @OA\Response(
     *          response=400,
     *          description="request error",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="status",
     *                      type="string",
     *                      enum={"fail"}
     *                  ),
     *                  @OA\Property(
     *                      property="errorCode",
     *                      type="string"
     *                  ),
     *              )
     *          )
     *      ),
     *     )
     *
     * Returns list of users
     */
    public function reset(Request $request)
    {
        $request->validate([
//            'email' => 'required | string | email',
            'password' => 'required | string',
            'token' => 'required | string'
        ]);
        $passwordReset = PasswordReset::where([
            ['token', $request->token],
//            ['email', $request->email]
        ])->first();
        if (!$passwordReset)
            return response()->json([
                'status' => 'fail',
                'errorCode' => 'invalidToken'
            ], 400);
        $user = User::where('email', $passwordReset->email)->first();
        if (!$user)
            return response()->json([
                'status' => 'fail',
                'errorCode' => 'emailNotFound'
            ], 400);
        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        $user->notify(new PasswordResetSuccess($passwordReset));
        return response()->json([
            'status'=> 'success',
            'user' => $user

        ]);
    }
}