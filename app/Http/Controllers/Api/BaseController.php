<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     title="Kabba Api Documentation",
 *     version="1.0.0",
 *     description="Description of your API",
 * ),
 * @OA\Server(
 *     url="http://api.kabba.local/customer/v1",
 *     description="Admin API base URL"
 * ),
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin APIs documentation"
 * ),

 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="Error Response",
 *     description="Common structure for error responses",
 *     @OA\Property(property="error", type="string", example="Bad Request"),
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(property="status_code", type="integer", example=400)
 * )
 */
class BaseController
{

}
