<?php

namespace App\Http\Controllers\Parse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use DB;

/**
 *  BaseParseController - class to execute the parse query and get values from DB models
 */
class ConfigController extends Controller
{

    protected function getconfig()
    {
        $configs = DB::table('parse_configs')->get();
        $params=[];
        foreach($configs as $config){
            $params[$config->name]=$config->value;

        }
        return \Response::json(['params'=>$params]);
    }
}