<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\EmailClient;
use Illuminate\Http\Request;

class EmailClientController extends Controller
{
    public function show(EmailClient $emailClient)
    {
        return view('pages.email.client', compact('emailClient'));
    }

    public function lists(Domain $domain)
    {
        $lists = $domain->emailClient->emailLists->sortBy('name');

        return view('pages.email.lists', compact('domain', 'lists'));
    }

    public function campaigns(Domain $domain)
    {
        $campaigns = $domain->emailClient->emailCampaigns->sortByDesc('sent_date')->take(50);

        return view('pages.email.campaigns', compact('domain', 'campaigns'));
    }
}
