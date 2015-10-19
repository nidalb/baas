<?php

namespace App\Http\Controllers;

use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;

class TodoController extends Controller
{
    public function __construct()
    {
        $app_id = "id";
        $rest_key = "rest_key";
        $master_key = "master_key";
        ParseClient::initialize($app_id, $rest_key, $master_key);
    }

    public function getIndex()
    {
        return view('todo');
    }

    public function getAll()
    {
        $query = new ParseQuery("todo");
        $results = $query->find();

        $resposnse = [];
        foreach ($results as $result) {
            $object = new \stdClass();
            $object->completed = $result->get('completed');
            $object->id = $result->getObjectId();
            $object->title = $result->get('title');
            $resposnse [] = $object;
        }
        return $resposnse;
    }

    public function postCreate()
    {
        $object = ParseObject::create("todo");
        $object->set("title", \Input::get('title'));
        $object->save();

        return $object->getObjectId();
    }

    public function postDelete($objectId)
    {
        $object = ParseObject::create("todo", $objectId);
        $object->destroy();
    }

    public function postUpdate($objectId)
    {
        $object = ParseObject::create("todo", $objectId);
        if (\Input::has('title')){ $object->set("title", \Input::get('title')); }
        if (\Input::has('completed')){ $object->set("completed", filter_var(\Input::get('completed'), FILTER_VALIDATE_BOOLEAN)); }

        $object->save();
    }
}
