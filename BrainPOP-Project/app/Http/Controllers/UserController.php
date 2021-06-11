<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserController extends Controller
{
  /**
  * Authenticate the user.
  *
  * @param  \Illuminate\Http\Request $request login validate request
  * @return \Illuminate\Http\Response
  */
  public function login(Request $request) {
    $user = User::where('name', $request->name)->first(); // fetch user

    Log::info($request->name);
    if (!$user) {
      return response('invalid user', 401); // user doesn't exist
    }
    Log::info(Hash::make($request->password));
    if (Hash::check($request->password, $user->password)) {
      return response()->json([
        'token' => $this->start_session($request, $user),
        'id' => $user->id,
      ], 200);
    }
    else {
      return response('invalid password', 401); // password is not correct
    }
  }


  /**
   * start a custom session for the user (not Laravel default session).
   *
   * @param  Illuminate\Http\Request $request. incoming login request
   * @param  App\Models\User $user. authenticated user
   * @return string
   */
  private function start_session(Request $request, $user) {
    $session = Session::where('user_id', $user->id)->first(); // get user session
    $data = [
      'id' => Str::random(128),
      'user_id' => $user->id,
      'ip_address' => $request->ip(),
      'user_agent' => $request->userAgent(),
      'last_activity' => Carbon::now()->timestamp,
    ];

    // update user session, or create a new session
    if ($session) {
      $session->update($data);
    }
    else {
      $session = new Session($data);
      $session->save();
    }

    return $session->id;
  }
}
