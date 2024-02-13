<?php

namespace App\Models;

use App\Models\EmailCampaignClick;
use App\Models\EmailCampaignOpen;
use App\Models\EmailClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Browsershot\Browsershot;

class EmailCampaign extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'remote_id';
    }

    public function searchableAs(): string
    {
        return 'email_campaigns_index';
    }

    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        $array['email_client'] = $this->emailClient->name;

        return $array;
    }

    public function emailClient()
    {
        return $this->belongsTo(EmailClient::class, 'email_client_id');
    }

    public function opens()
    {
        return $this->hasMany(EmailCampaignOpen::class, 'email_campaign_id');
    }

    public function clicks()
    {
        return $this->hasMany(EmailCampaignClick::class, 'email_campaign_id');
    }

    public function make_screenshot($device = 'desktop', $filepath = null)
    {
        switch ($device) {
            case 'desktop':
                $width = 800;
                $height = 0;
                break;
            case 'mobile':
                $width = 375;
                $height = 0;
                break;
            default:
                $width = 800;
                $height = 0;
                break;
        }

        $url = $this->webversion_url;
        if (!$filepath) {
            $filepath = storage_path('app/public/email_campaigns/'.$this->emailClient->slug.'/'.$this->id.'/'.$device.'-'.$this->id.'.png');
        }

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0777, true);
        }
        
        Browsershot::url($url)
            ->useCookies([
                'axeptio_all_vendors' => '%2Cgoogle_analytics%2Cmatomo%2Clinkedin%2Ctwitter%2Cvimeo%2Cyoutube%2Cfacebook_pixel%2Cgoogle_ads%2Cslido%2CMatomo%2CLinkedin%2CTwitter%2CYoutube%2CGoogle_Ads%2C',
                'axeptio_authorized_vendors' => '%2Cgoogle_analytics%2Cmatomo%2Clinkedin%2Ctwitter%2Cvimeo%2Cyoutube%2Cfacebook_pixel%2Cgoogle_ads%2Cslido%2CMatomo%2CLinkedin%2CTwitter%2CYoutube%2CGoogle_Ads%2C',
                'axeptio_cookies' => '{%22$$token%22:%22of7ygq32bfrybpd0b55e%22%2C%22$$date%22:%222024-01-09T10:38:05.796Z%22%2C%22$$cookiesVersion%22:{%22name%22:%22pcronline-en%22%2C%22identifier%22:%2263fcc982fc0e5dc395ebcba4%22}%2C%22google_analytics%22:true%2C%22matomo%22:true%2C%22linkedin%22:true%2C%22twitter%22:true%2C%22vimeo%22:true%2C%22youtube%22:true%2C%22facebook_pixel%22:true%2C%22google_ads%22:true%2C%22slido%22:true%2C%22Matomo%22:true%2C%22Linkedin%22:true%2C%22Twitter%22:true%2C%22Youtube%22:true%2C%22Google_Ads%22:true%2C%22$$completed%22:true}',
            ])
            ->waitUntilNetworkIdle()
            ->windowSize($width, $height)
            ->fullPage()
            ->ignoreHttpsErrors()
            ->save($filepath);
        
        return $filepath;
    }

    public function screenshot($device = 'desktop')
    {
        $private_filepath = storage_path('app/public/email_campaigns/'.$this->emailClient->slug.'/'.$this->id.'/'.$device.'-'.$this->id.'.png');
        if (!file_exists($private_filepath)) {
            $private_filepath = $this->make_screenshot($device, $private_filepath);

        }

        $public_filepath = str_replace(storage_path('app/public'), '/storage', $private_filepath);
        $full_url = url($public_filepath);

        return compact('private_filepath', 'public_filepath', 'full_url');
    }
}
