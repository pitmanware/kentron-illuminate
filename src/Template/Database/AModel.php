<?php

namespace Kentron\Template\Database;

use Illuminate\Database\Eloquent\Model;

use Kentron\Template\Database\ADbEntity;

abstract class AModel extends Model
{
    const CREATED_AT = ADbEntity::COLUMN_CREATED_AT;
    const UPDATED_AT = null;
    const DELETED_AT = ADbEntity::COLUMN_DELETED_AT;

    public $timestamps = true;

    protected $primaryKey = ADbEntity::COLUMN_ID;
}
