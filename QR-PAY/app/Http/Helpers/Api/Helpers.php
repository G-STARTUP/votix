<?php

namespace App\Http\Helpers\Api;

class Helpers
{
    public static function success($data = [], $messages = [], $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'messages' => $messages,
        ], $code);
    }

    public static function error($messages = [], $code = 422)
    {
        return response()->json([
            'status' => 'error',
            'messages' => $messages,
        ], $code);
    }

    public static function onlysuccess($messages = [], $code = 200)
    {
        return self::success([], $messages, $code);
    }
}
