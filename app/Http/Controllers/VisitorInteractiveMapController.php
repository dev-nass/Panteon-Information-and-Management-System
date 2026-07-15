<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class VisitorInteractiveMapController extends Controller
{
    public function index()
    {
        return Inertia::render('VisitorInteractiveMapView');
    }
}
