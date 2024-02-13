<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class EmailCampaignOpen extends Model
{
    use HasFactory, Searchable;

    protected $guarded = [];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function searchableAs(): string
    {
        return 'email_opens_index';
    }

    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        $array['email_campaign'] = $this->emailCampaign->name;
        $array['email_client'] = $this->emailCampaign->emailClient->name;
        $array['subscriber'] = $this->subscriber()?->get();
        $array['_geo'] = [
            'lat' => $this->latitude,
            'lon' => $this->longitude,
        ];

        return $array;
    }

    public function emailCampaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function subscriber()
    {
        if (!$this->email_subscriber_id) {
            $subscriber = EmailSubscriber::where('email_address', $this->email)->where('email_list_id', $this->email_list_id)->first();
            if (!$subscriber) {
                return null;
            }
            $this->update(['email_subscriber_id' => $subscriber->id]);
        }

        return $this->belongsTo(EmailSubscriber::class, 'email_subscriber_id');
    }

}
