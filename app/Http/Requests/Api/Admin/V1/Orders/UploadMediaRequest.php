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
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $files = $this->file('media', []);

            foreach ((array) $files as $file) {
                if ($type === OrderMediaType::LICENSE->value) {
                    // License: Images only
                    if (!$file->isValid() || !in_array($file->extension(), ['jpg', 'jpeg', 'png'])) {
                        $validator->errors()->add('media', 'License type only accepts JPG, JPEG, or PNG images.');
                    }
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
        ];
    }
}
