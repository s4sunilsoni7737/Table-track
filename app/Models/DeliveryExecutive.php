<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\BaseModel;

class DeliveryExecutive extends BaseModel
{
    use HasBranch;
    use Notifiable;

    protected $guarded = ['id'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderBy('id', 'desc');
    }

    public function orderCashCollections(): HasMany
    {
        return $this->hasMany(OrderCashCollection::class)->orderBy('id', 'desc');
    }

    public function cashSettlements(): HasMany
    {
        return $this->hasMany(DeliveryCashSettlement::class)->orderBy('id', 'desc');
    }

    public function routeNotificationForMail($notification): ?string
    {
        return $this->email ?: null;
    }
}
