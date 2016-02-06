<?php

namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;

class Foo extends ParseModel
{
    public $table = "foo";
    protected $fillable = ['rev'];

}