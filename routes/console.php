<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --once --tries=3')
    ->everyMinute();
