<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = ['contact_group_id', 'name', 'phone', 'notes'];

    public function group()
    {
        return $this->belongsTo(ContactGroup::class, 'contact_group_id');
    }
}
