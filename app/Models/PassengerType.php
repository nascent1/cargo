<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stub model for passenger types (passenger booking domain).
 * Created to prevent test framework crashes when CreatesTestData
 * references this model in seedReferenceData().
 */
class PassengerType extends Model
{
    protected $fillable = ['code', 'name', 'is_default', 'min_age', 'max_age'];
}
