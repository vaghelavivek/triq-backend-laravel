<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    public function order_documents() {
        return $this->hasMany(OrderDocument::class,"order_id");
    }
    public function order_updates() {
        return $this->hasMany(OrderUpdate::class,"order_id")->with('user');
    }
    public function user() {
        return $this->belongsTo(User::class,"user_id");
    }
    public function service() {
        return $this->belongsTo(Service::class,"service_id");
    }
}
