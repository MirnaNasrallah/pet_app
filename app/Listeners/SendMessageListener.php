<?php

namespace App\Listeners;

use App\Events\SymptomLogged;
use App\Models\Message;
use App\Models\pet;
use App\Models\Symptom;
use App\Models\User;
use App\Notifications\symptomLoggedNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class SendMessageListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public $symptom;
    public function __construct(Symptom $symptom)
    {
        $this->symptom = $symptom;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(SymptomLogged $event)
    {
        $symptom = $event->symptom;

        $user_id = auth()->user()->id;

        $pet = pet::where('id', $symptom->pet_id)->where('user_id', $user_id)->first();

        if ($symptom->name == "cough" || $symptom->name == "sneeze") {

            $today = Carbon::today();

            $logs = DB::table('symptoms')
                ->select('created_at')
                ->where('user_id', auth()->user()->id)
                ->where('pet_id', $pet->id)
                ->where('name', 'cough')
                ->orWhere('name', 'sneeze')
                ->where('created_at', '>=', $today->subDays(7))
                ->distinct()
                ->get()
                ->count();

            if ($logs == 1) {

                $message = Message::create([
                    'body' => "If you notice I am coughing twice weekly or more frequently,
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

                $symptom->message = "You have logged that I coughed .$logs times this week. If you notice I am sneezing twice weekly or more frequently, it could be an indication of irritation in my upper airways. If this is a new development and/or the frequency of the sneezing is increasing, please check with my veterinarian";
                $symptom->save();

                $message = Message::create([
                    'body' => "You have logged that I coughed .$logs times this week. If you notice I am sneezing twice weekly or more frequently, it could be an indication of irritation in my upper airways. If this is a new development and/or the frequency of the sneezing is increasing, please check with my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();
                $user->notify(new symptomLoggedNotification($message));
            }  else {
                $message = Message::create([
                    'body' => "You have logged that I had .$symptom->name .$logs times this week",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();
                $user->notify(new symptomLoggedNotification($message));
            }
        } elseif ($symptom->name == "decreased energy level" || $symptom->name == "increased energy level") {
            if ($symptom->name == "decreased energy level") {
                $today = Carbon::today();

                $logs = DB::table('symptoms')
                    ->select('created_at')
                    ->where('user_id', auth()->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'decreased energy level')
                    ->where('created_at', '>=', $today->subDays(7))
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
                }
                else {
                    $message = Message::create([
                        'body' => "You have logged that I had .$symptom->name .$logs times this week",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
            } elseif ($symptom->name == "increased energy level") {
                $today = Carbon::today();

                $logs = DB::table('symptoms')
                    ->select('created_at')
                    ->where('user_id', auth()->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'increased energy level')
                    ->where('created_at', '>=', $today->subDays(7))
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
                }
                else {
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
        } elseif ($symptom->name == "decreased appetite" || $symptom->name == "increased appetite") {
            if ($symptom->name == "increased appetite") {
                $today = Carbon::today();

                $logs = DB::table('symptoms')
                    ->select('created_at')
                    ->where('user_id', auth()->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'increased appetite')
                    ->distinct()
                    ->get()
                    ->count();
                if ($symptom->streak >= 14 || $logs==1) {
                    $message = Message::create([
                        'body' => "Increases in appetite can sometimes be due to me really enjoying my food, competitive eating when there are other animals in the house, or less frequently, a medical condition.  If my increased appetite persists beyond 2 weeks and there is no apparent underlying cause, contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
                else {
                    $message = Message::create([
                        'body' => "You have logged that I had .$symptom->name .$logs times this week",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
            } elseif ($symptom->name == "decreased appetite") {
                $today = Carbon::today();

                $logs = DB::table('symptoms')
                    ->select('created_at')
                    ->where('user_id', auth()->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'decreased appetite')
                    ->distinct()
                    ->get()
                    ->count();
                if ($symptom->streak >= 3 || $logs==1) {
                    $message = Message::create([
                        'body' => "There are many reasons I could have a decreased appetite including not liking my food, GI upset, stress, or an underlying medical problem.  If my appetite is significantly decreased for more than 3 days or if I am not eating at all for more than 1 day, please contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
                else {
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
        elseif ($symptom->name == "increased water consumption" || $symptom->name == "decreased water consumption") {
            if ($symptom->name == "increased water consumption") {
                $today = Carbon::today();

                $logs = DB::table('symptoms')
                    ->select('created_at')
                    ->where('user_id', auth()->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'increased water consumption')
                    ->where('created_at', '>=', $today->subDays(7))
                    ->distinct()
                    ->get()
                    ->count();
                if ($logs >= 3 || $logs==1) {
                    $message = Message::create([
                        'body' => "Increases in water consumption can occur in warm weather, if I have been exercising more than normal or if my diet is dry such as kibble, freeze dried, or dehydrated food without water added.  There are also medical conditions that could cause me to drink more.  If I am drinking more water than normal and there is no apparent cause, please contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
                else {
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
                $today = Carbon::today();

                $logs = DB::table('symptoms')
                    ->select('created_at')
                    ->where('user_id', auth()->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'decreased water consumption')
                    ->where('created_at', '>=', $today->subDays(7))
                    ->distinct()
                    ->get()
                    ->count();
                if ($logs >= 2 || $logs==1) {
                    $message = Message::create([
                        'body' => "Decreases in water consumption can sometimes happen if you switch my food to a high moisture diet such as fresh food, rehydrated freeze dried or dehydrated food, or canned.  In these cases, decreased water consumption is normal because I’m getting most of their water from my food.  Pets that are feeling sick will also sometimes drink less.  If my water consumption has decreased and the reason is not clear, please contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    $user->notify(new symptomLoggedNotification($message));
                }
                else {
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
        elseif ($symptom->name == "mobility") {
            $today = Carbon::today();

            $logs = DB::table('symptoms')
                ->select('created_at')
                ->where('user_id', auth()->user()->id)
                ->where('pet_id', $pet->id)
                ->where('name', 'mobility')
                ->distinct()
                ->get()
                ->count();
            if ($symptom->streak >= 2 || $logs==1) {
                $message = Message::create([
                    'body' => "I am not moving around as much as I should be. It could be due to over exercise, an injury, arthritis, or back pain.  If I exercised more than normal today or yesterday, I may just need a little time to rest. Sometimes decreases in mobility are caused by my not feeling well for more internal reasons.  If my decreased mobility lasts for more than a couple of days or if I otherwise look like I am not feeling well, please contact my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();
                $user->notify(new symptomLoggedNotification($message));
            }
            else {
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
         elseif ($symptom->name == "limping" || $symptom->name == "soreness") {
            if ($symptom->streak == 1) {
                $message = Message::create([
                    'body' => "Limping is usually a sign that I am sore.  It could be due to over exercise, an injury, arthritis, or back pain.  If I exercised more than normal today or yesterday, I may just need a little time to rest.  If there is not an explanation for my limping, my limp persists for more than a few days, or I am having trouble getting around, please contact my veterinarian",
                    'symptom_id' => $symptom->id,
                    'user_id' => $user_id,
                    'pet_id' => $pet->id,

                ]);
                $user = User::where('id', $symptom->user_id)->first();

                $user->notify(new symptomLoggedNotification($message));
            } elseif ($symptom->streak  >= 2) {
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
            } elseif ($symptom->streak  >= 3) {
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
            $today = Carbon::today();

            $logs = DB::table('symptoms')
                ->select('created_at')
                ->where('user_id', auth()->user()->id)
                ->where('pet_id', $pet->id)
                ->where('name', 'seizures')
                ->where('created_at', '>=', $today->subDays(30))
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
                    $message = Message::create([
                        'body' => "Most of the time, vomiting is due to short term stomach upset resulting from my eating something I should not have.  If the vomiting persists for more than 48 hours, I am vomiting but not eating, or I otherwise seem unwell, please contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    //$user->notify(new symptomLoggedNotification($symptom));
                    $user->notify(new symptomLoggedNotification($message));
                }
                if ($symptom->name == "diarrhea") {
                    $message = Message::create([
                        'body' => "Most of the time, diarrhea is due to short term stomach upset resulting from me eating something I should not have.  If I am acting normally otherwise and am not vomiting, you can try to feed me a bland diet for a few days to help my GI tract recover.  If the diarrhea persists for more than 48 hours or I otherwise seem unwell, please contact my veterinarian",
                        'symptom_id' => $symptom->id,
                        'user_id' => $user_id,
                        'pet_id' => $pet->id,

                    ]);
                    $user = User::where('id', $symptom->user_id)->first();
                    //$user->notify(new symptomLoggedNotification($symptom));
                    $user->notify(new symptomLoggedNotification($message));
                }
            }
            if ($pet->specie == "cat") {
                $today = Carbon::today();

                $logs = DB::table('symptoms')
                    ->select('created_at')
                    ->where('user_id', auth()->user()->id)
                    ->where('pet_id', $pet->id)
                    ->where('name', 'vomit')
                    ->orWhere('name', 'diarrhea')
                    ->where('created_at', '>=', $today->subDays(7))
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
}
