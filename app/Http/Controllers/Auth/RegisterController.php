<?php
namespace App\Http\Controllers\Auth;

use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'max:255',
            'email' => 'email|max:255|unique:users',
            'phone' => 'required|numeric'
        ]);
    }

    public function register(Request $request)
    {
        $validator = $this->validator($request->all());
        if ($validator->fails()) {
            return response($validator->errors(), 400);
        }

        $user = User::where('phone', $request->input('phone'))->first();

        $verify_code = rand(1000, 9999);

        $phones = [
            '380002345678',
            '380012345678',
            '380022345678',
            '380032345678',
            '380042345678',
            '380052345678',
            '380062345678',
            '380072345678',
            '380082345678',
            '380092345678'
        ];

        if (in_array($request->input('phone'), $phones)) {
            $verify_code = '9999';
        }

        if (!$user) {
            $user = User::create([
                'role' => User::ROLE_CLIENT,
                'phone' => $request->input('phone'),
                'password' => bcrypt(Str::random(20)),
                'verify_code' => $verify_code
            ]);
            event(new Registered($user));
        } else {
            $user->verify_code = $verify_code;
            $user->save();
        }

        if ($request->has('device_token')) {
            $user->refreshDeviceToken($request->input('device_token'), [
                'platform' => $request->input('platform')
            ]);
        }

        $this->_sendSms(
            $user->phone,
            trans('messages.verify_message', ['code' => $user->verify_code])
        );

        $response = ['success' => 'ok', 'phone' => $user->phone];
        if (env('APP_ENV') == 'local') {
            $response['verify_code'] = $user->verify_code;
        }

        return response()->json($response);
    }

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|numeric',
            'phone' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response($validator->errors(), 400);
        }

        $token = Str::random(40);
        try {
            $user = User::where('verify_code', $request->input('code'))
                ->where('phone', $request->input('phone'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response(['error' => ['User not found']], 400);
        }
        $user->api_token = $token;
        $user->verify_code = null;
        $user->save();
        return $user;
    }

    private function _sendSms($phone, $message)
    {
        $accountSid = env('TWILIO_ACCOUNT_SID');
        $authToken = env('TWILIO_AUTH_TOKEN');
        $twilioNumber = env('TWILIO_NUMBER');

        $client = new Client($accountSid, $authToken);

        $to = $phone;

        try {
            $client->messages->create('+' . $to, [
                "from" => $twilioNumber,
                "body" => $message
            ]);
            Log::info('Message sent to ' . $to . 'text: ' . $message);
        } catch (TwilioException $e) {
            Log::error(
                'Could not send SMS notification. Twilio replied with: ' . $e
            );
        }
    }
}
