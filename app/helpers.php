<?php

use App\Enum\ResponseMethodEnum;
use App\Models\Device;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;

use Illuminate\Database\Eloquent\Model;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\{
    Facades\DB,
    Facades\File as File,
    Facades\Http,
    Facades\Storage,
    Str
};


function uploadFile($files, $url = 'files', $key = 'file', $model = null)
{
    $dist = storage_path('app/public/' . $url);
    if ($url != 'images' && !File::isDirectory(storage_path('app/public/files/' . $url . "/"))) {
        File::makeDirectory(storage_path('app/public' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $url . DIRECTORY_SEPARATOR), 0777, true);
        $dist = storage_path('app/public' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $url . DIRECTORY_SEPARATOR);
    } elseif (File::isDirectory(storage_path('app/public/files/' . $url . "/"))) {
        $dist = storage_path('app/public/files/' . $url . "/");
    }
    $file = '';

    if (gettype($files) == 'array') {
        $file = [];
        foreach ($files as $new_file) {
            $file_name = time() . "___file_" . $new_file->getClientOriginalName();
            if ($new_file->move($dist, $file_name)) {
                $file[][$key] = $file_name;
            }
        }
    } else {
        $file = $files;
        $file_name = time() . "___file_" . $file->getClientOriginalName();
        if ($file->move($dist, $file_name)) {
            $file = $file_name;
        }
    }

    return $file;
}

function generalApiResponse(
    ResponseMethodEnum $method,
    $resource = null,
    $data_passed = null,
    $custom_message = null,
    $custom_status_msg = 'success',
    $custom_status = 200,
    $additional_data = null
) {
    return match ($method) {
        ResponseMethodEnum::CUSTOM_SINGLE => !is_null($additional_data) ? $resource::make($data_passed)->additional(['status' => $custom_status_msg, 'message' => $custom_message, 'additional_data' => $additional_data], $custom_status) : $resource::make($data_passed)->additional(['status' => $custom_status_msg, 'message' => $custom_message], $custom_status),

        ResponseMethodEnum::CUSTOM_COLLECTION => !is_null($additional_data) ? $resource::collection($data_passed)->additional(['status' => $custom_status_msg, 'message' => $custom_message, 'additional_data' => $additional_data], $custom_status) : $resource::collection($data_passed)->additional(['status' => $custom_status_msg, 'message' => $custom_message], $custom_status),

        ResponseMethodEnum::CUSTOM => !is_null($additional_data) ? response()->json(['status' => $custom_status_msg, 'data' => $data_passed, 'message' => $custom_message, 'additional_data' => $additional_data], $custom_status) : response()->json(['status' => $custom_status_msg, 'data' => $data_passed, 'message' => $custom_message], $custom_status),

        default => throw new InvalidArgumentException('Invalid response method'),
    };
}

function createSlug(Model $model, $title, $lang)
{
    $slug = Str::slug($title);

    // Check if the slug already exists in the table
    $isUnique = !$model::where('slug->' . $lang, $slug)->exists();

    // If the slug is not unique, append a number to make it unique
    if (!$isUnique) {
        $counter = 2;
        while (!$isUnique) {
            $newSlug = $slug . '-' . $counter;
            $isUnique = !$model::where('slug->' . $lang, $newSlug)->exists();
            $counter++;
        }
        $slug = $newSlug;
    }
    return $slug;
}

function isProduction()
{
    return $is_production = env('APP_ENV') == 'production' ?: false;
}

