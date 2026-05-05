<?php
use Illuminate\Support\Facades\Mail;

Mail::raw('Test email from Laravel', function($msg) {
    $msg->to('idrissabba14@gmail.com')->subject('Test Email');
});
echo "Mail sent!";
