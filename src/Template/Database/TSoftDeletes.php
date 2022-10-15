<?php
declare(strict_types=1);

namespace Kentron\Template\Database;

use Illuminate\Database\Eloquent\SoftDeletes;

trait TSoftDeletes
{
    use SoftDeletes;
}
