<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use stdClass;

use App\Helpers\LightControllerHelper;

abstract class Controller
{
    use LightControllerHelper;
    // // =========================>
    // // ## Get Params
    // // =========================>
    // public function getParams(Request $request)
    // {
    //     return [
    //         'sortDirection' => $request->get('sortDirection', 'DESC'),
    //         'sortBy'        => $request->get('sortBy', 'created_at'),
    //         'paginate'      => $request->get('paginate', 10),
    //         'filter'        => $request->get('filter', null),
    //         'search'        => $request->get('search', null),
    //     ];
    // }

    // // =========================>
    // // ## Validation
    // // =========================>
    // public function validation(
    //     array $request, //? http request body
    //     array $rules    //? validator rules
    // ) {
    //     $validate = Validator::make($request, $rules);

    //     if ($validate->fails()) {
    //         response()->json([
    //             'message' => "Error: Unprocessable Entity!",
    //             'errors' => $validate->errors(),
    //         ], 422)->throwResponse();
    //     }
    // }

    // // =========================>
    // // ## Response Error Handler
    // // =========================>
    // public function responseError(
    //     $error,                    // can be string or Throwable
    //     string|null $section = null,
    //     string|null $message = null,
    // ) {
    //     // Extract message safely if $error is an Exception or Throwable
    //     if ($error instanceof \Throwable) {
    //         $errorMessage = $error->getMessage();
    //         $errorTrace = $error->getTraceAsString();
    //     } else {
    //         $errorMessage = (string) $error;
    //         $errorTrace = null;
    //     }

    //     if (env('APP_DEBUG')) {
    //         response()->json([
    //             'message' => $message ?? "Error: Server Side Having Problem!",
    //             'error' => $errorMessage ?? 'unknown',
    //             'trace' => $errorTrace,
    //             'section' => $section ?? 'unknown',
    //         ], 500)->throwResponse();
    //     } else {
    //         response()->json([
    //             'message' => $message ?? "Error: Server Side Having Problem!",
    //         ], 500)->throwResponse();
    //     }
    // }

    // // =========================>
    // // ## Response Data Handler
    // // =========================>
    // public function responseData(
    //     array $data,
    //     int|null $totalRow = null,
    //     string|null $message = null,
    //     array|null $columns = null,
    // ) {
    //     return response()->json([
    //         'message' => $message ?? (count($data) ? 'Success' : 'Empty data'),
    //         'data' => $data ?? [],
    //         'total_row' => $totalRow ?? null,
    //         'columns' => $columns ?? null,
    //     ], count($data) ? 200 : 206);
    // }


    // // =========================>
    // // ## Response Saved Handler
    // // =========================>
    // public function responseSaved(
    //     array|stdClass $data,
    //     string|null $message = null,
    // ) {
    //     return response()->json([
    //         'message' => $message ?? 'Success',
    //         'data' => $data ?? [],
    //     ], 201);
    // }
    // // =========================>
    // // ## Upload file
    // // =========================>
    // public function uploadFile(
    //     \Illuminate\Http\UploadedFile $file,  //? file
    //     string $folder = ''                   //? storage folder name
    // ) {
    //     return Storage::disk('private')->put($folder, $file);
    // }

    // // =========================>
    // // ## Delete file
    // // =========================>
    // public function deleteFile(
    //     string $path  //? path to file
    // ) {
    //     if (Storage::disk('private')->exists($path)) {
    //         Storage::disk('private')->delete($path);
    //     }
    // }

    // // =========================>
    // // ## Response file
    // // =========================>
    // public function responseFile(
    //     string $path  //? path to file
    // ) {
    //     $file_path = Storage::disk('private') . $path;

    //     if (Storage::disk('private')->exists($path)) {
    //         return response()->file($file_path);
    //     }

    //     return response(['message' => 'File not found'], 404);
    // }
}
