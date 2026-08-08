<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class BurialScheduleController extends Controller
{
    public function index()
    {
        return Inertia::render('Clerk/BurialSchedules/IndexView');
    }
}
