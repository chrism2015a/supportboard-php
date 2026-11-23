<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ChatbotController extends Controller
{
    public function index()
    {
        return view('chatbots.index');
    }
}
