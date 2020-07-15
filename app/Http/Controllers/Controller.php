<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 *  @OA\Info(
 *      version="1.0.0",
 *      title="Api Documentation",
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 *  ),
 *  @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Swagger Api dynamic host server"
 *  )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="bearer",
 * )
 *
 *  @OA\Tag(
 *     name="Users",
 *     description="api users",
 *  )
 *  @OA\Tag(
 *     name="Account",
 *     description="api users",
 *  )
 *  @OA\Tag(
 *     name="Prediction",
 *     description="predictions",
 *  )
 */

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
