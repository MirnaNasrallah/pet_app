<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\Symptom;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\AndroidConfig;
use NotificationChannels\Fcm\Resources\AndroidFcmOptions;
use NotificationChannels\Fcm\Resources\AndroidNotification;
use NotificationChannels\Fcm\Resources\ApnsConfig;
use NotificationChannels\Fcm\Resources\ApnsFcmOptions;

class symptomLoggedNotification extends Notification
{
    use Queueable;
    //send to a great number of users

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $message;
    public $symptom;
    // public function __construct(Symptom $symptom)
    // {
    //     $this->symptom = $symptom;
    // }
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        //mail, database, sms, broadcast channel(push notification)
        //slack channel, custom channel
        //this function is used to specify which we'll use
        //we can select multiple
        //notifiable is an instance of the notifiable model
        //either user or pet
        return ['broadcast', 'database', FcmChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toBroadcast($notifiable)
    {
        $symptom = Symptom::where('id', $this->message->symptom_id)->first();
        return new BroadcastMessage([
            'title' => 'Thanks for noticing my ' . $symptom->name . ' today!',
            'text' => $symptom->id,
            'body' => $this->message->body,
        ]);
    }
    public function toFcm($notifiable)
    {
        $symptom = Symptom::where('id', $this->message->symptom_id)->first();
        return FcmMessage::create()
            ->setData([
                'symptom id' => $symptom->id,
                'message id' => $this->message->id,
                'pet id' => $symptom->pet_id,
            ])
            ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->setTitle('Thanks for noticing my ' . $symptom->name . ' today!')
                ->setBody($this->message->body)
               // ->setImage('http://example.com/url-to-image-here.png')
               )
            ->setAndroid(
                AndroidConfig::create()
                    ->setFcmOptions(AndroidFcmOptions::create()->setAnalyticsLabel('analytics'))
                    ->setNotification(AndroidNotification::create()->setColor('#0A0A0A'))
            )->setApns(
                ApnsConfig::create()
                    ->setFcmOptions(ApnsFcmOptions::create()->setAnalyticsLabel('analytics_ios'))
            )
            ;

    }
    public function toDatabase($notifiable)
    {
        $symptom = Symptom::where('id', $this->message->symptom_id)->first();
        return [
            'title' => 'Thanks for noticing my ' . $symptom->name . ' today!',
            'body' => $this->message->body,

        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
