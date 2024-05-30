<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function homepage(){

        return response()->json([
        'message' => 'Home page'
         ]);
   }
}