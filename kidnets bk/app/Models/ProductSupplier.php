<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSupplier extends Model
{
    protected $guarded = [];
    use HasFactory;

    public function Supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    public function Product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
