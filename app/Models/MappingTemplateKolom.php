<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MappingTemplateKolom extends Model
{
    protected $table = 'mapping_template_kolom';

    public $timestamps = false;

    protected $fillable = ['template_id', 'kolom_excel', 'rekening_id'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(MappingTemplate::class, 'template_id');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class);
    }
}
