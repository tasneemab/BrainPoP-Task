<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Period;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Period\StorePeriodRequest;
use App\Http\Requests\Period\GetPeriodRequest;
use App\Http\Requests\Period\UpdatePeriodRequest;
use App\Http\Requests\Period\DeletePeriodRequest;


class PeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \App\Http\Requests\Period\GetPeriodRequest $request (Authoization)
     * @return \Illuminate\Http\Response
     */
    public function index(GetPeriodRequest $request)
    {
        return Period::with('teacher', 'students')->get()->toJson();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Period\StorePeriodRequest $request (Authoization & Validation)
     * @return \Illuminate\Http\Response
     */
    public function store(StorePeriodRequest $request)
    {
      try {
        $record = new Period( $request->all() );
        $record->save();
        return response($record, 201);
       } catch (\Exception $e) {
          Log::error($e);
          /**in case that an error occurred before saving the new record we need to
          * rollback the id tp the current max id that we have before creating the
          * new record.
          **/
          DB::statement('ALTER TABLE periods AUTO_INCREMENT=1');
          return response('Error', 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Http\Requests\Period\GetPeriodRequest $request (Authoization)
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(GetPeriodRequest $request, $id)
    {
        return Period::with('teacher', 'students')->findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Period\UpdatePeriodRequest $request (Authoization && validation)
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePeriodRequest $request, $id)
    {
      $record = Period::findOrFail($id);
      try {
        $record->update( $request->all() );
        return response($record);
      } catch (\Exception $e) {
        Log::error($e);
        return response('Error', 500);
      }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Requests\Period\DeletePeriodRequest $request (Authoization)
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeletePeriodRequest $request, $id)
    {
      $record = Period::findOrFail($id);
      $temp_timestamp = $record->updated_at; //inorder to save the last time the record have been updated before the deletion.
      $result = $record->delete();
      $record->students()->detach(); //remove assosiated studens in the given peiod
      if ($result) {
      $record['updated_at'] = $temp_timestamp; //returning the last time the record has been updated befor the deletion.
      $result = $record->save();
      }

      return response(204);
    }
}
