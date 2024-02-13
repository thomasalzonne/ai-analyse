<?php

namespace App\Models;

use App\Models\EmailClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailList extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'remote_id';
    }

    public function emailClient()
    {
        return $this->belongsTo(EmailClient::class, 'email_client_id');
    }
}
