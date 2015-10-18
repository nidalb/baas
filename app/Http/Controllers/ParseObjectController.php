<?php

namespace App\Http\Controllers;

use Carbon\Carbon;


/*
 * Object Controller based on Parse Rest API documentation
 * https://parse.com/docs/rest/guide#quick-reference-objects
*/
class ParseObjectController extends Controller
{
    /**
     * Creating Objects
     * https://parse.com/docs/rest/guide#objects-creating-objects
     *
     * @param $className
     * @return mixed
     */
    public function create($className)
    {
    }

    /**
     * Retrieving Objects
     * https://parse.com/docs/rest/guide#objects-retrieving-objects
     *
     * @param $className
     * @param $objectId
     * @return mixed
     */
    public function getById($className, $objectId)
    {
    }

    /**
     * Updating Objects
     * https://parse.com/docs/rest/guide#objects-updating-objects
     *
     * @param $className
     * @param $objectId
     */
    public function update($className, $objectId)
    {
    }

    /**
     * Queries
     * https://parse.com/docs/rest/guide#queries
     *
     * @param $className
     * @return mixed
     */
    public function get($className)
    {
    }

    /**
     * Deleting Objects
     * https://parse.com/docs/rest/guide#objects-deleting-objects
     *
     * @param $className
     * @param $objectId
     * @return mixed
     */
    public function delete($className, $objectId)  //delete all row from db
    {
    }
}