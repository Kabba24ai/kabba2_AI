<?php

namespace App\Http\Requests\Api\Admin\V1\Orders;

use App\Enums\Orders\OrderMediaType;
use App\Http\Requests\ApiBaseFormRequest;

class UploadMediaRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_unique_id' => 'required|string|exists:orders,unique_id', // Required unique ID parameter
            'type' => 'required|string|in:' . implode(',', OrderMediaType::getValues()), // Enum validation for media type
            'order_product_unique_id' => 'nullable|required_if:type,delivery,pickup|exists:order_products,unique_id',
            'media' => 'required|array',
            'media.*' => 'file', // Each file validation (image or video)
            'side' => 'nullable|string|in:front,back', // New field for license side
            'license_expiry_date' => 'nullable|date|date_format:Y-m-d', // Optional date field for license
            'auto_inject_by' => 'nullable|integer|exists:users,id', // Optional field to specify who is uploading the media
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $files = $this->file('media', []);
            $side = $this->input('side');

            foreach ((array) $files as $file) {
                if ($type === OrderMediaType::LICENSE->value) {
                    // License: Images only
                    if (!$file->isValid() || !in_array($file->extension(), ['jpg', 'jpeg', 'png'])) {
                        $validator->errors()->add('media', 'License type only accepts JPG, JPEG, or PNG images.');
                    }
                    // License: Require side to be front or back
                    // if (is_null($side) || !in_array($side, ['front', 'back'])) {
                    //     $validator->errors()->add('side', 'License type requires side to be either front or back.');
                    // }
                } elseif (in_array($type, [OrderMediaType::DELIVERY->value, OrderMediaType::PICKUP->value])) {
                    // Delivery/Pickup: Images and videos
                    if (!$file->isValid() || !in_array($file->extension(), ['jpg', 'jpeg', 'png', 'mp4', 'mov', 'avi'])) {
                        $validator->errors()->add('media', 'Delivery/Pickup type only accepts images and videos.');
                    }
                }
            }
        });
    }

    /**
     * Get the body parameters for the request documentation.
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'order_unique_id' => [
                'description' => 'The unique ID of the order.',
                'example' => 'ORD123456',
                'type' => 'string',
            ],
            'type' => [
                'description' => 'The type of media being uploaded. Possible values: license, delivery, pickup.',
                'example' => 'license',
                'type' => 'string',
            ],
            'side' => [
                'description' => 'Side of the license image. Possible values: front, back. Required if type is license.',
                'example' => 'front',
                'type' => 'string',
            ],
            'order_product_unique_id' => [
                'description' => 'The unique ID of the order product, required if type is delivery or pickup.',
                'example' => '',
                'type' => 'string',
            ],
            'media[]' => [
                'description' => 'A file to upload (image or video).',
                'example' => 'file.jpg',
                'type' => 'file',
            ],
            'license_date' => [
                'description' => 'The date associated with the license, required if type is license.',
                'example' => '2024-01-01',
                'type' => 'string (date)',
            ],
            'auto_inject_by' => [
                'description' => 'Optional user ID to attribute the upload to, if different from the authenticated user.',
                'example' => 1,
                'type' => 'integer',
            ],
        ];
    }
}
