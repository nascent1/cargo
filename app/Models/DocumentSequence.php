<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The next number of one document series in one year (quotations, invoices, credit notes…).
 * Numbers are taken only through App\Services\DocumentNumberService, which locks the row.
 */
class DocumentSequence extends Model
{
    protected $fillable = ['series', 'year', 'prefix', 'next_number', 'padding'];

    protected $casts = [
        'year' => 'integer',
        'next_number' => 'integer',
        'padding' => 'integer',
    ];
}
