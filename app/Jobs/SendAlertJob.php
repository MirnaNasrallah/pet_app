<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\pet;
use App\Models\Symptom;
use App\Models\User;
use App\Notifications\sendLinkNotification;
use App\Notifications\SendLinkTwoNotification;
use App\Notifications\symptomLoggedNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notifiable;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Notifiable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    //public $message;

    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $today = Carbon::today();
        $users = User::whereDate('created_at','<=',$today->subDays(2))->get();
        foreach($users as $user)
        {
            $user->notify(new sendLinkNotification());
            $user->notify(new SendLinkTwoNotification());

        }

      // Notification::send($users, new sendLinkNotification());
    }
}
