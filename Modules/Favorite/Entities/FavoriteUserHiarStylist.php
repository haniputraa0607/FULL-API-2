<?php

namespace Modules\Favorite\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductModifier;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\ProductVariant\Entities\ProductVariant;

class FavoriteUserHiarStylist extends Model
{
	protected $primaryKey = 'id_favorite_use_hair_stylist';

	protected $fillable = [
		'id_user',
		'id_user_hair_stylist'
	];
}
