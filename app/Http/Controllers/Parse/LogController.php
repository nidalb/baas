<?php

namespace App\Http\Controllers\Parse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use DB;

/**
 *  BaseParseController - class to execute the parse query and get values from DB models
 */
class LogController extends Controller
{

    protected function getlog()
    {

        $parseLogs = DB::table('parseLogs')->orderBy('id', 'desc')->take(50)->get();
       echo '<table style="width:100%"  border="1" style="width:100%"><tr>
                <td>id</td>
                <td>request</td>
                <td>method</td>
                 <td>response</td>
                <td>user_agent</td>
                </tr>';


        foreach($parseLogs as $parseLog){
            echo '<tr>
                <td><a target="_blank" href="datalog/'.$parseLog->id.'">'.$parseLog->id.'</a></td>
                <td>'.$parseLog->request.'</td>
                <td>'.$parseLog->method.'</td>
                <td>'.substr($parseLog->response,0,1000).'</td>
                <td>'.substr($parseLog->user_agent,0,50).'</td>
            </tr>';
        }
        echo '</table>';
        dd();
    }

    protected function getdatalog($id)
    {

        $parseLog = DB::table('parseLogs')->where('id', $id)->first();
        echo '<table style="width:100%"  border="1" style="width:100%"><tr><td>id</td><td>request</td></tr>';


        foreach($parseLog as $k=>$val) {
            echo '<tr>
                <td>' .$k . '</a></td>
                <td>' . $val . '</td>

            </tr>';
        }
        echo '</table>';
        dd();
    }
}