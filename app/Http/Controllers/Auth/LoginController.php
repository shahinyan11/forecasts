<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
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
        $this->middleware('guest')->except('logout');
    }

    /**
     * @OA\Post(
     *      path="/account/login",
     *      operationId="login",
     *      tags={"Account"},
     *      summary="login",
     *      description="login",
     *     @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="Your username or email",
     *         required=true,
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Your password",
     *         required=true,
     *         @OA\Schema(
     *              type="string",
     *         )
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
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        $login = $request['username'];
        $password = $request['password'];

        $usernameOrEmail = strpos($login, '@') ? 'email' : 'username';

        $credentials = [
            $usernameOrEmail => $login,
            'password' => $password,
        ];
        $user = User::where([$usernameOrEmail => $login])->first();

        if ($user && $user->flag === 'removed') {
            return response()->json([
                'status' => 'fail',
                'errorCode' => 'userIsDeleted'
            ]);
        }
//      if ($this->attemptLogin($request)) {
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $data = [
                'status' => 'success',
                'token' => $user->createToken('MyApp')->accessToken,
                'user' => $user
            ];

            return response()->json($data, 200);
        }

//      return $this->sendFailedLoginResponse($request);
        return response()->json([
            'status' => 'fail',
            'errorCode' => 'incorrectLoginOrPassword'

        ]);
    }

    /**
     * @OA\Get(
     *      path="/account/logout",
     *      operationId="logout",
     *      tags={"Account"},
     *      summary="logout",
     *      description="logout",
     *      security={
     *        {"bearerAuth": {}},
     *      },
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
     *                      type="string",
     *                  ),
     *              )
     *          )
     *      ),
     *     )
     *
     * Returns list of users
     */
    public function logout()
    {
        $user = Auth::user();
        $user->token()->revoke();
        $user->token()->delete();

        return response()->json(['status' => 'success'], 200);
    }
}
