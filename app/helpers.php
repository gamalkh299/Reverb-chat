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
