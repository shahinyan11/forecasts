<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use App\Models\PredictionEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PredictionController extends Controller
{
    /**
     * @OA\Post(
     *      path="/predictions",
     *      operationId="createPrediction",
     *      tags={"Prediction"},
     *      summary="createPrediction",
     *      description="create prediction",
     *      security={
     *        {"bearerAuth": {}},
     *      },
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="description",
     *                      description="prediction description",
     *                      type="string",
     *                  ),
     *                  @OA\Property(
     *                      property="betPercentage",
     *                      description="percentage of money",
     *                      type="integer",
     *                  ),
     *                  @OA\Property(
     *                      property="events",
     *                      description="events list",
     *                      type="array",
     *                      @OA\Items(
     *                          type="object"
     *                      )
     *                  ),
     *              ),
     *          ),
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
     *                      property="prediction",
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
    public function createPrediction(Request $request)
    {
        $user = Auth::user();

        if($user->balance <= 0){
            return response()->json([
                'status' => 'fail',
                'error' => [
                    'errorCode' => 'insufficientBalance',
                ],
            ]);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'description' => ['required', 'string', 'max:2000'],
                'betPercentage' => ['required', 'integer'],
                'events' => ['required', 'array'],
                'events.*.eventId' => ['required', 'integer', 'distinct'],
                'events.*.marketId' => ['required', 'string', 'max:50'],
                'events.*.outcome' => ['required', 'string', 'max:50'],
                'events.*.odds' => ['required', 'between:0,99.99'],
            ],
            [
                'string' => 'incorrect:' . ucfirst('attribute'),
                'max' => 'incorrect:' . ucfirst('attribute'),
                'unique' => ':attributeAlreadyUsed',
                'required' => ':attributeIsRequired',
                'events.*.required' => ':attribute is required',
                'distinct' => 'eventId should be unique',
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

        $events = $request->events;
        $betPercentage = $request['betPercentage'];
        $betAmount = round ($user->balance * $betPercentage / 100, 2 );


        $prediction = Prediction::create([
            'userId' => $user->id,
            'betPercentage' => $betPercentage,
            'betAmount' => $betAmount,
            'isApproved' => false,
            'description' => $request['description'],
            'odds' => 1
        ]);

        $user->balance -= $betAmount;
        $user->save();

        foreach ($events as $event) {
            PredictionEvents::create([
                'predictionId' => $prediction->id,
                'eventId' => $event['eventId'],
                'marketId' => $event['marketId'],
                'outcome' => $event['outcome'],
                'odds' => $event['odds'],
            ]);

            $prediction->odds *= $event['odds'];
        }

        $prediction->save();

        return response()->json([
            'status' => 'success',
            'predictions' => $prediction
        ], 200);
    }

    /**
     * @OA\Get(
     *      path="/predictions",
     *      operationId="getPrediction",
     *      tags={"Prediction"},
     *      summary="getPrediction",
     *      description="get prediction",
     *      security={
     *        {"bearerAuth": {}},
     *      },
     *     @OA\Parameter(
     *          name="sort",
     *          description="sorting",
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              enum={"latest", "betAmount"}
     *          )
     *     ),
     *     @OA\Parameter(
     *         name="eventId",
     *         description="user id to view all his forecasts",
     *         in="query",
     *     ),
     *     @OA\Parameter(
     *         name="dateFrom",
     *         description="Only predictions added after this date",
     *         in="query",
     *     ),
     *     @OA\Parameter(
     *         name="dateTo",
     *         description="Only predictions added before this date",
     *         in="query",
     *     ),
     *     @OA\Parameter(
     *          name="perPage",
     *          description="The number of predictions issued at a time per page",
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         description="page number",
     *         in="query",
     *     ),
     *     @OA\Parameter(
     *          name="isApproved",
     *          description="show predictions by flag isApproved",
     *          in="query",
     *          @OA\Schema(
     *              enum={"true", "false"}
     *          )
     *
     *     ),
     *     @OA\Parameter(
     *          name="takeForModeration",
     *          description="If true, all returned predictions moderated",
     *          in="query",
     *          @OA\Schema(
     *              enum={"true", "false"}
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="moderatorId",
     *          description="returns predictions that were taken by a particular moderator",
     *          in="query",
     *     ),
     *     @OA\Response(
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
     *                      property="prediction",
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
    public function getPredictions(Request $request)
    {
        $filterParams = $request->all();
        $user = Auth::user();
        $haveAccess = $user->type === 'admin' || $user->type === 'moderator' ? true : false;

        $whereArr = [];
        $minusCount = 0;
        $moderationMaxCount = 50;
        $perPage = key_exists('perPage', $filterParams) ? $filterParams['perPage'] : 50;
        $page = key_exists('page', $filterParams) ? $filterParams['page'] : 1;
        $takeForModeration = key_exists('takeForModeration', $filterParams) ? $filterParams['takeForModeration'] : null;
        $sortField = key_exists('sort', $filterParams) && $filterParams['sort'] === 'betAmount' ? 'betAmount' : 'created_at';

        key_exists('userId', $filterParams) ? array_push($whereArr, ['userId', '=', $filterParams['userId']]) : null;
        key_exists('dateFrom', $filterParams) ? array_push($whereArr, ['created_at', '>', $filterParams['dateFrom']]) : null;
        key_exists('dateTo', $filterParams) ? array_push($whereArr, ['created_at', '<', $filterParams['dateTo']]) : null;

        $predictions = Prediction::where($whereArr);

        if( key_exists('eventId', $filterParams) ){
            $predictions->join('prediction_events as e', function ($join) use ($filterParams){
                $join->on('predictions.id', '=', 'e.predictionId')
                    ->where('e.eventId', '=', $filterParams['eventId']);
            })
            ->select("predictions.*");
        }

        if ($haveAccess) {

            $whereArr = [];
            key_exists('moderatorId', $filterParams) ? array_push($whereArr, [ 'moderatorId', '=', $filterParams['moderatorId']]) : null;
            key_exists('isApproved', $filterParams) ? array_push($whereArr, [ 'isApproved', '=', $filterParams['isApproved'] ]) : null;
            key_exists('isApproved', $filterParams) && $filterParams['isApproved'] == 'false'  ? array_push($whereArr, [ 'moderatorId', '=', $user->id ]) : null;

            $predictions = $predictions->where($whereArr);

            if ($takeForModeration) {
                $perPage = $perPage > $moderationMaxCount ? $moderationMaxCount : $perPage;
                $minusCount = Prediction::where(['moderatorId' => $user->id])->count();
                $predictions = $predictions->where('moderatorId', '<>', $user->id)
                    ->orWhere('moderatorId', '=', null);
            }
        }

        $predictions = $predictions->orderBy($sortField, 'desc')
            ->with('predictionEvents')
            ->paginate($perPage - $minusCount, ['*'], 'page', $page);

        if ($haveAccess) {
            if ($takeForModeration) {
                foreach ($predictions as $prediction) {
                    $prediction->moderatorId = $user->id;
                    $prediction->onModeration = gmdate("Y/m/d H:i:s");
                    $prediction->save();
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'predictions' => $predictions->toArray()
        ], 200);
    }

    /**
     * @OA\Get(
     *      path="/predictions/{id}",
     *      operationId="getPrediction",
     *      tags={"Prediction"},
     *      summary="getPrediction",
     *      description="get prediction by id",
     *      security={
     *        {"bearerAuth": {}},
     *      },
     *      @OA\Parameter(
     *          name="id",
     *          description="prediction id",
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
     *                      property="prediction",
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
    public function getPredictionById($id)
    {
        $predictions = Prediction::find($id);

        return response()->json([
            'status' => 'success',
            'predictions' => $predictions
        ], 200);
    }

    /**
     * @OA\Put(
     *      path="/predictions/approve/{id}",
     *      operationId="approvePrediction",
     *      tags={"Prediction"},
     *      summary="approvePrediction",
     *      description="approve prediction",
     *      security={
     *        {"bearerAuth": {}},
     *      },
     *      @OA\Parameter(
     *          name="id",
     *          description="prediction id",
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
     *                      property="prediction",
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
    public function approvePrediction($id)
    {
        $user = Auth::user();
        if ($user->type === 'admin' || $user->type === 'moderator') {

            $prediction = Prediction::find($id);

            if (!$prediction) {
                return response()->json([
                    'status' => 'fail',
                    'errorCode' => 'notFound'
                ], 400);
            }

            $prediction->isApproved = true;
            $prediction->save();

            return response()->json([
                'status' => 'success',
                'prediction' => $prediction
            ], 200);
        }

        return response()->json([
            'status' => 'fail',
            'errorCode' => 'accessDenied'
        ], 400);
    }

    /**
     * @OA\Put(
     *      path="/predictions/approve",
     *      operationId="approveSomePrediction",
     *      tags={"Prediction"},
     *      summary="approveSomePrediction",
     *      description="approve some prediction",
     *      security={
     *        {"bearerAuth": {}},
     *      },
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="list",
     *                      description="list prediction id",
     *                      type="array",
     *                      @OA\Items(
     *                          type="integer"
     *                      )
     *                  ),
     *              ),
     *          ),
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
     *                      property="updatedList",
     *                      type="array",
     *                      @OA\Items()
     *                  ),
     *                  @OA\Property(
     *                      property="failedsList",
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
    public function approveSomePredictions(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'list' => ['required', 'array']
            ],
            [
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

        $user = Auth::user();

        if ($user->type === 'admin' || $user->type === 'moderator') {

            $listId = $request->list;
            $updatedList = [];
            $failedList = [];
            foreach ($listId as $id) {

                $prediction = Prediction::find($id);

                if ($prediction) {

                    $prediction->isApproved = true;
                    $prediction->save();

                    array_push($updatedList, $prediction);

                } else {

                    array_push($failedList, [
                        'id' => $id,
                        'errorCode' => 'notFound'
                    ]);

                }

            }
            return response()->json([
                'status' => 'success',
                'updatedList' => $updatedList,
                'failedList' => $failedList
            ], 200);
        }

        return response()->json([
            'status' => 'fail',
            'errorCode' => 'accessDenied'
        ], 400);
    }

}
