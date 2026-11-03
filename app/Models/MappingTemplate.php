<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MappingTemplate extends Model
{
    protected $table = 'mapping_template';

    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = 'diubah_pada';

    protected $fillable = ['nama', 'kolom_skpd'];

    public function kolom(): HasMany
    {
        return $this->hasMany(MappingTemplateKolom::class, 'template_id');
    }
}
