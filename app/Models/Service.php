<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    public function service_document() {
        return $this->hasMany(ServiceDocument::class,"service_id");
    }
}
