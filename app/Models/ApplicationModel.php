<?php

namespace App\Models;

use \Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationModel extends Model
{
    public $id;

    /**
     * No models are removed from the database upon deletion
     */
    use SoftDeletes;
}