if (!function_exists('sendnotification')) {
    function sendnotification($title, $body, $divces_ids)
    {
        $success = false;

        $devices = Device::whereIn('id', $divces_ids)->get();
        foreach ($devices as $device) {
            $data = [
                "notification" => [
                    "body" => $body,
                    "title" => $title,
                ],
                "priority" => "high",
                "data" => [
                    "click_action" => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                "to" => $device->device_token,
            ];
            $https_check = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'AAAASwb2p44:APA91bHyO4iYkY0V3cRxw2ipOlTFdKtt2ZgrN0O4aaA6zWtLdznjiIh44V7wm4Mz2k2KACz5H25izyY8YlHKQxIcwJnefhZP89pbhIDid9hRxToJgM4JVwomHzMvmMv8vFL8nzVn5ZuD',
            ])->withBody(json_encode($data), 'application/json')->post('https://fcm.googleapis.com/fcm/send');
            if ($https_check->status() == 200) {
                $success = true;
            }
        }

        return $success;
    }

    // function calculateAppProfit($order)
    // {
    //     $appProfit = (float) nova_get_setting('app_profit', 0);
    //     $appProfitAmount = ($order->sub_total * $appProfit) / 100;
    //     return $order->sub_total + $appProfitAmount;
    // }

    function calculateTotalPriceService($price_service)
    {
        $appProfitAmount = (float) nova_get_setting('app_profit', 0);
        return $price_service + ($price_service * $appProfitAmount / 100);
    }

    function calculateAppProfit($order)
    {
        return $order->sub_total ?? 0;
    }
    function runSeeder($plural, $singular, $model, $has_img = false, $has_file = false, $img_extension = '.svg', $file_extension = null, $image_field = 'image', $file_field = 'file'): void
    {
    //    dd($plural);
        // Delete plural storage directory
        Storage::disk('public')->deleteDirectory('images/' . $plural);
        Storage::disk('public')->deleteDirectory('files/' . $plural);

        // Delete old data
        DB::table($plural)->delete();

        // Fetch data from json file
        $$plural = json_decode(file_get_contents(database_path('data/' . $plural . '.json')), true);

        foreach ($$plural as $$singular) {
            $data = $$singular;

            if ($has_img) {
                $img_name = str_replace($img_extension, '', str_replace('assets/images/' . $plural . '/', '', $$singular[$image_field]));

                ${$singular . '_img'} = new UploadedFile(public_path($$singular[$image_field]), $img_name);

                $img_data = [
                    $image_field => Storage::disk('public')->putFile('images/' . $plural, ${$singular . '_img'})
                ];

                $data = Arr::except($$singular, $image_field) + $img_data;
            }

            if($has_file) {
                $file_name = str_replace($file_extension, '', str_replace('assets/files/' . $plural, '', $$singular[$file_field]));

                ${$singular . '_file'} = new UploadedFile(public_path($$singular[$file_field]), $file_name);

                $file_data = [
                    $file_field => Storage::disk('public')->putFile('files/' . $plural, ${$singular . '_file'})
                ];

                $data = Arr::except($data, $file_field) + $file_data;
            }

            $model::create($data);
        }
    }

    function runSeederV2($plural, $singular, $model, $has_img = false, $has_file = false, $img_extension = '.png', $file_extension = null, $image_fields = ['image'], $file_fields = ['file'], $observer = true): void
{
    // Delete plural storage directory
    Storage::disk('public')->deleteDirectory('images/' . $plural);
    Storage::disk('public')->deleteDirectory('files/' . $plural);

    // Delete old data
    DB::table($plural)->delete();

    // Fetch data from json file
    $$plural = json_decode(file_get_contents(database_path('data/' . $plural . '.json')), true);
    foreach ($$plural as $$singular) {
        $data = $$singular;

        if ($has_img) {
            $img_data = [];
            $keys = [];
            foreach ($image_fields as $key => $image_field) {
                $img_name = str_replace($img_extension, '', str_replace('assets/images/' . $plural . '/', '', $$singular[$image_field]));

                ${$singular . '_img'} = new UploadedFile(public_path($$singular[$image_field]), $img_name);
                $img_data += [
                    $image_field => Storage::disk('public')->putFile('images/' . $plural, ${$singular . '_img'})
                ];
                $keys += [$key];
            }
            $data = Arr::except($$singular, $image_fields) + $img_data;
        }
        if ($has_file) {
            $file_data = [];
            $keys = [];
            foreach ($file_fields as $key => $file_field) {
                $file_name = str_replace($file_extension, '', str_replace('assets/files/' . $plural, '', $$singular[$file_field]));

                ${$singular . '_file'} = new UploadedFile(public_path($$singular[$file_field]), $file_name);

                $file_data += [
                    $file_field => Storage::disk('public')->putFile('files/' . $plural, ${$singular . '_file'})
                ];
                $keys += [$key];
            }
            $data = Arr::except($data, $file_fields) + $file_data;
        }
        if($observer) {
            $model::create($data);
        } else {
            $dispatcher = $model::getEventDispatcher();
            $model::unsetEventDispatcher();
            $model::create($data);
            $model::setEventDispatcher($dispatcher);
        }
    }
}

}

    function nova_get_image($key)
    {
        return nova_get_setting($key) ? Storage::disk('public')->url(nova_get_setting($key)) : null;
    }

    function otp_code_generator()
    {
        $length = config('otp.length');
        return
        config('otp.apply_static_code')
            ? config('otp.static_code')
            : str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    function otp_expiry_time()
    {
        return now()->addMinutes(config('otp.expiry'));
    }

