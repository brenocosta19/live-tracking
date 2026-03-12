<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\LocationUpdated;

class LocationController extends Controller
{
    public function index()
    {
        return view('location-tracker');
    }
    
    public function update(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        return view('location-tracker');
    }
}
