<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index()
    {
        $domains = Domain::all();

        return view('pages.domains.index', compact('domains'));
    }

    public function show(Domain $domain)
    {
        return view('pages.domains.show', compact('domain'));
    }
}
