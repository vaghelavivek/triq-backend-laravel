<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDocument extends Model
{
    use HasFactory;
    public function service_document()
    {
        return $this->belongsTo(ServiceDocument::class,'id',"service_id")->with('service');
    }
}
