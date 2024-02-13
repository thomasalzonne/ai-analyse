<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\EmailCampaign;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class EmailCampaignScreenshotController extends Controller
{
    public function get(Domain $domain, EmailCampaign $emailCampaign, string $device)
    {
        $screenshot = $emailCampaign->screenshot($device);

        // returns the image
        return response(file_get_contents($screenshot['private_filepath']))
            ->header('Content-Type', 'image/png');
    }
}
