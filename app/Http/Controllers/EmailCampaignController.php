<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\EmailCampaign;
use Illuminate\Http\Request;

class EmailCampaignController extends Controller
{
    public function show(domain $domain, EmailCampaign $emailCampaign)
    {
        return view('pages.email.campaign', compact('emailCampaign'));
    }
}
