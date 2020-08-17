<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryParentUnit extends Model
{
    protected $table = 'inventory_parent_unit';
    public $timestamps = false;

    protected $fillable = ['inventory_id','unit_id','parent_id','content','min_purchased'];

    public function subCategories()
    {
        return $this->hasMany(self::class, 'parent_id', 'unit_id')->with('subCategories')
            ->leftJoin('inventory_unit as iunit', 'iunit.unit_id', '=', 'inventory_parent_unit.unit_id')->orderBy('iunit.name', 'asc');
    }
}
