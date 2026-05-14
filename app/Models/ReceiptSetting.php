<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\BaseModel;

class ReceiptSetting extends BaseModel
{
    use HasBranch;
    use HasFactory;
    protected $guarded = ['id'];

    protected $appends = [
        'payment_qr_code_url',
    ];

    public function paymentQrCodeUrl(): Attribute
    {
        return Attribute::get(function (): string {
            return $this->payment_qr_code ? asset_url_local_s3('payment_qr_code/' . $this->payment_qr_code) : '';
        });
    }
}
