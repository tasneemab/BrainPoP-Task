<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use App\Models\Period;
use App\Models\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;


class StudentController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
      return Student::with('periods')->get()->toJson();
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    try {
      $record = new Student( $request->all() );
      $record->password = Hash::make($request->password);
      $record->save();

      $record->periods()->attach($request->periods);
      //saves all the periods assosiated with student

      return response($record, 201);
     } catch (\Exception $e) {
        Log::error($e);
        /**in case that an error occurred before saving the new record we need to
        * rollback the id tp the current max id that we have before creating the
        * new record.
        **/
        DB::statement('ALTER TABLE students AUTO_INCREMENT=1');
        return response('Error', 500);
      }
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
      return Student::with('periods')->findOrFail($id);
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, $id)
  {
    $record = Student::findOrFail($id);
    try {
      $record->update( $request->all() );
      foreach ($request->input('periods', []) as $period) {
        if ( isset($period['id']) ) {
          $row = Period::findOrFail( $period['id'] );
          $row->update($period);
        }
        else {
          $row = new Period($period);
          $row->student_id = $record->id;
          $row->save();
        }
      }
      return response($record);
    } catch (\Exception $e) {
      Log::error($e);
      return response('Error', 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    $record = Student::findOrFail($id);
    $temp_timestamp = $record->updated_at; //inorder to save the last time the record have been updated before the deletion.
    $result = $record->delete();
    $record->periods()->detach();
    if ($result) {
    $record['updated_at'] = $temp_timestamp; //returning the last time the record has been updated befor the deletion.
    $result = $record->save();
    }

    return response(204);
  }

  /**
  * Authenticate the user.
  *
  * @param  \Illuminate\Http\Request $request login validate request
  * @return \Illuminate\Http\Response
  */
  public function login(Request $request) {
    $student = Student::where('username', $request->name)->first(); // fetch user
    if (!$user) {
      return response('invalid user', 401); // user doesn't exist
    }
    if (Hash::check($request->password, $student->password)) {
      return response()->json([
        'token' => $this->start_session($request, $student),
        'id' => $student->id,
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
