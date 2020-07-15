<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdditionalUsersFields;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class userController extends Controller
{
    /**
     * @OA\Put(
     *      path="/account/update",
     *      operationId="updateUser",
     *      tags={"Account"},
     *      summary="update user",
     *      description="update user",
     *      security={
     *        {"bearerAuth": {}},
     *      },
     *     @OA\Parameter(
     *         name="fields",
     *         in="query",
     *         description="additional fields",
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
     *                      property="users",
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
    public function update(Request $request)
    {
        $user = Auth::user();
        $additional_fields = AdditionalUsersFields::all()->pluck('', 'field_name')->toArray();
        $fields = json_decode($request['fields'], true);
        $diff = array_diff_key($fields, $additional_fields);
//        if( array_diff_key($fields, $additional_fields) ){
//            return response()->json([
//                'status' => 'fail',
//                'error' => [
//                    'errorCode' => 'unexpectedField',
//                    'data' => [
//                        'unexpectedFields' => $diff
//                    ]
//                ]
//            ]);
//        };
        $additional_data = $user->additional_data;
        $updateFields = array_unique(array_merge($additional_data, $fields));
        $user->additional_data = $updateFields;
        $user->save();
        return response()->json([
            'status' => 'success',
            'user' => $user,
        ], 200);

    }

    /**
     * @OA\Get(
     *      path="/users",
     *      operationId="getUsersList",
     *      tags={"Users"},
     *      summary="Get list of Users",
     *      description="Returns list of Users",
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
     *                  @OA\Property(
     *                      property="users",
     *                      type="array",
     *                      @OA\Items()
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
    public function getUsersList(Request $request)
    {
        $user = Auth::user();

        if ($user->type === 'admin') {
            foreach ($request->all() as $key => $value) {
                $value = '%' . $value . '%';
                array_push($arr, [$key, 'like', $value]);
            }
            $users = User::all();
            $additional_fields = AdditionalUsersFields::all()->pluck('field_name')->toArray();

            return response()->json([
                'status' => 'success',
                'users' => $users,
                'additional_fields' => $additional_fields
            ], 200);
        }
        return response()->json([
            'status' => 'fail',
            'errorCode' => 'accessDenied'
        ], 400);
    }

    /**
     * @OA\Get(
     *      path="/users/{userId}",
     *      operationId="getUser",
     *      tags={"Users"},
     *      summary="Get User",
     *      description="Get user by id",
     *      security={
     *        {"bearerAuth": {}},
     *      },
     *      @OA\Parameter(
     *          name="userId",
     *          description="user id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
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
    public function getUserById($id)
    {
        $user = Auth::user();
        if ($user->type === 'admin' || $user->type === 'moderator') {

            $userFound = User::where([
                'id' => $id
            ])->first();

            return response()->json([
                'status' => 'success',
                'user' => $userFound
            ], 200);

        }
        return response()->json([
            'status' => 'fail',
            'errorCode' => 'accessDenied'
        ], 400);
    }

    /**
     * @OA\Put(
     *      path="/users/{userId}/update",
     *      operationId="updateUserByid",
     *      tags={"Users"},
     *      summary="update user",
     *      description="update user by id",
     *      security={
     *        {"bearerAuth": {}},
     *      },
     *     @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="new username",
     *         @OA\Schema(
     *              type="string",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="new email",
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="new password",
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="new type",
     *     ),
     *     @OA\Parameter(
     *         name="fields",
     *         in="query",
     *         description="additional fields",
     *     ),
     *      @OA\Parameter(
     *          name="userId",
     *          description="user id",
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
    public function updateUser(Request $request, $id)
    {

        $user = Auth::user();

        if ($user->type === 'admin') {

            $additional_fields = AdditionalUsersFields::all()->pluck('validation rules', 'field_name')->toArray();
            $rules = array_merge($additional_fields, [
                'username' => ['string', 'max:255', 'unique:users', 'regex:/^[a-zA-Z][a-zA-Z0-9.,$;]+$/i'],
                'email' => ['string', 'email', 'max:255', 'unique:users'],
                'password' => ['string', 'min:8'],
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

            $dataArr = $request->all();
            if($dataArr){
                $foundUser = User::where(['id' => $id])->first();

                if(key_exists('fields', $dataArr)){

                    $fields = json_decode($dataArr['fields'], true);
                    unset($dataArr['fields']);
                    $additional_data = $foundUser->additional_data ? $foundUser->additional_data : [];
                    $updateFields = array_unique(array_merge($additional_data , $fields));
                    $dataArr['additional_data'] = $updateFields;
                }
                if(key_exists('password', $dataArr)){
                    $dataArr['password'] = bcrypt($dataArr['password']);
                }
                $updated = $foundUser->update($dataArr);

                if ($updated) {
//                $updatedUser = User::where(['id' => $id])->get();
                    return response()->json([
                        'status' => 'success',
                        'updatedUser' => $foundUser
                    ], 200);
                }

                return response()->json([
                    'status' => 'success',
                    'errorCode' => 'userNotFound'
                ]);
            }
            return response()->json([
                'status' => 'fail',
                'errorCode' => 'wrongData'
            ], 400);
        }

        return response()->json([
            'status' => 'fail',
            'errorCode' => 'accessDenied'
        ], 400);
    }

    /**
     * @OA\Post(
     *      path="/users/{userId}/suspend",
     *      operationId="suspendUser",
     *      tags={"Users"},
     *      summary="suspend user",
     *      description="suspend user",
     *      security={
     *        {"bearerAuth": {}},
     *      },
     *      @OA\Parameter(
     *          name="userId",
     *          description="user id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
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
    public function suspendUser($id)
    {
        $user = Auth::user();
        if ($user->type === 'admin') {

            $updated = User::where(['id' => $id])->update([
                'flag' => 'suspended'
            ]);

            if ($updated) {
                return response()->json([
                    'status' => 'success',
                ], 200);
            }

            return response()->json([
                'status' => 'fail',
                'errorCode' => 'userNotFound'
            ]);
        }

        return response()->json([
            'status' => 'fail',
            'errorCode' => 'accessDenied'
        ], 400);
    }

    /**
     * @OA\Delete(
     *      path="/users/{userId}/delete",
     *      operationId="deleteUser",
     *      tags={"Users"},
     *      summary="delete user",
     *      description="delete user",
     *      security={
     *        {"bearerAuth": {}},
     *      },
     *      @OA\Parameter(
     *          name="userId",
     *          description="user id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
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
    public function deleteUser($id)
    {
        $user = Auth::user();
        if ($user->type === 'admin') {

            $updated = User::where(['id' => $id])->update([
                'flag' => 'removed'
            ]);
            if ($updated) {
                return response()->json([
                    'status' => 'success',
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'errorCode' => 'userNotFound'
            ]);
        }

        return response()->json([
            'status' => 'fail',
            'errorCode' => 'accessDenied'
        ], 400);
    }
}