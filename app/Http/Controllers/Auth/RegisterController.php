<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdditionalUsersFields;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {

    }

    /**
     * @OA\Post(
     *      path="/account/register",
     *      operationId="userRegister",
     *      tags={"Account"},
     *      summary="user reigster ",
     *      description="user registration",
     *     @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="Your username",
     *         required=true,
     *         @OA\Schema(
     *              type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Your email",
     *         required=true,
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Your password",
     *         required=true,
     *     ),
     *     @OA\Parameter(
     *         name="password_confirmation",
     *         in="query",
     *         description="Your password confirmation",
     *         required=true,
     *     ),
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
     *                      property="token",
     *                      type="string",
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
     *                      property="error",
     *                      type="array",
     *                      @OA\Items()
     *                  ),
     *              )
     *          )
     *      ),
     *     )
     *
     * Returns list of users
     */
    public function register(Request $request)
    {
        $additional_fields = AdditionalUsersFields::all()->pluck('validation rules', 'field_name')->toArray();

        $rules = array_merge($additional_fields, [
            'username' => ['required', 'string', 'max:255', 'unique:users', 'regex:/^[a-zA-Z][a-zA-Z0-9.,$;]+$/i'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $validator =  Validator::make(
            $request->all(),
            $rules,
            [
                'regex' => 'incorrect:'.ucfirst('attribute'),
                'string' => 'incorrect:'.ucfirst('attribute'),
                'max' => 'incorrect:'.ucfirst('attribute'),
                'unique' => ':attributeAlreadyUsed',
                'required' => ':attributeIsRequired'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'error' => [
                    'errorCode' => 'wrongData',
                    'data' => $validator->errors()

                ],
            ]);
        };

        $user = User::create([
            'username' => $request['username'],
            'email' => $request['email'],
            'password' => bcrypt($request['password']),
            'type' => $request['type'] ? $request['type'] : 'admin',
            'additional_data' => $request['additional_data'] ? $request['additional_data'] : null
        ]);

        $this->guard()->login($user);

        $data = [
            'status' => 'success',
            'token' => $user->createToken('nfce_client')->accessToken,
            'user'  => $user

        ];

        return response()->json($data, 200);
    }
}