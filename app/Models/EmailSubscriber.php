<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class EmailSubscriber extends Model
{
    use HasFactory, Searchable;

    protected $guarded = [];

    protected $casts = [
        'joined_at' => 'datetime',
        'custom_fields' => 'array',
    ];
    
    public function searchableAs(): string
    {
        return 'email_subscribers_index';
    }

    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        $array['email_list'] = $this->list->name;
        $array['email_client'] = $this->list->emailClient->name;
        foreach ($this->custom_fields as $key => $value) {
            $array['custom_fields'][$value['Key']] = $value['Value'];
            unset($array['custom_fields'][$key]);
        }

        return $array;
    }

    public function list()
    {
        return $this->belongsTo(EmailList::class, 'email_list_id');
    }
}
