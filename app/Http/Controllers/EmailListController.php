<?php

namespace App\Http\Controllers;

use App\Models\EmailList;
use Illuminate\Http\Request;

class EmailListController extends Controller
{
    public function show(EmailList $emailList)
    {
        return view('pages.email.list', compact('emailList'));
    }
}
