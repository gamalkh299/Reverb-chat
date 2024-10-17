<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendMessageFromLaravel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-message-from-laravel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        //ask for receiver email
        $receiver_email = $this->ask('Enter receiver email');
        //ask for message
        $message = $this->ask('Enter message');
        //find the user with the email
        $receiver = \App\Models\User::query()->where('email', $receiver_email)->first();
        //if user not found
        if (!$receiver) {
            $this->error('User not found');
            return;
        }
        //create the message
        $message = \App\Models\ChatMessage::query()
            ->create([
                'receiver_id' => $receiver->id,
                'sender_id' => 1,
                'message' => $message
            ]);
        //broadcast the message
        broadcast(new \App\Events\MessageSent($message));
        //show success message
        $this->info('Message sent');

    }
}
