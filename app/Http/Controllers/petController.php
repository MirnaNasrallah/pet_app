<?php

namespace App\Http\Controllers;

use App\Events\SymptomLogged;
use App\Models\breed;
use App\Models\calculations;
use App\Models\DailyCheckIn;
use App\Models\dailyexcercise;
use App\Models\foodActivity;
use App\Models\medications;
use App\Models\Message;
use App\Models\pet;
use App\Models\PetHealthData;
use App\Models\petoptionaldata;
use App\Models\Symptom;
use App\Models\User;
use App\Notifications\symptomLoggedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class petController extends Controller
{

    //--------------CRUD PETS DATA ---------------//
    public function create(Request $request)
    {
        $id = auth('sanctum')->user()->id;
        $data = $request->all();
        // $data['spayedOr'] = filter_var($data['spayedOr'], FILTER_VALIDATE_BOOLEAN);
        // $data['mixed'] = filter_var($data['mixed'], FILTER_VALIDATE_BOOLEAN);

        $validator = Validator::make(
            $data,
            [
                "PetName" => "required|min:3",
                "specie" => ['required', Rule::in(['dog', 'cat'])],
                "birthday" => "nullable|date",
                "PetYears" => "nullable|numeric",
                "PetMonths" => "nullable|numeric",
                "weight" => "required|numeric",
                "gender" => ['required', Rule::in(['male', 'female'])],
                "spayedOr" => "required|boolean",
                "image_path" => "nullable|image|mimes:png,jpg,jpeg",
                "mixed" => "required|boolean",
                "breedOne" => "required|string",
                "breedTwo" => "nullable|string",
                "BodyConditionScore" => "required|numeric",
                "food" => "required|array",
                "medications" => "required|array",
                "conditions" => "required|array",
                "worklevel" => ['required', Rule::in(['light', 'heavy'])],
                "watchingweight" => "required|boolean",
                "losingweight" => "required|boolean",

            ]
        );
        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $data["user_id"] = auth('sanctum')->user()->id;
            if ($request->hasFile('image_path')) {
                $FinalName = hexdec(uniqid()) . '.' . $request->image_path->extension();
                $request->image_path->move(public_path('PetImages'), $FinalName);
                $data['image_path'] = $FinalName;
            }
            // calculations
            // rer
            $der = 0;
            $rer = 0;
            if ($request->has('birthday')) {
                $dateOfBirth = $data['birthday'];
                $today = date("Y-m-d");
                $diff = date_diff(date_create($dateOfBirth), date_create($today));
                $age = $diff->format('%y');
            } else {
                $age = ($data['PetYears'] . $data['PetMonths']) * 0.1;
            }

            if ($data["weight"] < 2 || $data["weight"] > 45) {
                $rer = pow(($data["weight"] * 70), (3 / 4));
            } else {
                $rer = ($data["weight"] * 30) + 70;
            }
            if ($data['specie'] == "dog" && $age > 1 && $data['worklevel'] == "light") {
                $der = 2 * $rer;
            }
            if ($data['specie'] == "dog" && $age > 1 && $data['worklevel'] == "heavy") {
                $der = 4 * $rer;
            }
            if ($age < 1) {
                $der = 2.5 * $rer;
            }
            if ($age > 1 && $data['spayedOr'] == 1) {
                $der = 1.2 * $rer;
            }
            if ($age > 1 && $data['spayedOr'] == 0) {
                $der = 1.4 * $rer;
            }
            if ($age > 1 && $data['watchingweight'] == 1) {
                $der = 1 * $rer;
            }
            if ($age > 1 && $data['losingweight'] == 1) {
                $der = 0.8 * $rer;
            }

            $startCbd = 0.5 * $data['weight'];
            $maxCbd = 2 * $data["weight"];

            $op1 = pet::create($data);

            if (isset($op1->id)) {
                $enteredFood = array();
                $enteredMedication = array();
                $enteredCondition = array();
                $pet_id = $op1->id;
                $op = breed::create([
                    "mixed" => $data["mixed"],
                    "breedOne" => $data["breedOne"],
                    "breedTwo" => $data["breedTwo"],
                    "pet_id" => $pet_id,
                ]);
                $cal = calculations::create([
                    "der" => $der,
                    "rer" => $rer,
                    "startcbd" => $startCbd,
                    "maxcbd" => $maxCbd,
                    "petId" => $pet_id,
                ]);
                foreach ($data["food"] as $value) {
                    $foodArray = array("PetO_id" => $pet_id, "foodName" => $value[0], 'unit' => $value[1], 'CaloriesPerUnit' => $value[2], 'noOfUnits' => $value[2]);
                    array_push($enteredFood, $foodArray);
                };
                foreach ($enteredFood as $value) {
                    $second = petoptionaldata::create($value);
                }

                foreach ($data["medications"] as $value) {
                    $medicationArray = array("medicationName" => $value[0], "frequency" => $value[1], "petId" => $pet_id);
                    array_push($enteredMedication, $medicationArray);
                };
                foreach ($enteredMedication as $value) {
                    $op3 = medications::create([
                        'medicationName' => $value["medicationName"],
                        'frequency' => $value['frequency'],
                        'petId' => $value['petId'],
                    ]);
                }
                foreach ($data["conditions"] as $value) {
                    $conditionsArray = array("PetCondition" => $value[0], "PetConditionState" => $value[1], "PetH_id" => $pet_id);
                    array_push($enteredCondition, $conditionsArray);
                };
                foreach ($enteredCondition as $value) {
                    $op4 = PetHealthData::create([
                        'PetCondition' => $value["PetCondition"],
                        'PetConditionState' => $value["PetConditionState"],
                        'PetH_id' => $value['PetH_id'],
                    ]);
                }
                if ($op4) {
                    // $Pet = pet::where('breeds.pet_id', $op->pet_id)->join('breeds', 'breeds.pet_id', '=', 'petinfo.id')->select('petinfo.id as PetID', 'petinfo.*', 'breeds.id as BreedID', 'breeds.*')->get()->makeHidden(['pet_id', 'id', 'created_at', 'updated_at']);
                    // $Pet = pet::where('petinfo.id', $op->pet_id)->join('breeds', 'breeds.pet_id', '=', 'petinfo.id')->join('petoptionaldata', 'petoptionaldata.PetO_id', '=', 'petinfo.id')->join('medications', 'medications.petId', '=', 'petinfo.id')->join('pethealthcondition', 'pethealthcondition.PetH_id', '=', 'petinfo.id')->select('petinfo.id as PetID', 'petinfo.*', 'breeds.id as BreedID', 'breeds.*', 'petoptionaldata.id as optionalDataID', 'petoptionaldata.*', 'medications.id as medicationsID', 'medications.*', 'pethealthcondition.id as PetHealthDataID', 'pethealthcondition.*')->get()->makeHidden(['pet_id', 'id', 'created_at', 'updated_at', "petId", 'PetH_id']);
                    $Pet = pet::where('petinfo.id', $op->pet_id)->join('breeds', 'breeds.pet_id', '=', 'petinfo.id')->select('petinfo.*', 'breeds.*', 'breeds.id as BreedID')->get()->makeHidden(['id', 'created_at', 'updated_at', "petId", 'PetH_id']);
                }
                $message = 'Pet Created';
                foreach ($Pet as $value) {
                    $value->image_path = url('PetImages/' . $value->image_path);
                    $value->food = pet::where('petinfo.id', $value->pet_id)->join('petoptionaldata', 'petoptionaldata.PetO_id', '=', 'petinfo.id')->select('petoptionaldata.id as foodID', 'petoptionaldata.*')->get()->makeHidden(['id', 'PetO_id']);
                    $value->medications = pet::where('petinfo.id', $value->pet_id)->join('medications', 'medications.petId', '=', 'petinfo.id')->select('medications.id as medicationsID', 'medications.*')->get()->makeHidden(['id', 'petId']);
                    $value->conditions = pet::where('petinfo.id', $value->pet_id)->join('pethealthcondition', 'pethealthcondition.PetH_id', '=', 'petinfo.id')->select('pethealthcondition.id as conditionsID', 'pethealthcondition.*')->get()->makeHidden(['id', 'PetH_id']);
                }
                // $medications = medications::where('petId', $pet_id)->get();
                // $foodActivity = petoptionaldata::where('PetO_id', $pet_id)->get()
                // $exercises = dailyexcercise::where('petE_id', $pet_id)->get();
                // $dailyCheckIn = DailyCheckIn::where('pet_id', $pet_id)->get();

                return response()->json(["status" => " 201", "message" => $message, "data" => ['createdPet' => $Pet]], 201);
            } else {
                $message = 'error try again';
                return response()->json(["status" => "500", "message" => $message], 500);
            }
        }
    }

    public function PetInfo(Request $request)
    {
        $id = auth('sanctum')->user()->id;

        // $Pet = User::where('users.id', $id)->join('petinfo', 'users.id', '=', 'petinfo.user_id')->join('breeds', 'breeds.pet_id', '=', 'petinfo.id')->select('petinfo.id as PetID', 'petinfo.*', 'breeds.id as BreedID', 'breeds.*')->get()->makeHidden(['pet_id', 'id', 'created_at', 'updated_at']);
        // $Pet = User::where('users.id', $id)->join('petinfo', 'users.id', '=', 'petinfo.user_id')->select('petinfo.id as PetID', 'petinfo.*')->join('breeds', 'breeds.pet_id', '=','petinfo.id')->select('breeds.id as BreedID', 'breeds.*')->get();
        $Pet = User::where('users.id', $id)->join('petinfo', 'users.id', '=', 'petinfo.user_id')->join('breeds', 'breeds.pet_id', '=', 'petinfo.id')->select('petinfo.*', 'breeds.*', 'breeds.id as BreedID')->get()->makeHidden(['id', 'created_at', 'updated_at', "petId", 'PetH_id']);
        foreach ($Pet as $key => $value) {
            $Pet[$key]->image_path = url('PetImages/' . $Pet[$key]->image_path);
            $value->food = User::where('users.id', $id)->where('petinfo.id', $value->pet_id)->join('petinfo', 'users.id', '=', 'petinfo.user_id')->join('petoptionaldata', 'petoptionaldata.PetO_id', '=', 'petinfo.id')->select('petoptionaldata.id as foodID', 'petoptionaldata.*')->get()->makeHidden(['id', 'PetO_id', 'created_at', 'updated_at']);
            // foreach ($value->food as $value1) {
            //     $value1->foodName = petoptionaldata::where("foodname.optionalId", $value1["foodID"])->join("foodname", "foodname.optionalId", "=", "petoptionaldata.id")->select("foodname.*", "foodname.id as foodNameId")->get()->makeHidden(['id', 'pet_id']);
            // }
            $value->medications = User::where('users.id', $id)->where('petinfo.id', $value->pet_id)->join('petinfo', 'users.id', '=', 'petinfo.user_id')->join('medications', 'medications.petId', '=', 'petinfo.id')->select('medications.id as medicationsID', 'medications.*')->get()->makeHidden(['id', 'petId', 'created_at', 'updated_at']);
            $value->conditions = User::where('users.id', $id)->where('petinfo.id', $value->pet_id)->join('petinfo', 'users.id', '=', 'petinfo.user_id')->join('pethealthcondition', 'pethealthcondition.PetH_id', '=', 'petinfo.id')->select('pethealthcondition.id as conditionsID', 'pethealthcondition.*')->get()->makeHidden(['id', 'PetH_id', 'created_at', 'updated_at']);
        }
        return response()->json(["status" => "200", "message" => "Pet data", "data" => ["pets" => $Pet]], 200);
    }

    public function UpdatePetInfo(Request $request, $pet_id)
    {
        // there are also here PetID  BreedID  user_id
        $data = $request->all();
        // $data['spayedOr'] = filter_var($data['spayedOr'], FILTER_VALIDATE_BOOLEAN);
        // $data['mixed'] = filter_var($data['mixed'], FILTER_VALIDATE_BOOLEAN);
        $Pet = pet::find($pet_id);
        // $breed=breed::find($data['BreedID']);
        $enteredFood = array();
        $enteredMedication = array();
        $enteredCondition = array();
        $validator = Validator::make(
            $data,
            [
                "PetName" => "nullable|min:3",
                "specie" => ['nullable', Rule::in(['dog', 'cat'])],
                "birthday" => "nullable|date",
                "PetYears" => "nullable|numeric",
                "PetMonths" => "nullable|numeric",
                "weight" => "nullable|numeric",
                "gender" => ['nullable', Rule::in(['male', 'female'])],
                "spayedOr" => "nullable|boolean",
                "image_path" => "nullable|image|mimes:png,jpg,jpeg",
                "mixed" => "nullable|boolean",
                "breedOne" => "nullable|string",
                "breedTwo" => "nullable|string",
                "BodyConditionScore" => "nullable|numeric",
                "food" => "nullable|array",

                "medications" => "required|array",
                "conditions" => "required|array",
                "worklevel" => ['nullable', Rule::in(['light', 'heavy'])],
                "watchingweight" => "nullable|boolean",
                "losingweight" => "nullable|boolean",
            ]
        );

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            if ($request->hasFile('image_path')) {

                $FinalName = hexdec(uniqid()) . '.' . $request->image_path->extension();

                if ($request->image_path->move(public_path('PetImages'), $FinalName)) {
                    $file = public_path('PetImages/' . $Pet['image_path']);
                    if (file_exists($file) and $Pet['image_path'] !== null) {
                        unlink(public_path('PetImages/' . $Pet['image_path']));
                    }
                }
            } else {
                $FinalName = $Pet['image_path'];
            }
            $data['image_path'] = $FinalName;
            $pet = pet::find($pet_id);

            $op = pet::where('id', $pet_id)->update([
                "PetName" => $data['PetName'] ?? $pet->PetName,
                "specie" => $data['specie'] ?? $pet->specie,
                "birthday" => $data['birthday'] ?? $pet->birthday,
                "PetYears" => $data['PetYears'] ?? $pet->PetYears,
                "PetMonths" => $data['PetMonths'] ?? $pet->PetMonths,
                "weight" => $data['weight'] ?? $pet->weight,
                "gender" => $data['gender'] ?? $pet->gender,
                "image_path" => $data['image_path'] ?? $pet->image_path,
                "spayedOr" => $data['spayedOr'] ?? $pet->spayedOr,
                "BodyConditionScore" => $data['BodyConditionScore'] ?? $pet->BodyConditionScore,
                "worklevel" => $data['worklevel'] ?? $pet->worklevel,
                "watchingweight" => $data['watchingweight'] ?? $pet->watchingweight,
                "losingweight" => $data['losingweight'] ?? $pet->losingweight,
            ]);
            //update calculations

            if (isset($data['birthday']) && isset($data['PetYears']) && isset($data['PetMonths']) && isset($data["weight"]) && isset($data['specie']) && isset($data['worklevel']) && isset($data['watchingweight']) && isset($data['losingweight'])) {
                if ($request->has('birthday')) {
                    $dateOfBirth = $data['birthday'];
                    $today = date("Y-m-d");
                    $diff = date_diff(date_create($dateOfBirth), date_create($today));
                    $age = $diff->format('%y');
                } else {
                    $age = ($data['PetYears'] . $data['PetMonths']) * 0.1;
                }
                $der = 0;
                $rer = 0;
                if ($data["weight"] < 2 || $data["weight"] > 45) {
                    $rer = pow(($data["weight"] * 70), (3 / 4));
                } else {
                    $rer = ($data["weight"] * 30) + 70;
                }
                if ($data['specie'] == "dog" && $age > 1 && $data['worklevel'] == "light") {
                    $der = 2 * $rer;
                }
                if ($data['specie'] == "dog" && $age > 1 && $data['worklevel'] == "heavy") {
                    $der = 4 * $rer;
                }
                if ($age < 1) {
                    $der = 2.5 * $rer;
                }
                if ($age > 1 && $data['spayedOr'] == 1) {
                    $der = 1.2 * $rer;
                }
                if ($age > 1 && $data['spayedOr'] == 0) {
                    $der = 1.4 * $rer;
                }
                if ($age > 1 && $data['watchingweight'] == 1) {
                    $der = 1 * $rer;
                }
                if ($age > 1 && $data['losingweight'] == 1) {
                    $der = 0.8 * $rer;
                }

                $startCbd = 0.5 * $data['weight'];
                $maxCbd = 2 * $data["weight"];
            }
            $cal_old = calculations::where('petId', $pet_id)->first();
            $cal = calculations::where('petId', $pet_id)->update([
                "der" => $der ?? $cal_old->der,
                "rer" => $rer ?? $cal_old->rer,
                "startcbd" => $startCbd ?? $cal_old->startcbd,
                "maxcbd" => $maxCbd ?? $cal_old->maxcbd,
                "petId" => $pet_id,
            ]);
            //update breed
            $breed_id = breed::where('pet_id', $pet_id)->first();

            $OPBreed = breed::where('id', $breed_id)->update([
                "mixed" => $data["mixed"] ?? $breed_id->mixed,
                "breedOne" => $data["breedOne"] ?? $breed_id->breedOne,
                "breedTwo" => $data["breedTwo"] ?? $breed_id->breedTwo,
            ]);
            //update food`;
            if (isset($data["food"])) {
                foreach ($data["food"] as $value) {
                    $foodArray = array("PetO_id" => $pet_id, "foodName" => $value[0], 'unit' => $value[1], 'CaloriesPerUnit' => $value[2], 'noOfUnits' => $value[3], "foodID" => $value[4]);
                    array_push($enteredFood, $foodArray);
                };
                // return response()->json(["status" => "201", "message" =>$enteredFood], 201);
                foreach ($enteredFood as $value) {
                    $opMedications = petoptionaldata::updateOrCreate([
                        "id" => $value["foodID"],
                    ], [
                        'unit' => $value["unit"],
                        'foodName' => $value['foodName'],
                        'PetO_id' => $value['PetO_id'],
                        'CaloriesPerUnit' => $value['CaloriesPerUnit'],
                        'noOfUnits' => $value['noOfUnits'],
                    ]);
                }
            }
            //ID THE OPTIONAL DATA ITSELF
            // $peo =  petoptionaldata::find($value["foodID"]);
            // petoptionaldata::where('id', $value["foodID"])->first()->update([
            //     "foodName" => $value["foodName"] ?? $peo->foodName,
            //     "unit" => $value["unit"] ?? $peo->unit,
            //     "CaloriesPerUnit" => $value["CaloriesPerUnit"] ?? $peo->CaloriesPerUnit,
            //     "noOfUnits" => $value['noOfUnits'] ?? $peo->noOfUnits,
            //     "PetO_id" => $value["PetO_id"]
            // ]);
            //

            // foreach ($enteredFood as $key => $value) {
            //     $opFood = petoptionaldata::updateOrCreate([
            //         "id" => $value["id"],
            //     ], [
            //         'unit' => $value["unit"],
            //         'CaloriesPerUnit' => $value["CaloriesPerUnit"],
            //         'noOfUnits' => $value["noOfUnits"],
            //         'foodName' => $value["foodName"],
            //         "PetO_id" => $value["PetO_id"],
            //     ]);
            // };

            foreach ($data["medications"] as $value) {
                $medicationArray = array("medicationName" => $value[0], "frequency" => $value[1], "petId" => $pet_id, "id" => $value[2]);
                array_push($enteredMedication, $medicationArray);
            };
            foreach ($enteredMedication as $key => $value) {
                $opMedications = medications::updateOrCreate([
                    "id" => $value["id"],
                ], [
                    'medicationName' => $value["medicationName"],
                    'frequency' => $value['frequency'],
                    'petId' => $value['petId'],
                ]);
            }
            foreach ($data["conditions"] as $value) {
                // $conditionsArray = array("PetCondition" => $value[0], "PetConditionState" => $value[1], "PetH_id" => $value[2], "id" => $value[3]);
                $conditionsArray = array("PetCondition" => $value[0], "PetConditionState" => $value[1], "PetH_id" => $pet_id, "id" => $value[2]);
                array_push($enteredCondition, $conditionsArray);
            };
            foreach ($enteredCondition as $key => $value) {
                // [
                //     "id" => $value["id"],
                // ],
                $op4 = PetHealthData::updateOrCreate([

                    "id" => $value["id"],
                ], [
                    'PetCondition' => $value["PetCondition"],
                    'PetConditionState' => $value["PetConditionState"],
                    'PetH_id' => $value['PetH_id'],
                ]);
            }
            // $Pet = pet::where('breeds.pet_id', $data['PetID'])->join('breeds', 'breeds.pet_id', '=', 'petinfo.id')->select('petinfo.id as PetID', 'petinfo.*', 'breeds.id as BreedID', 'breeds.*')->get()->makeHidden(['pet_id', 'id', 'created_at', 'updated_at']);
            if ($op4) {
                return response()->json(["status" => "201", "message" => "data Updated Successfully"], 201);
            }
        }
    }

    public function DeletePet($id)
    {
        $op = pet::where('id', $id)->delete();
        return response()->json(["status" => "200", "message" => "delete successfully"], 200);
    }

    public function displayAllByDate(Request $request, $pet_id)
    {
        $user_id = auth('sanctum')->user()->id;
        $date = $request->validate([
            'date1' => 'required|date',
            'date2' => 'required|date'
        ]);
        //entries include : food , exercise , meds and daily check_in for the pet given.
        $pet = pet::find($pet_id);

        $foodActivity = foodActivity::whereDate('created_at', '>=', $date['date1'])->whereDate('created_at', '<=', $date['date2'])->where('petA_id', $pet->id)->get();
        $exercises = dailyexcercise::whereDate('created_at', '>=', $date['date1'])->whereDate('created_at', '<=', $date['date2'])->where('petE_id', $pet->id)->get();
        // $dailyCheckIn = DailyCheckIn::whereDate('created_at', '>=', $date['date1'])->whereDate('created_at', '<=', $date['date2'])->where('pet_id', $pet->id)->get();
        $medications = medications::whereDate('created_at', '>=', $date['date1'])->whereDate('created_at', '<=', $date['date2'])->where('petId', $pet->id)->get();
        $symptoms = Symptom::whereDate('created_at', '>=', $date['date1'])->whereDate('created_at', '<=', $date['date2'])->where('pet_id', $pet->id)->get();
        return response()->json(["status" => "200", "message" => "Data retrieved successfully", "data" => [

            "food Activity" => $foodActivity,
            "exercises" => $exercises,
            //"daily check-in" => $dailyCheckIn,
            "medications" => $medications,
            "symptoms" => $symptoms,
            "pet" => $pet,

        ]]);
    }

    //-----------------ALL ABOUT EXERCISE--------------//

    public function DailyExcercise(Request $request)
    {
        $last = DB::table('dailyexcercise')->latest()->first();
        //$today = Carbon::today();

        $data = $request->all();
        $validator = Validator::make(
            $data,
            [
                "exerciseName" => "required|min:3",
                "hours" => "required|numeric",
                "mins" => "required|numeric",
                "petE_id" => "required|numeric",
                "intensity" => ['required', Rule::in(['light', 'moderate', 'strenuous'])],
            ]
        );

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $op = dailyexcercise::create($data);



            if ($last->petE_id == $op->petE_id) {
                $LastDay = date('Y-m-d', strtotime($last->created_at));
                $newDay = date('Y-m-d', strtotime($op->created_at));

                $newDaySub = date('Y-m-d', strtotime($op->created_at->subDays(1)));

                if (($LastDay == $newDaySub)) {
                    $op->streak = $last->streak + 1;
                    $op->save();
                } elseif ($LastDay == $newDay) {
                    $op->streak = $last->streak;
                    $op->save();
                }
            } else {
                $last2 = DB::table('dailyexcercise')->where('petE_id', $op->petE_id)->latest()->skip(1)->first();
                if ($last2) {
                    $LastDay = date('Y-m-d', strtotime($last2->created_at));
                    $newDay = date('Y-m-d', strtotime($op->created_at));

                    $newDaySub = date('Y-m-d', strtotime($op->created_at->subDays(1)));

                    if (($LastDay == $newDaySub)) {
                        $op->streak = $last->streak + 1;
                        $op->save();
                    } elseif ($LastDay == $newDay) {
                        $op->streak = $last->streak;
                        $op->save();
                    }
                } else {
                }
            }
            if ($op) {
                return response()->json(["status" => "200", "message" => "Excercise Activity Created", "data" => ["createdExcercise" => $op]], 200);
            }
        }
    }

    public function getExcercise($id, $date)
    {
        $op = dailyexcercise::where('petE_id', $id)->where('created_at', '<', $date)->get();
        if (isset($op[0]->id)) {
            return response()->json(["status" => "200", "message" => "Success", "data" => ["petExcercises" => $op]], 200);
        } else {
            return response()->json(["status" => "500", "message" => "Internal Error"], 500);
        }
    }

    public function getExcercises($id)
    {
        $op = dailyexcercise::where('petE_id', $id)->get();
        if (isset($op[0]->id)) {
            return response()->json(["status" => "200", "message" => "Success", "data" => ["petExcercises" => $op]], 200);
        } else {
            return response()->json(["status" => "404", "message" => "no data", "data" => []], 404);
        }
    }

    public function petUpdateData($id, $pet_id)
    {
        $op = dailyexcercise::where('petE_id', $pet_id)->where('id', $id)->get();
        if (isset($op[0]->id)) {
            return response()->json(["status" => "200", "message" => "Success", "data" => ["petExcercises" => $op]], 200);
        } else {
            return response()->json(["status" => "404", "message" => "no data", "data" => []], 404);
        }
    }

    public function deleteExercise($id)
    {
        $op = dailyexcercise::where('id', $id)->delete();
        return response()->json(["status" => "200", "message" => "delete successfully"], 200);
    }

    public function UpdateExcercise(Request $request, $petE_id, $id)
    {
        $data = $request->all();
        $validator = Validator::make(
            $data,
            [
                "exerciseName" => "required|min:3",
                "hours" => "required|numeric",
                "mins" => "required|numeric",
            ]
        );

        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $op = dailyexcercise::where('petE_id', $petE_id)->where("id", $id)->update($data);
            if ($op) {
                $message = 'Excercise Updated';
                return response()->json(["status" => " 201", "message" => $message], 201);
            } else {
                $message = 'error try again';
                return response()->json(["status" => "500", "message" => $message], 500);
            }
        }
    }

    // daily food activity

    public function createFoodActivity(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make(
            $data,
            [
                "foodName" => "array",
                "petA_id" => "required|numeric"
            ]
        );
        $enteredFood = [];
        if ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        } else {
            $product = array();
            foreach ($data["foodName"] as $value) {
                $foodArray = array("petA_id" => $data['petA_id'], "food" => $value[0], 'unit' => $value[1], 'calPerUnit' => $value[2], 'noOfUnits' => $value[3]);
                array_push($enteredFood, $foodArray);
            };
            foreach ($enteredFood as $value) {
                $second = foodActivity::create($value);
            }
            if ($second) {
                return response()->json(["status" => "200", "message" => "Food Activity Created"], 200);
            } else {
                return response()->json(["status" => "500", "message" => "Internal Error"], 500);
            }
        }
    }

    public function getFoodActivity($id)
    {
        $op = foodActivity::where("petA_id", $id)->get();
        if ($op) {
            return response()->json(["status" => "200", "message" => "success", "data" => ["foodActivity" => $op]], 200);
        } else {
            return response()->json(["status" => "500", "message" => "Internal Error"], 500);
        }
    }
    public function getFoodActivityByDate($id, $date)
    {

        $dateIncremented = strtotime("+1 day", strtotime($date));
        $op = foodActivity::where("petA_id", $id)->where("foodactivity.created_at", '<', date("Y-m-d", $dateIncremented))->get();
        if ($op) {
            return response()->json(["status" => "200", "message" => "success", "data" => ["foodActivity" => $op]], 200);
        } else {
            return response()->json(["status" => "500", "message" => "Internal Error"], 500);
        }
    }
    public function deleteFoodActivity($id)
    {
        $op = foodActivity::where('foodActivity_id', $id)->delete();
        if ($op) {
            return response()->json(["status" => "200", "message" => "delete successfully"], 200);
        } else {
            return response()->json(["status" => "500", "message" => "Internal Error"], 500);
        }
    }

    // Usage streak
    //=======================STREAK ONLY FOR DAILY EXERCISE=====================//
    public function CurrentStreak($id)
    {

        $streak = dailyexcercise::where('petE_id', $id)->latest()->pluck('streak')->first();
        return response()->json(["status" => "200", "message" => 'Success', "data" => ["currentStreak" => $streak]], 200);
    }
    //only exercise
    public function totalEntries($id)
    {
        $totalentries = dailyexcercise::where('petE_id', $id)->count();

        return response()->json(["status" => "200", "message" => 'success', "data" => ["totalEntries" => $totalentries]], 200);
    }

    public function bestStreak($id)
    {
        $max = dailyexcercise::where('petE_id', $id)->max('streak');
        return response()->json(["status" => "200", "message" => "Success", "data" => ["max streak" => $max]], 200);
    }

    //----SENDMESSAGE FUNCTION IS THE LOGIC FOR MESSAGE SENDING
    public function SendMessage(Symptom $symptom)
    {

        $user_id = auth('sanctum')->user()->id;

        $pet = pet::where('id', $symptom->pet_id)->where('user_id', $user_id)->first();

        if ($symptom->name == "cough" || $symptom->name == "sneeze") {


            $last7Days = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));
            $logs = DB::table('symptoms')
                ->select(DB::raw('DATE(created_at) as stat_day'))
                ->where('user_id', auth('sanctum')->user()->id)
                ->where('pet_id', $pet->id)
                ->where('name', 'cough')
                ->orWhere('name', 'sneeze')
                ->whereDate('created_at', '>=', $last7Days)
                ->distinct()
                ->get()
                ->count();

            if ($logs == 1) {

                $message = Message::create([
                    'body' => "If you notice I am coughing/sneezing twice weekly or more frequently,
                            it could be an indication of irritation in my lungs and/or airways.
                             Coughing can be an early indicator of heart and/or lung problems.
                               Please check with my veterinarian ",

                    'user_id' => $user_id,
                    'pet_id' => $pet->id,
                    'symptom_id' => $symptom->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();
                $user->notify(new symptomLoggedNotification($message));
            } elseif ($logs > 1) {

                // $symptom->message = "You have logged that I coughed .$logs times this week. If you notice I am sneezing twice weekly or more frequently, it could be an indication of irritation in my upper airways. If this is a new development and/or the frequency of the sneezing is increasing, please check with my veterinarian";
                // $symptom->save();

                $message = Message::create([
                    'body' => "You have logged that I coughed/sneezed $logs times this week. If you notice I am sneezing twice weekly or more frequently, it could be an indication of irritation in my upper airways. If this is a new development and/or the frequency of the sneezing is increasing, please check with my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();
                $user->notify(new symptomLoggedNotification($message));
            } else {
                $message = Message::create([
                    'body' => "You have logged that I had $symptom->name $logs times this week",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();
                $user->notify(new symptomLoggedNotification($message));
            }
        } elseif ($symptom->name == "decreased energy level" || $symptom->name == "increased energy level") {
            if ($symptom->name == "decreased energy level") {

                $last7Days = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));

                $logs = DB::table('symptoms')
                    ->select(DB::raw('DATE(created_at) as stat_day'))
                    ->where('user_id', auth('sanctum')->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'decreased energy level')
                    ->whereDate('created_at', '>=', $last7Days)
                    ->distinct()
                    ->get()
                    ->count();
                if ($logs == 1 || $logs >= 2) {
                    $message = Message::create([
                        'body' => "It looks like my energy level was decreased this week.  Sometimes this can be due to changes in the environment such as less stimulus or activity at home or the absence of a person or other animal that is normally there.  Decreased energy can also be a sign I am not feeling my best.  If there is no apparent reason for my decreased energy level, please contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                } else {
                    $message = Message::create([
                        'body' => "You have logged that I had $symptom->name $logs times this week",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
            } elseif ($symptom->name == "increased energy level") {
                $last7Days = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));

                $logs = DB::table('symptoms')
                    ->select(DB::raw('DATE(created_at) as stat_day'))
                    ->where('user_id', auth('sanctum')->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'increased energy level')
                    ->where('created_at', '>=', $last7Days)
                    ->distinct()
                    ->get()
                    ->count();
                if ($logs == 1 || $logs >= 4) {
                    $message = Message::create([
                        'body' => "It looks like I've had a very energetic week!  Most likely this is due to increased opportunity to interact and exercise.  Increases in energy levels can also be caused by anxiety or, less frequently, medical conditions.  If my energy level is increased and there is no apparent reason, contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                } else {
                    $message = Message::create([
                        'body' => "You have logged that I had $symptom->name $logs times this week",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
            }
        } elseif ($symptom->name == "decreased appetite" || $symptom->name == "increased appetite") {
            if ($symptom->name == "increased appetite") {
                $last7Days = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));

                $logs = DB::table('symptoms')
                    ->select(DB::raw('DATE(created_at) as stat_day'))
                    ->where('user_id', auth('sanctum')->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'increased appetite')
                    ->distinct()
                    ->get()
                    ->count();
                if ($symptom->streak >= 14 || $logs == 1) {
                    $message = Message::create([
                        'body' => "Increases in appetite can sometimes be due to me really enjoying my food, competitive eating when there are other animals in the house, or less frequently, a medical condition.  If my increased appetite persists beyond 2 weeks and there is no apparent underlying cause, contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                } else {
                    $message = Message::create([
                        'body' => "You have logged that I had $symptom->name $logs times this week",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
            } elseif ($symptom->name == "decreased appetite") {
                $last7Days = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));

                $logs = DB::table('symptoms')
                    ->select(DB::raw('DATE(created_at) as stat_day'))
                    ->where('user_id', auth('sanctum')->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'decreased appetite')
                    ->distinct()
                    ->get()
                    ->count();
                if ($symptom->streak >= 3 || $logs == 1) {
                    $message = Message::create([
                        'body' => "There are many reasons I could have a decreased appetite including not liking my food, GI upset, stress, or an underlying medical problem.  If my appetite is significantly decreased for more than 3 days or if I am not eating at all for more than 1 day, please contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                } else {
                    $message = Message::create([
                        'body' => "You have logged that I had $symptom->name $logs times this week",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
            }
        } elseif ($symptom->name == "increased water consumption" || $symptom->name == "decreased water consumption") {
            if ($symptom->name == "increased water consumption") {
                $last7Days = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));

                $logs = DB::table('symptoms')
                    ->select(DB::raw('DATE(created_at) as stat_day'))
                    ->where('user_id', auth('sanctum')->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'increased water consumption')
                    ->where('created_at', '>=', $last7Days)
                    ->distinct()
                    ->get()
                    ->count();
                if ($logs >= 3 || $logs == 1) {
                    $message = Message::create([
                        'body' => "Increases in water consumption can occur in warm weather, if I have been exercising more than normal or if my diet is dry such as kibble, freeze dried, or dehydrated food without water added.  There are also medical conditions that could cause me to drink more.  If I am drinking more water than normal and there is no apparent cause, please contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                } else {
                    $message = Message::create([
                        'body' => "You have logged that I had .$symptom->name .$logs times this week",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
            } elseif ($symptom->name == "decreased water consumption") {
                $last7Days = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));

                $logs = DB::table('symptoms')
                    ->select(DB::raw('DATE(created_at) as stat_day'))
                    ->where('user_id', auth('sanctum')->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'decreased water consumption')
                    ->where('created_at', '>=', $last7Days)
                    ->distinct()
                    ->get()
                    ->count();
                if ($logs >= 2 || $logs == 1) {
                    $message = Message::create([
                        'body' => "Decreases in water consumption can sometimes happen if you switch my food to a high moisture diet such as fresh food, rehydrated freeze dried or dehydrated food, or canned.  In these cases, decreased water consumption is normal because I’m getting most of their water from my food.  Pets that are feeling sick will also sometimes drink less.  If my water consumption has decreased and the reason is not clear, please contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                } else {
                    $message = Message::create([
                        'body' => "You have logged that I had .$symptom->name .$logs times this week",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
            }
        } elseif ($symptom->name == "mobility") {
            $last7Days = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));

            $logs = DB::table('symptoms')
                ->select(DB::raw('DATE(created_at) as stat_day'))
                ->where('user_id', auth('sanctum')->user()->id)
                ->where('pet_id', $pet->id)
                ->where('name', 'mobility')
                ->distinct()
                ->get()
                ->count();
            if ($symptom->streak >= 2 || $logs == 1) {
                $message = Message::create([
                    'body' => "I am not moving around as much as I should be. It could be due to over exercise, an injury, arthritis, or back pain.  If I exercised more than normal today or yesterday, I may just need a little time to rest. Sometimes decreases in mobility are caused by my not feeling well for more internal reasons.  If my decreased mobility lasts for more than a couple of days or if I otherwise look like I am not feeling well, please contact my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();
                $user->notify(new symptomLoggedNotification($message));
            } else {
                $message = Message::create([
                    'body' => "You have logged that I had .$symptom->name .$logs times this week",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();
                $user->notify(new symptomLoggedNotification($message));
            }
        } elseif ($symptom->name == "limping" || $symptom->name == "soreness") {
            if ($symptom->streak == 1) {
                $message = Message::create([
                    'body' => "Limping is usually a sign that I am sore.  It could be due to over exercise, an injury, arthritis, or back pain.  If I exercised more than normal today or yesterday, I may just need a little time to rest.  If there is not an explanation for my limping, my limp persists for more than a few days, or I am having trouble getting around, please contact my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();

                $user->notify(new symptomLoggedNotification($message));
            } elseif ($symptom->streak >= 2) {
                $message = Message::create([
                    'body' => "Limping is a sign that I am sore.  It could be due to over exercise, an injury, arthritis, or back pain.  If I exercised more than normal today or yesterday, I may just need a little time to rest.  If there is not an explanation for my limping, my limp persists for more than a few days, or I am having trouble getting around, please contact my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();

                $user->notify(new symptomLoggedNotification($message));
            }
        } elseif ($symptom->name == "scratching" || $symptom->name == "licking") {
            if ($symptom->streak == 1 || $symptom->streak < 3) {
                $message = Message::create([
                    'body' => "I am scratching and licking more than normal.  This could mean I am having a flare up of allergies, I have picked up some fleas, or I have got something irritating on my skin.  I might also lick if I am stressed.  Please look me over to see if you can figure out what is causing this.  Sometimes a bath helps.  If you do not see a reason for my itching and licking or my skin seems red, please contact my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();

                $user->notify(new symptomLoggedNotification($message));
            } elseif ($symptom->streak >= 3) {
                $message = Message::create([
                    'body' => "You have logged that I had .$symptom->name .$symptom->streak days in a row. I am scratching and licking more than normal.  This could mean I am having a flare up of allergies, I have picked up some fleas, or I have got something irritating on my skin.  I might also lick if I am stressed.  Please look me over to see if you can figure out what is causing this.  Sometimes a bath helps.  If my itching and licking has been going on for a few days and/or my skin seems red, please contact my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();

                $user->notify(new symptomLoggedNotification($message));
            }
        } elseif ($symptom->name == "seizures") {
            $last30Days = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 30, date("Y")));

            $logs = DB::table('symptoms')
                ->select(DB::raw('DATE(created_at) as stat_day'))
                ->where('user_id', auth('sanctum')->user()->id)
                ->where('pet_id', $pet->id)
                ->where('name', 'seizures')
                ->where('created_at', '>=', $last30Days)
                ->distinct()
                ->get()
                ->count();
            if ($logs == 1) {
                $message = Message::create([
                    'body' => "I hope my seizure was not too scary for you. I do not really remember it. If the seizure is within my normal frequency and severity, keep doing whatever my veterinarian instructed you to.  If my seizure frequency is increasing or the severity is worse, please contact my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();

                $user->notify(new symptomLoggedNotification($message));
            } else {
                $message = Message::create([
                    'body' => "I hope my seizure was not too scary for you. I do not really remember it. If the seizure is within my normal frequency and severity, keep doing whatever my veterinarian instructed you to.  If my seizure frequency is increasing or the severity is worse, please contact my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();

                $user->notify(new symptomLoggedNotification($message));
            }
        } elseif ($symptom->name == "vomit" || $symptom->name == "diarrhea") {
            if ($pet->specie == "dog") {

                if ($symptom->name == "vomit") {
                    $logs = DB::table('symptoms')
                        ->select(DB::raw('DATE(created_at) as stat_day'))
                        ->where('user_id', auth('sanctum')->user()->id)
                        ->where('pet_id', $pet->id)
                        ->where('name', 'vomit')
                        ->distinct()
                        ->get()
                        ->count();
                    if ($symptom->streak >= 2 || $logs == 1) {
                        $message = Message::create([
                            'body' => "Most of the time, vomiting is due to short term stomach upset resulting from my eating something I should not have.  If the vomiting persists for more than 48 hours, I am vomiting but not eating, or I otherwise seem unwell, please contact my veterinarian",
                            'symptom_id' => $symptom->id,
                            'user_id' => $user_id,
                            'pet_id' => $pet->id,

                        ]);
                        $user = User::where('id', $symptom->user_id)->first();
                        //$user->notify(new symptomLoggedNotification($symptom));
                        $user->notify(new symptomLoggedNotification($message));
                    } else {
                        $message = Message::create([
                            'body' => "",
                            'symptom_id' => $symptom->id,
                            'user_id' => $user_id,
                            'pet_id' => $pet->id,

                        ]);
                        $user = User::where('id', $symptom->user_id)->first();
                        //$user->notify(new symptomLoggedNotification($symptom));
                        $user->notify(new symptomLoggedNotification($message));
                    }
                }
                if ($symptom->name == "diarrhea") {
                    $logs = DB::table('symptoms')
                        ->select(DB::raw('DATE(created_at) as stat_day'))
                        ->where('user_id', auth('sanctum')->user()->id)
                        ->where('pet_id', $pet->id)
                        ->where('name', 'diarrhea')
                        ->distinct()
                        ->get()
                        ->count();
                    if ($symptom->streak >= 2 || $logs == 1) {
                        $message = Message::create([
                            'body' => "Most of the time, diarrhea is due to short term stomach upset resulting from me eating something I should not have.  If I am acting normally otherwise and am not vomiting, you can try to feed me a bland diet for a few days to help my GI tract recover.  If the diarrhea persists for more than 48 hours or I otherwise seem unwell, please contact my veterinarian",
                            'symptom_id' => $symptom->id,
                            'user_id' => $user_id,
                            'pet_id' => $pet->id,

                        ]);
                        $user = User::where('id', $symptom->user_id)->first();
                        //$user->notify(new symptomLoggedNotification($symptom));

                        $user->notify(new symptomLoggedNotification($message));
                    } else {
                        $message = Message::create([
                            'body' => "",
                            'symptom_id' => $symptom->id,
                            'user_id' => $user_id,
                            'pet_id' => $pet->id,

                        ]);
                        $user = User::where('id', $symptom->user_id)->first();
                        //$user->notify(new symptomLoggedNotification($symptom));
                        $user->notify(new symptomLoggedNotification($message));
                    }
                }
            }
            if ($pet->specie == "cat") {
                $last7Days = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") - 7, date("Y")));

                $logs = DB::table('symptoms')
                    ->select(DB::raw('DATE(created_at) as stat_day'))
                    ->where('user_id', auth('sanctum')->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'vomit')
                    ->orWhere('name', 'diarrhea')
                    ->whereDate('created_at', '>=', $last7Days)
                    ->distinct()
                    ->get()
                    ->count();

                if ($logs == 1) {

                    $message = Message::create([
                        'body' => "Kitties sometimes vomit due to hairballs or eating too fast.  If I am vomiting more than once every week or two, please contact my veterinarian",

                        'user_id' => $user_id,
                        'pet_id' => $pet->id,
                        'symptom_id' => $symptom->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    //$user->notify(new symptomLoggedNotification($symptom));
                    $user->notify(new symptomLoggedNotification($message));
                } elseif ($logs > 1) {

                    $message = Message::create([
                        'body' => "You have logged that I vomitted .$logs times this week. Kitties sometimes vomit due to hairballs or eating too fast.  If I am vomiting more than once every week or two, please contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    //$user->notify(new symptomLoggedNotification($symptom));
                    $user->notify(new symptomLoggedNotification($message));
                } else {
                    $message = Message::create([
                        'body' => "You have logged that I had .$symptom->name .$logs times this week",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
            }
        }
    }
    //----THIS IS SYMPTOM LOG BUT DIRECTLY CALLING SENDMESSAGE FUNCTION WITHOUT EVENT LISTENERS
    public function SymptomLogNotify(Request $request)
    {
        $last = DB::table('symptoms')->latest()->first();
        $user_id = auth('sanctum')->user()->id;
        // $today = Carbon::today();
        $data = $request->all();
        $validator = Validator::make(
            $data,
            [
                "name" => ['required', Rule::in(['cough', 'sneeze', 'vomit', 'diarrhea', 'limping', 'soreness', 'scratching', 'licking', 'seizures', 'decreased energy level', 'increased energy level', 'decreased appetite', 'increased appetite', 'increased water consumption', 'decreased water consumption', 'mobility'])],
                "severity" => ['required', Rule::in(['mild', 'mid', 'severe'])],
                "pet_id" => "required|numeric",

            ]
        );
        if ($validator) {

            $symptom = Symptom::create([
                'name' => $data['name'],
                'severity' => $data['severity'],
                'pet_id' => $data['pet_id'],
                'user_id' => $user_id,
            ]);
        } elseif ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        }

        if ($symptom) {
            //begenning of action
            if ($last->pet_id == $symptom->pet_id && $last->user_id == $symptom->user_id && $last->name == $symptom->name) {
                //calculate streak of symptom if the previous one is for the same pet
                // $LastDay = date('d', strtotime($last->created_at));
                // $newDay = date('d', strtotime($symptom->created_at));
                $LastDay = date('Y-m-d', strtotime($last->created_at));
                $newDay = date('Y-m-d', strtotime($symptom->created_at));
                // $LastDay = $last->created_at->toDateString();
                // $newDay = $symptom->created_at->toDateString();
                $newDaySub = date('Y-m-d', strtotime($symptom->created_at->subDays(1)));

                if (($LastDay == $newDaySub)) {
                    $symptom->streak = $last->streak + 1;
                    $symptom->save();
                } elseif ($LastDay == $newDay) {
                    $symptom->streak = $last->streak;
                    $symptom->save();
                }
                //event(new SymptomLogged($symptom));
                $this->SendMessage($symptom);

                return response()->json([
                    "status" => " 200",
                    "message" => "created successfully",
                    "Symptom" => $symptom,
                    $newDaySub
                ]);
            } else {
                $last2 = DB::table('symptoms')->where('pet_id', $symptom->pet_id)->where('user_id', $symptom->user_id)->where('name', $symptom->name)->latest()->skip(1)->first();
                if ($last2) {
                    $LastDay = date('Y-m-d', strtotime($last2->created_at));
                    $newDay = date('Y-m-d', strtotime($symptom->created_at));
                    // $LastDay = $last->created_at->toDateString();
                    // $newDay = $symptom->created_at->toDateString();
                    $newDaySub = date('Y-m-d', strtotime($symptom->created_at->subDays(1)));

                    if (($LastDay == $newDaySub)) {
                        $symptom->streak = $last2->streak + 1;
                        $symptom->save();
                    } elseif ($LastDay == $newDay) {
                        $symptom->streak = $last2->streak;
                        $symptom->save();
                    }
                    $this->SendMessage($symptom);
                    //event(new SymptomLogged($symptom));
                    return response()->json([
                        "status" => " 200",
                        "message" => "created successfully",
                        "Symptom" => $symptom,
                        $newDaySub
                    ]);
                } else {
                    $this->SendMessage($symptom);
                    //event(new SymptomLogged($symptom));
                    return response()->json([
                        "status" => " 200",
                        "message" => "created successfully",
                        "Symptom" => $symptom,

                    ]);
                }
            }
        } else {
            return response()->json(["status" => "400", "message" => "failed to create"], 400);
        }
    }

    public function SymptomArray(Request $request)
    {

        $user_id = auth('sanctum')->user()->id;

        $data = $request->all();
        $symptoms = array();
        $newSymptoms = array();
        $lastDays = array();
        $last2Days = array();
        $validator = Validator::make(
            $data,
            [
                "symptoms" => 'required|array',

            ]
        );
        if ($validator) {

            if (isset($data["symptoms"])) {
                foreach ($data["symptoms"] as $value) {
                    $symptom_array = array("pet_id" => $value[0], "name" => $value[1], 'severity' => $value[2]);
                    array_push($symptoms, $symptom_array);
                };
                // return response()->json(["status" => "201", "message" =>$enteredFood], 201);
                foreach ($symptoms as $value) {
                    $symptom = Symptom::create([
                        'name' => $value['name'],
                        'severity' => $value['severity'],
                        'pet_id' => $value['pet_id'],
                        'user_id' => $user_id,

                    ]);
                    array_push($newSymptoms, $symptom);
                }
            }
            foreach ($newSymptoms as $symptom) {
                if ($symptom) {
                    //begenning of action
                    $last = DB::table('symptoms')->where('name', $symptom->name)->where('pet_id', $symptom->pet_id)->latest()->skip(1)->first();
                    if ($last == $symptom) {
                        $last = DB::table('symptoms')->where('name', $symptom->name)->where('pet_id', $symptom->pet_id)->latest()->skip(2)->first();
                    }
                    if ($last) {
                        if ($last->pet_id == $symptom->pet_id && $last->user_id == $symptom->user_id && $last->name == $symptom->name) {

                            $LastDay = date('Y-m-d', strtotime($last->created_at));
                            $newDay = date('Y-m-d', strtotime($symptom->created_at));
                            $newDaySub = date('Y-m-d', strtotime($symptom->created_at->subDays(1)));

                            if (($LastDay == $newDaySub)) {
                                $symptom->streak = $last->streak + 1;
                                $symptom->save();
                            } elseif ($LastDay == $newDay) {
                                $symptom->streak = $last->streak;
                                $symptom->save();
                            }
                            //event(new SymptomLogged($symptom));
                            $this->SendMessage($symptom);
                            array_push($lastDays, $last);
                        } else {
                            $last2 = DB::table('symptoms')->where('pet_id', $symptom->pet_id)->where('user_id', $symptom->user_id)->where('name', $symptom->name)->latest()->skip(2)->first();
                            if ($last2) {
                                $LastDay = date('Y-m-d', strtotime($last2->created_at));
                                $newDay = date('Y-m-d', strtotime($symptom->created_at));
                                $newDaySub = date('Y-m-d', strtotime($symptom->created_at->subDays(1)));

                                if (($LastDay == $newDaySub)) {
                                    $symptom->streak = $last2->streak + 1;
                                    $symptom->save();
                                } elseif ($LastDay == $newDay) {
                                    $symptom->streak = $last2->streak;
                                    $symptom->save();
                                }
                                $this->SendMessage($symptom);
                                //event(new SymptomLogged($symptom));
                                array_push($last2Days, $last2);
                            } else {
                                $this->SendMessage($symptom);
                                //event(new SymptomLogged($symptom));

                            }
                        }
                    } else {
                        $this->SendMessage($symptom);
                    }
                } else {
                    return response()->json(["status" => "400", "message" => "failed to create"], 400);
                }
            }

            return response()->json([
                "status" => " 200",
                "message" => "created successfully",
                "new symptoms" => $newSymptoms,
                "last Days" => $lastDays,
                "last2Days" => $last2Days

            ]);
        } elseif ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        }
    }

    //--------FULL LOG DAILY CHECK-IN --------//
    public function dailyCheckIn(Request $request)
    {

        $user_id = auth('sanctum')->user()->id;
        $data = $request->all();
        $validator = Validator::make(
            $data,
            [
                "exercise_min" => "nullable|numeric",
                "exercise_level" => ['nullable', Rule::in(['light exercise', 'heavy exercise'])],
                "energy_level" => ['nullable', Rule::in(['less than usual', 'normal', 'more than usual'])],
                "cough_or_sneeze" => ['nullable', Rule::in(['cough', 'sneeze'])],
                "vomit_or_diarrhea" => ['nullable', Rule::in(['Vomite', 'Diarrhea'])],
                //less energy than normal

                "limping_or_soreness" => ['nullable', Rule::in(['less than usual', 'normal', 'more than usual'])],
                "scratching_or_licking" => ['nullable', Rule::in(['less than usual', 'normal', 'more than usual'])],
                "seizures" => ['nullable', Rule::in(['yes', 'no'])],
                "notes" => "nullable|text",
                "pet_id" => "required|numeric",

            ]
        );

        if ($validator) {
            //check if that pet has already a log for today
            $dailyCheckIn = DailyCheckIn::where('user_id', $user_id)
                ->where('pet_id', $data['pet_id'])
                ->whereDate('created_at', Carbon::today())
                ->first();

            if ($dailyCheckIn) {
                // if yes then update that log
                $dailyCheckIn->update([
                    'exercise_min' => $data['exercise_min'] ?? $dailyCheckIn->exercise_min,
                    'exercise_level' => $data['exercise_level'] ?? $dailyCheckIn->exercise_level,
                    'energy_level' => $data['energy_level'] ?? $dailyCheckIn->energy_level,
                    'cough_or_sneeze' => $data['cough_or_sneeze'] ?? $dailyCheckIn->cough_or_sneeze,
                    'limping_or_soreness' => $data['limping_or_soreness'] ?? $dailyCheckIn->limping_or_soreness,
                    'vomit_or_diarrhea' => $data['vomit_or_diarrhea'] ?? $dailyCheckIn->vomit_or_diarrhea,
                    'scratching_or_licking' => $data['scratching_or_licking'] ?? $dailyCheckIn->scratching_or_licking,
                    'seizures' => $data['seizures'] ?? $dailyCheckIn->seizures,
                    'notes' => $data['notes'] ?? $dailyCheckIn->notes,
                    'pet_id' => $data['pet_id'] ?? $dailyCheckIn->pet_id,
                    'user_id' => $user_id,

                ]);
            } else {
                //if no then create a new one
                $dailyCheckIn = DailyCheckIn::create([
                    'exercise_min' => $data['exercise_min'] ?? 0,
                    'exercise_level' => $data['exercise_level'] ?? null,
                    'energy_level' => $data['energy_level'] ?? null,
                    'cough_or_sneeze' => $data['cough_or_sneeze'] ?? null,
                    'limping_or_soreness' => $data['limping_or_soreness'] ?? null,
                    'vomit_or_diarrhea' => $data['vomit_or_diarrhea'] ?? null,
                    'scratching_or_licking' => $data['scratching_or_licking'] ?? null,
                    'seizures' => $data['seizures'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'pet_id' => $data['pet_id'] ?? null,
                    'user_id' => $user_id,

                ]);
            }

            $pet = pet::where('id', $dailyCheckIn->pet_id)->first();
            //adding the exercise min to the dailyexercise table
            $exercise = dailyexcercise::where('petE_id', $dailyCheckIn->pet_id)->whereDate('created_at', '=', $dailyCheckIn->created_at->format('Y-m-d'))->first();
            if ($exercise) {
                $exercise->total_minutes = $dailyCheckIn->exercise_min;
                $exercise->save();
            }

            return response()->json(["status" => "200", "message" => "success", "data" => "You have completed $pet->PetName's daily check-in!"]);
        } elseif ($validator->fails()) {
            return response()->json(["status" => "400", "message" => "failed to pass validation", "data" => ["errors" => $validator->errors()]], 400);
        }
    }

    //--------GET ALL FOOD/EXERCISE MINS/MEDICATIONS/RECENT SYMPTOMS FOR HOME PAGE

    //----CHANGE THE NAME INTO SOMETHING MORE RELATIVE


    //--counts the symptom logs in a week
    //--arguments: week's number/pet_id/symptom name
    public function getLastWeek($week, $pet_id, $name)
    {
        //Exercise per week
        $dates = collect();

        foreach (range(-6, 0) as $i) {
            //here we get the last 7 days starting from the current week and moving backward to the last 3 weeks
            $date = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d") + ($i - (($week - 1) * 7) + 1), date("Y")));
            // $date = Carbon::today()->addDays($i)->subWeeks($week - 1)->format('Y-m-d');
            $dates->put($date, 0);
        }

        // Get the log counts
        // where the date is between the first day of the week and the last day of that week
        $Logs = Symptom::where('pet_id', $pet_id)->where('name', $name)->where('created_at', '>=', $dates->keys()->first())->where('created_at', '<=', $dates->keys()->last())
            ->groupBy('date')
            ->orderBy('date')
            ->get([
                DB::raw('DATE( created_at ) as date'),
                DB::raw('COUNT( * ) as "count"')
            ])
            ->pluck('count')->sum();

        //return the number of that $symptom_name logs in that $week
        return $Logs;
    }
    public function GetAllEntries($pet_id)
    {
        $user_id = auth('sanctum')->user()->id;
        //----------------------------------------------------------------------------------------------------//
        //--------------------------------------------FOOD----------------------------------------------------//
        //----------------------------------------------------------------------------------------------------//

        $date = Carbon::now()->subDays(7)->format('Y-m-d');
        $der = calculations::where('petId', $pet_id)->pluck('der')->first();

        $total_calories = 0;
        $avegFood = 0;
        $totalOfSaturday = 0;
        $totalOfSunday = 0;
        $totalOfMonday = 0;
        $totalOfTuesday = 0;
        $totalOfWednesday = 0;
        $totalOfThursday = 0;
        $totalOfFriday = 0;
        $totalForFood = array("Saturday" => 0, "Sunday" => 0, "Monday" => 0, "Tuesday" => 0, "Wednesday" => 0, "Thursday" => 0, "Friday" => 0);
        $food = foodActivity::where('petA_id', $pet_id)->where('created_at', '>=', $date)->get()->makeHidden(["id", 'created_at', 'updated_at']);
        foreach ($food as $val) {
            $amoutoffood = $der / $val->calPerUnit;
            $total_calories += $amoutoffood;
            $val->day = $val->created_at->format('l');
            $avegFood += $val->calPerUnit;
        }
        $avegAm = $avegFood / 7;
        $avegAmount = number_format((float)$avegAm, 2, '.', '');
        foreach ($food as $val) {
            if ($val->day == "Saturday") {
                $totalOfSaturday += $val->calPerUnit;
            }
            if ($val->day == "Sunday") {
                $totalOfSunday += $val->calPerUnit;
            }
            if ($val->day == "Monday") {
                $totalOfMonday += $val->calPerUnit;
            }
            if ($val->day == "Tuesday") {
                $totalOfTuesday += $val->calPerUnit;
            }
            if ($val->day == "Wednesday") {
                $totalOfWednesday += $val->calPerUnit;
            }
            if ($val->day == "Thursday") {
                $totalOfThursday += $val->calPerUnit;
            }
            if ($val->day == "Friday") {
                $totalOfFriday += $val->calPerUnit;
            }
        }
        $totalForFood["Saturday"] = $totalOfSaturday;
        $totalForFood["Sunday"] = $totalOfSunday;
        $totalForFood["Monday"] = $totalOfMonday;
        $totalForFood["Tuesday"] = $totalOfTuesday;
        $totalForFood["Wednesday"] = $totalOfWednesday;
        $totalForFood["Thursday"] = $totalOfThursday;
        $totalForFood["Friday"] = $totalOfFriday;

        // ------ GET THE NUMBER OF TIMES THE FOOD LOGGED -------//
        $datesFood = collect();
        //looping on week days from latest to earliest
        foreach (range(-6, 0) as $i) {
            $dates = Carbon::now()->addDays($i)->format('Y-m-d');
            //pushing the dates with initial value of 0
            $datesFood->put($dates, 0);
        }

        //--creating a new collection of also (date,value) for the last 7 days
        //--begenning from the earliest day of the week
        $foodLogs = foodActivity::where('petA_id', $pet_id)->where('created_at', '>=', $datesFood->keys()->first())
            ->groupBy('date')
            //sort to count all logs per day
            ->orderBy('date')
            ->get([

                DB::raw('DATE( created_at ) as date'),
                DB::raw('COUNT( * ) as "count"'),

            ])
            ->pluck('count', 'date');

        // Merge the two collections; any results in $'foodLogs' will overwrite the zero-value in '$datesFood'
        $datesFood = $datesFood->merge($foodLogs);
        $totalForFood["Saturday"] = $totalOfSaturday;
        $totalForFood["Sunday"] = $totalOfSunday;
        $totalForFood["Monday"] = $totalOfMonday;
        $totalForFood["Tuesday"] = $totalOfTuesday;
        $totalForFood["Wednesday"] = $totalOfWednesday;
        $totalForFood["Thursday"] = $totalOfThursday;
        $totalForFood["Friday"] = $totalOfFriday;
        // $recommended_food_amount = $der / $total_calories;
        //recent activities done in the day

        //----------------------------------------------------------------------------------------------------//
        //--------------------------------------------EXERCISE------------------------------------------------//
        //----------------------------------------------------------------------------------------------------//


        //$recent_exercise = dailyexcercise::where('petE_id', $pet_id)->where('created_at', '>=', $date)->get()->makeHidden(["id", 'created_at', 'updated_at']);
        $BCS = pet::where('id', $pet_id)->pluck('BodyConditionScore')->first();

        function sum_arrays($array1, $array2)
        {
            $array = array();
            foreach ($array1 as $index => $value) {
                $array[$index] = isset($array2[$index]) ? $array2[$index] + $value : $value;
            }
            return $array;
        }

        //--Same logic as food log count
        $datesExe = collect();
        $datesIntensity = collect();
        $intensity_per_day = collect();
        $dates_daily_CheckIn = collect();

        foreach (range(-6, 0) as $i) {
            $date = Carbon::now()->addDays($i)->format('Y-m-d');
            $datesExe->put($date, 0);
            $datesIntensity->put($date, 0);
            $intensity_per_day->put($date, 0);
            $dates_daily_CheckIn->put($date, 0);
        }

        //-------THE ARRAY OF TOTAL EXERCISE MINUTES PER DAY FROM DAILYEXERCISE TABLE -----//
        $exeLogs = dailyexcercise::where('petE_id', $pet_id)->where('created_at', '>=', $datesExe->keys()->first())
            ->groupBy('date')
            ->orderBy('date')
            ->get([
                DB::raw('DATE( created_at ) as date'),
                //here we're summing the total minutes/hours/daily_check_in minutes
                DB::raw('SUM(mins)+SUM(hours)*60 as minutes'),

            ])
            ->pluck('minutes', 'date');

        //----THE ARRAY OF TOTAL MINUTES FROM DAILY CHECK IN TABLE---//
        $daily_CheckIn_Logs = DailyCheckIn::where('pet_id', $pet_id)->where('created_at', '>=', $dates_daily_CheckIn->keys()->first())
            ->groupBy('date')
            ->orderBy('date')
            ->get([
                DB::raw('DATE( created_at ) as date'),
                //here we're summing the total minutes/hours/daily_check_in minutes
                'exercise_min',

            ])
            ->pluck('exercise_min', 'date');

        //--THE ARRAY GETTING THE MAX INTENSITY OF THE DAY
        $exeIntensity = dailyexcercise::where('petE_id', $pet_id)->where('created_at', '>=', $datesIntensity->keys()->first())
            ->groupBy('date')
            ->orderBy('date')
            ->get([
                DB::raw('DATE( created_at ) as date'),
                //we're getting the max of intensity (ALphabetically S > M > L)
                DB::raw('MAX(intensity) as intensity'),

            ])
            ->pluck('intensity', 'date');

        //------ARRAY OF (DATES,EXERCISE MINUTES FROM DAILYEXERCISE)
        $datesExe = $datesExe->merge($exeLogs);
        //------ARRAY OF (DATES,MAX INTENSITY)
        $datesIntensity = $datesIntensity->merge($exeIntensity);
        //----ARRAY OF (DATE,CHECKIN MINUTES)
        $dates_daily_CheckIn = $dates_daily_CheckIn->merge($daily_CheckIn_Logs);
        //-----ARRAY OF (DATE,SUM OF EXERCISE MINUTES FROM DAILYEXERCISE AND DAILY CHECK IN)
        $TotalDaysForExercise = sum_arrays($datesExe, $dates_daily_CheckIn);
        //-----OVERWRITING THE NEW RESULTS IN THE FIRST ARRAY ----//
        $datesExe = $datesExe->merge($TotalDaysForExercise);

        //--getting average minutes for the whole weeek
        $sum_all_week = 0;
        foreach ($datesExe->values() as $value) {
            $sum_all_week += $value;
        }
        $average = $sum_all_week / 7;
        $aveg = number_format((float)$average, 2, '.', '');




        //----------------------------------------------------------------------------------------------------//
        //--------------------------------------------MEDS----------------------------------------------------//
        //----------------------------------------------------------------------------------------------------//
        $daily_medications = medications::where('petId', $pet_id)->get();
        $recent_symptoms = Symptom::select('name', 'severity')->where('pet_id', $pet_id)->where('user_id', $user_id)->latest()->take(3)->get();

        $totalForcough = [];
        $totalForSneeze = [];
        $totalForVomit = [];
        $totalForDiarrhea = [];
        $totalForLimping = [];
        $totalForSoreness = [];
        $totalForScratching = [];
        $totalForLicking = [];
        $totalForSeizures = [];
        $totalForIncE = [];
        $totalForDecE = [];
        $totalForIncApt = [];
        $totalForDecApt = [];
        $totalForIncWater = [];
        $totalForDecWater = [];
        $totalForMobility = [];

        $coughLogs = 0;
        $sneezeLogs = 0;
        $vomitLogs = 0;
        $diarrheaLogs = 0;
        $limpingLogs = 0;
        $sorenessLogs = 0;
        $scratchingLogs = 0;
        $lickingLogs = 0;
        $seizuresLogs = 0;
        $incELogs = 0;
        $decELogs = 0;
        $incAptLogs = 0;
        $decAptLogs = 0;
        $incWaterLogs = 0;
        $decWaterLogs = 0;
        $mobilityLogs = 0;


        //looping on the 4 weeks
        foreach (range(1, 4) as $i) {
            //--calculating the number of symptom logs for the whole week
            //--sending the week's number($i),pet_id and symptom name
            $coughLogs = $this->getLastWeek($i, $pet_id, 'cough');
            $totalForcough["Week $i"] = $coughLogs;

            $sneezeLogs = $this->getLastWeek($i, $pet_id, 'sneeze');
            $totalForSneeze["Week $i"] = $sneezeLogs;

            $vomitLogs = $this->getLastWeek($i, $pet_id, 'vomit');
            $totalForVomit["Week $i"] = $vomitLogs;

            $diarrheaLogs = $this->getLastWeek($i, $pet_id, 'diarrhea');
            $totalForDiarrhea["Week $i"] = $diarrheaLogs;

            $limpingLogs = $this->getLastWeek($i, $pet_id, 'limping');
            $totalForLimping["Week $i"] = $limpingLogs;

            $sorenessLogs = $this->getLastWeek($i, $pet_id, 'soreness');
            $totalForSoreness["Week $i"] = $sorenessLogs;

            $scratchingLogs = $this->getLastWeek($i, $pet_id, 'scratching');
            $totalForScratching["Week $i"] = $scratchingLogs;

            $lickingLogs = $this->getLastWeek($i, $pet_id, 'licking');
            $totalForLicking["Week $i"] = $lickingLogs;

            $seizuresLogs = $this->getLastWeek($i, $pet_id, 'seizures');
            $totalForSeizures["Week $i"] = $seizuresLogs;

            $incELogs = $this->getLastWeek($i, $pet_id, 'increased energy level');
            $totalForIncE["Week $i"] = $incELogs;

            $decELogs = $this->getLastWeek($i, $pet_id, 'decreased energy level');
            $totalForDecE["Week $i"] = $decELogs;

            $incAptLogs = $this->getLastWeek($i, $pet_id, 'increased appetite');
            $totalForIncApt["Week $i"] = $incAptLogs;

            $decAptLogs = $this->getLastWeek($i, $pet_id, 'decreased appetite');
            $totalForDecApt["Week $i"] = $decAptLogs;

            $incWaterLogs = $this->getLastWeek($i, $pet_id, 'increased water consumption');
            $totalForIncWater["Week $i"] = $incWaterLogs;

            $decWaterLogs = $this->getLastWeek($i, $pet_id, 'decreased water consumption');
            $totalForDecWater["Week $i"] = $decWaterLogs;

            $mobilityLogs = $this->getLastWeek($i, $pet_id, 'mobility');
            $totalForMobility["Week $i"] = $mobilityLogs;
        }

        return response()->json(["status" => "200", "message" => "success", "data" =>
        [
            "food" => [
                "Amount of food" => $total_calories,
                "aveg food amount" => $avegAmount,
                "calories for each day" => $totalForFood,
                "number of food logs each day" => $datesFood

            ],
            "activity" => [
                "average minutes for each day" => $aveg,
                "Minutes for each day" => $datesExe,
                "BCS" => $BCS,
                "intensity" => $datesIntensity,

            ],
            "Medical" =>
            [
                [
                    "daily medications" => $daily_medications,

                ],
                [
                    "recently Logged Symptoms" => [

                        "cough" => $totalForcough,
                        "sneeze" => $totalForSneeze,
                        "vomit" => $totalForVomit,
                        "diarrhea" => $totalForDiarrhea,
                        "soreness" => $totalForSoreness,
                        "scratching" => $totalForScratching,
                        "licking" => $totalForLicking,
                        "seizures" => $totalForSeizures,
                        "increased energy" => $totalForIncE,
                        "decreased energy" => $totalForDecE,
                        "increased appetite" => $totalForIncApt,
                        "decreased appetite" => $totalForDecApt,
                        "increased water consumption" => $totalForIncWater,
                        "decreased water consumption" => $totalForDecWater,
                        "mobility" => $totalForMobility,
                        "limping" => $totalForLimping,


                    ]

                ],
                [
                    "recent symptoms logged" => $recent_symptoms,
                ],
            ],

        ]]);
    }

    //------GET PET CBD--------//
    public function getCBD($pet_id)
    {
        $start_cbd = calculations::where('petId', $pet_id)->select('startcbd')->first();
        $max_cbd = calculations::where('petId', $pet_id)->select('maxcbd')->first();
        return response()->json(["status" => "200", "message" => "success", "data" => [
            "start cbd" => $start_cbd,
            "max cbd" => $max_cbd,

        ]]);
    }
}
