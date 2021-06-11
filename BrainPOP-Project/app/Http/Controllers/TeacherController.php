<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Period;
use Illuminate\Support\Facades\Hash;
use App\Models\Session;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\Requests\Teacher\StoreTeacherRequest;
use App\Http\Requests\Teacher\GetTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use App\Http\Requests\Teacher\DeleteTeacherRequest;


class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Http\Requests\Teacher\GetTeacherRequest  $request (Authoization)
     * @return \Illuminate\Http\Response
     */
    public function index(GetTeacherRequest $request)
    {
        return Teacher::with('periods.students')->get()->toJson();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Teacher\StoreTeacherRequest  $request (Authoization && Validation)
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTeacherRequest $request)
    {
      try {
        $record = new Teacher( $request->all() );
        $record->password = Hash::make($request->password);
        $record->save();

        //saves all the periods assosiated with teacher
        foreach ($request->input('periods', []) as $period) {
            $row = new Period($period);
            $row->teacher_id = $record->id;
            $row->save();
          }
        return response($record, 201);
       } catch (\Exception $e) {
          Log::error($e);
          /**in case that an error occurred before saving the new record we need to
          * rollback the id tp the current max id that we have before creating the
          * new record.
          **/
          DB::statement('ALTER TABLE teachers AUTO_INCREMENT=1');
          return response('Error', 500);
        }
    }

    /**
     * Display the specified resource.
     * @param  \App\Http\Requests\Teacher\GetTeacherRequest  $request (Authoization)
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(GetTeacherRequest $request, $id)
    {
        return Teacher::with('periods.students')->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Teacher\UpdateTeacherRequest  $request (Authoization && Validation)
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTeacherRequest $request, $id)
    {
      $record = Teacher::findOrFail($id);
      try {
        $record->update( $request->all() );
        foreach ($request->input('periods', []) as $period) {
          if ( isset($period['id']) ) {
            $row = Period::findOrFail( $period['id'] );
            $row->update($period);
          }
          else {
            $row = new Period($period);
            $row->teacher_id = $record->id;
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
     * @param  \App\Http\Requests\Teacher\DeleteTeacherRequest  $request (Authoization)
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteTeacherRequest $request, $id)
    {
      $record = Teacher::findOrFail($id);
      $temp_timestamp = $record->updated_at; //inorder to save the last time the record have been updated before the deletion.
      $result = $record->delete();
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
      $teacher = Teacher::where('username', $request->name)->first(); // fetch user
      if (!$user) {
        return response('invalid user', 401); // user doesn't exist
      }
      if (Hash::check($request->password, $teacher->password)) {
        return response()->json([
          'token' => $this->start_session($request, $teacher),
          'id' => $teacher->id,
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
