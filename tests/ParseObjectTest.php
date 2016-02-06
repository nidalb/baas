<?php

use Parse\Internal\SetOperation;
use Parse\ParseObject;
use Parse\ParseQuery;

require_once 'ParseTestHelper.php';

class ParseObjectTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        ParseTestHelper::setUp();
    }

    public function tearDown()
    {
        ParseTestHelper::tearDown();
    }

    public function testCreate()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('test', 'test');
        $obj->save();
    }

    public function dtestUpdate()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $obj->set('foo', 'changed');
        $obj->save();
        $this->assertEquals(
            $obj->foo, 'changed',
            'Update should have succeeded'
        );
    }

    public function dtestSaveCycle()
    {
        $a = ParseObject::create('TestObject');
        $b = ParseObject::create('TestObject');
        $a->set('b', $b);
        $a->save();
        $this->assertFalse($a->isDirty());
        $this->assertNotNull($a->getObjectId());
        $this->assertNotNull($b->getObjectId());
        $b->set('a', $a);
        $b->save();
        $this->assertEquals($b, $a->get('b'));
        $this->assertEquals($a, $b->get('a'));
    }

    public function dtestReturnedObjectIsAParseObject()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();

        $query = new ParseQuery('TestObject');
        $returnedObject = $query->get($obj->getObjectId());
        $this->assertTrue(
            $returnedObject instanceof ParseObject,
            'Returned object was not a ParseObject'
        );
        $this->assertEquals(
            'bar', $returnedObject->foo,
            'Value of foo was not saved.'
        );
    }

    public function dtestFetch()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('test', 'test');
        $obj->save();
        $t2 = ParseObject::create('TestObject', $obj->getObjectId());
        $t2->fetch();
        $this->assertEquals('test', $t2->get('test'), 'Fetch failed.');
    }

    public function dtestDelete()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $obj->destroy();
        $query = new ParseQuery('TestObject');
        $this->setExpectedException('Parse\ParseException', 'Object not found');
        $out = $query->get($obj->getObjectId());
    }

    public function dtestFind()
    {
        ParseTestHelper::clearClass('TestObject');
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $query = new ParseQuery('TestObject');
        $query->equalTo('foo', 'bar');
        $response = $query->count();
        $this->assertTrue($response == 1);
    }

    public function dtestRelationalFields()
    {
        ParseTestHelper::clearClass("Item");
        ParseTestHelper::clearClass("Container");

        $item = ParseObject::create("Item");
        $item->set("property", "x");
        $item->save();

        $container = ParseObject::create("Container");
        /* relation name not fk name */
        $container->set("item", $item);
        $container->save();

        $query = new ParseQuery("Container");
        $query->includeKey("item");
        $containerAgain = $query->get($container->getObjectId());
        $itemAgain = $containerAgain->get("item");
        $this->assertEquals("x", $itemAgain->get("property"));

        $query->equalTo("item", $item);
        $results = $query->find();
        $this->assertEquals(1, count($results));
    }

    public function dtestRelationDeletion()
    {
        ParseTestHelper::clearClass("SimpleObject");
        ParseTestHelper::clearClass("Child");
        $simple = ParseObject::create("SimpleObject");
        $child = ParseObject::create("Child");
        $simple->set('child', $child);
        $simple->save();
        $this->assertNotNull($simple->get('child'));
        $simple->delete('child');
        $this->assertNull($simple->get('child'));
        $this->assertTrue($simple->isDirty());
        $this->assertTrue($simple->isKeyDirty('child'));
        $simple->save();
        $this->assertNull($simple->get('child'));
        $this->assertFalse($simple->isDirty());
        $this->assertFalse($simple->isKeyDirty('child'));

        $query = new ParseQuery("SimpleObject");
        $simpleAgain = $query->get($simple->getObjectId());
        $this->assertNull($simpleAgain->get('child'));
    }


    public function dtestSaveAddsNoDataKeys()
    {
        $obj = ParseObject::create('TestObject');
        $obj->save();
        $json = $obj->_encode();
        $data = get_object_vars(json_decode($json));
        unset($data['objectId']);
        unset($data['createdAt']);
        unset($data['updatedAt']);
        $this->assertEquals(0, count($data));
    }

    public function dtestRecursiveSave()
    {
        ParseTestHelper::clearClass('Container');
        ParseTestHelper::clearClass('Item');
        $a = ParseObject::create('Container');
        $b = ParseObject::create('Item');
        $b->set('foo', 'bar');
        $a->set('item', $b);
        $a->save();
        $query = new ParseQuery('Container');
        $result = $query->find();
        $this->assertEquals(1, count($result));
        $containerAgain = $result[0];
        $itemAgain = $containerAgain->get('item');
        $itemAgain->fetch();
        $this->assertEquals('bar', $itemAgain->get('foo'));
    }

    public function dtestFetchRemovesOldFields()
    {
        $obj = ParseObject::create('SimpleObject');
        $obj->set('foo', 'bar');
        $obj->set('test', 'foo');
        $obj->save();

        $query = new ParseQuery('SimpleObject');
        $object1 = $query->get($obj->getObjectId());
        $object2 = $query->get($obj->getObjectId());
        $this->assertEquals('foo', $object1->get('test'));
        $this->assertEquals('foo', $object2->get('test'));
        $object2->delete('test');
        $this->assertEquals('foo', $object1->get('test'));
        $object2->save();
        $object1->fetch();
        $this->assertEquals(null, $object1->get('test'));
        $this->assertEquals(null, $object2->get('test'));
        $this->assertEquals('bar', $object1->get('foo'));
        $this->assertEquals('bar', $object2->get('foo'));
    }

    public function dtestCreatedAtAndUpdatedAtExposed()
    {
        $obj = ParseObject::create('TestObject');
        $obj->save();
        $this->assertNotNull($obj->getObjectId());
        $this->assertNotNull($obj->getCreatedAt());
        $this->assertNotNull($obj->getUpdatedAt());
    }

    public function dtestCreatedAtDoesNotChange()
    {
        $obj = ParseObject::create('TestObject');
        $obj->save();
        $this->assertNotNull($obj->getObjectId());
        $objAgain = ParseObject::create('TestObject', $obj->getObjectId());
        $objAgain->fetch();
        $this->assertEquals(
            $obj->getCreatedAt(), $objAgain->getCreatedAt()
        );
    }

    public function dtestUpdatedAtGetsUpdated()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $this->assertNotNull($obj->getUpdatedAt());
        $firstUpdate = $obj->getUpdatedAt();
        // Parse is so fast, this test was flaky as the \DateTimes were equal.
        sleep(1);
        $obj->set('foo', 'baz');
        $obj->save();
        $this->assertNotEquals($obj->getUpdatedAt(), $firstUpdate);
    }

    public function dtestCreatedAtIsReasonable()
    {
        $startTime = new \DateTime();
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $endTime = new \DateTime();
        $startDiff = abs(
            $startTime->getTimestamp() - $obj->getCreatedAt()->getTimestamp()
        );
        $endDiff = abs(
            $endTime->getTimestamp() - $obj->getCreatedAt()->getTimestamp()
        );
        $this->assertLessThan(5000000, $startDiff);
        $this->assertLessThan(5000000, $endDiff);
    }

    public function dtestCanSetNull()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', null);
        $obj->save();
        $this->assertEquals(null, $obj->get('foo'));
    }

    public function dtestCanSetBoolean()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('yes', true);
        $obj->set('no', false);
        $obj->save();
        $this->assertTrue($obj->get('yes'));
        $this->assertFalse($obj->get('no'));
    }

    public function dtestInvalidClassName()
    {
        $obj = ParseObject::create('Foo^bar');
        $this->setExpectedException('Parse\ParseException', 'bad characters in classname');
        $obj->save();
    }

    public function dtestInvalidKeyName()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo^bar', 'baz');
        $this->setExpectedException(
            'Parse\ParseException',
            'invalid field name'
        );
        $obj->save();
    }

    public function dtestSimpleFieldDeletion()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set('foo', 'bar');
        $obj->save();
        $obj->delete('foo');
        $this->assertFalse($obj->has('foo'), 'foo should have been unset.');
        $this->assertTrue($obj->isKeyDirty('foo'), 'foo should be dirty.');
        $this->assertTrue($obj->isDirty(), 'the whole object should be dirty.');
        $obj->save();
        $this->assertFalse($obj->has('foo'), 'foo should have been unset.');
        $this->assertFalse($obj->isKeyDirty('foo'), 'object was just saved.');
        $this->assertFalse($obj->isDirty(), 'object was just saved.');

        $query = new ParseQuery("TestObject");
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('foo'), 'foo was not removed.');
    }

    public function dtestFieldDeletionBeforeFirstSave()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->delete('foo');
        $this->assertFalse($obj->has('foo'), 'foo should have been unset.');
        $this->assertTrue($obj->isKeyDirty('foo'), 'foo should be dirty.');
        $this->assertTrue($obj->isDirty(), 'the whole object should be dirty.');
        $obj->save();
        $this->assertFalse($obj->has('foo'), 'foo should have been unset.');
        $this->assertFalse($obj->isKeyDirty('foo'), 'object was just saved.');
        $this->assertFalse($obj->isDirty(), 'object was just saved.');
    }

    public function dtestDeletedKeysGetCleared()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->delete('foo');
        $obj->save();
        $obj->set('foo', 'baz');
        $obj->save();

        $query = new ParseQuery("TestObject");
        $result = $query->get($obj->getObjectId());
        $this->assertEquals('baz', $result->get('foo'));
    }

    public function dtestSettingAfterDeleting()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $obj->delete('foo');
        $obj->set('foo', 'baz');
        $obj->save();

        $query = new ParseQuery("TestObject");
        $result = $query->get($obj->getObjectId());
        $this->assertEquals('baz', $result->get('foo'));
    }

    public function dtestDirtyKeys()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('cat', 'good');
        $obj->set('dog', 'bad');
        $obj->save();
        $this->assertFalse($obj->isDirty());
        $this->assertFalse($obj->isKeyDirty('cat'));
        $this->assertFalse($obj->isKeyDirty('dog'));
        $obj->set('dog', 'okay');
        $this->assertTrue($obj->isKeyDirty('dog'));
        $this->assertTrue($obj->isDirty());
    }

    public function dtestOldAttributeUnsetThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 3);
        $obj->save();
        $obj->delete('x');
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestNewAttributeUnsetThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 5);
        $obj->delete('x');
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestUnknownAttributeUnsetThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->delete('x');
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestoldAttributeUnsetThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 3);
        $obj->save();
        $obj->delete('x');
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestNewAttributeUnsetThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 5);
        $obj->delete('x');
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestUnknownAttributeUnsetThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->delete('x');
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestoldAttributeClearThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 3);
        $obj->save();
        $obj->clear();
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestNewAttributeClearThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 5);
        $obj->clear();
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestUnknownAttributeClearThenUnset()
    {
        $obj = ParseObject::create('TestObject');
        $obj->clear();
        $obj->delete('x');
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestoldAttributeClearThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 3);
        $obj->save();
        $obj->clear();
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestNewAttributeClearThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('x', 5);
        $obj->clear();
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function dtestUnknownAttributeClearThenClear()
    {
        $obj = ParseObject::create('TestObject');
        $obj->clear();
        $obj->clear();
        $obj->save();
        $this->assertFalse($obj->has('x'));
        $this->assertNull($obj->get('x'));

        $query = new ParseQuery('TestObject');
        $result = $query->get($obj->getObjectId());
        $this->assertFalse($result->has('x'));
        $this->assertNull($result->get('x'));
    }

    public function nytestSavingChildrenInArray()
    {
        ParseTestHelper::clearClass("Parent");
        ParseTestHelper::clearClass("Child");
        $parent = ParseObject::create("Parent");
        $child1 = ParseObject::create("Child");
        $child2 = ParseObject::create("Child");
        $child1->set('name', 'tyrian');
        $child2->set('name', 'cersei');
        $parent->setArray('children', [$child1, $child2]);
        $parent->save();

        $query = new ParseQuery("Child");
        $query->ascending('name');
        $results = $query->find();
        $this->assertEquals(2, count($results));
        $this->assertEquals('cersei', $results[0]->get('name'));
        $this->assertEquals('tyrian', $results[1]->get('name'));
    }

    public function nytestManySaveAfterAFailure()
    {
        $obj = ParseObject::create("TestObject");
        $obj->set("number", "qwe");
        $obj->save();
        $obj2 = ParseObject::create("TestObject");
        $obj2->set("number", "two");
        $exceptions = 0;
        try {
            $obj2->save();
        } catch (\Parse\ParseException $pe) {
            $exceptions++;
        }
        $obj2->set('foo', 'bar');
        try {
            $obj2->save();
        } catch (\Parse\ParseException $pe) {
            $exceptions++;
        }
        $obj2->set('foo', 'baz');
        try {
            $obj2->save();
        } catch (\Parse\ParseException $pe) {
            $exceptions++;
        }
        $obj2->set('number', 3);
        $obj2->save();
        if ($exceptions != 3) {
            $this->fail("Did not cause expected # of exceptions.");
        }
    }

    public function dtestNewKeyIsDirtyAfterSave()
    {
        $obj = ParseObject::create("TestObject");
        $obj->save();
        $obj->set('content', 'x');
        $obj->fetch();
        $this->assertTrue($obj->isKeyDirty('content'));
    }

    public function nytestAddWithAnObject()
    {
        $parent = ParseObject::create("Person");
        $child = ParseObject::create("Person");
        $child->save();
        $parent->add("children", [$child]);
        $parent->save();

        $query = new ParseQuery("Person");
        $parentAgain = $query->get($parent->getObjectId());
        $children = $parentAgain->get("children");
        $this->assertEquals(
            $child->getObjectId(), $children[0]->getObjectId()
        );
    }

    public function nytestAddUnique()
    {
        $obj = ParseObject::create("TestObject");
        $obj->setArray('arr', [1, 2, 3]);
        $obj->addUnique('arr', [1]);
        $this->assertEquals(3, count($obj->get('arr')));
        $obj->addUnique('arr', [4]);
        $this->assertEquals(4, count($obj->get('arr')));

        $obj->save();
        $obj2 = ParseObject::create("TestObject");
        $obj3 = ParseObject::create("TestObject");
        $obj2->save();
        $obj3->save();

        $obj4 = ParseObject::create("TestObject");
        $obj4->setArray('parseObjects', [$obj, $obj2]);
        $obj4->save();
        $obj4->addUnique('parseObjects', [$obj3]);
        $this->assertEquals(3, count($obj4->get('parseObjects')));
        $obj4->addUnique('parseObjects', [$obj2]);
        $this->assertEquals(3, count($obj4->get('parseObjects')));
    }

    public function dtestToJSONSavedObject()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $obj->save();
        $json = $obj->_encode();
        $decoded = json_decode($json);
        $this->assertTrue(isset($decoded->objectId));
        $this->assertTrue(isset($decoded->createdAt));
        $this->assertTrue(isset($decoded->updatedAt));
        $this->assertTrue(isset($decoded->foo));
    }

    public function dtestToJSONUnsavedObject()
    {
        $obj = ParseObject::create('TestObject');
        $obj->set('foo', 'bar');
        $json = $obj->_encode();
        $decoded = json_decode($json);
        $this->assertFalse(isset($decoded->objectId));
        $this->assertFalse(isset($decoded->createdAt));
        $this->assertFalse(isset($decoded->updatedAt));
        $this->assertTrue(isset($decoded->foo));
    }

    public function nytestRemoveOperation()
    {
        $obj = ParseObject::create('TestObject');
        $obj->setArray('arr', [1, 2, 3]);
        $obj->save();
        $this->assertEquals(3, count($obj->get('arr')));
        $obj->remove('arr', 1);
        $this->assertEquals(2, count($obj->get('arr')));
        $obj->remove('arr', 1);
        $obj->save();
        $query = new ParseQuery("TestObject");
        $objAgain = $query->get($obj->getObjectId());
        $this->assertEquals(2, count($objAgain->get('arr')));
        $objAgain->remove('arr', 2);
        $this->assertEquals(1, count($objAgain->get('arr')));
    }

    public function nytestRemoveOperationWithParseObjects()
    {
        $o1 = ParseObject::create('TestObject');
        $o2 = ParseObject::create('TestObject');
        $o3 = ParseObject::create('TestObject');
        ParseObject::saveAll([$o1, $o2, $o3]);
        $obj = ParseObject::create('TestObject');
        $obj->setArray('objs', [$o1, $o2, $o3]);
        $obj->save();
        $this->assertEquals(3, count($obj->get('objs')));
        $obj->remove('objs', $o3);
        $this->assertEquals(2, count($obj->get('objs')));
        $obj->remove('objs', $o3);
        $obj->save();
        $query = new ParseQuery("TestObject");
        $objAgain = $query->get($obj->getObjectId());
        $this->assertEquals(2, count($objAgain->get('objs')));
        $objAgain->remove('objs', $o2);
        $this->assertEquals(1, count($objAgain->get('objs')));
    }

    public function dtestDestroyAll()
    {
        ParseTestHelper::clearClass("TestObject");
        $o1 = ParseObject::create('TestObject');
        $o2 = ParseObject::create('TestObject');
        $o3 = ParseObject::create('TestObject');
        ParseObject::saveAll([$o1, $o2, $o3]);
        ParseObject::destroyAll([$o1, $o2, $o3]);
        $query = new ParseQuery("TestObject");
        $results = $query->find();
        $this->assertEquals(0, count($results));
    }

    public function nytestEmptyArray()
    {
        $obj = ParseObject::create('TestObject');
        $obj->setArray('baz', []);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $returnedObject = $query->get($obj->getObjectId());
        $this->assertTrue(
            is_array($returnedObject->get('baz')),
            'Value was not stored as an array.'
        );
        $this->assertEquals(0, count($returnedObject->get('baz')));
    }

    public function nytestArraySetAndAdd()
    {
        $obj = ParseObject::create('TestObject');
        $obj->setArray('arrayfield', ['a', 'b']);
        $obj->save();
        $obj->add('arrayfield', ['c', 'd', 'e']);
        $obj->save();
    }

    public function dtestObjectIsDirty()
    {
        $obj = ParseObject::create('TestObject');
        $key1 = 'awesome';
        $key2 = 'great';
        $key3 = 'arrayKey';
        $value1 = 'very true';
        $value2 = true;

        $obj->set($key1, $value1);
        $this->assertTrue($obj->isKeyDirty($key1));
        $this->assertFalse($obj->isKeyDirty($key2));
        $this->assertTrue($obj->isDirty());

        $obj->save();
        $this->assertFalse($obj->isKeyDirty($key1));
        $this->assertFalse($obj->isKeyDirty($key2));
        $this->assertFalse($obj->isDirty());

        $obj->set($key2, $value2);
        $this->assertTrue($obj->isKeyDirty($key2));
        $this->assertFalse($obj->isKeyDirty($key1));
        $this->assertTrue($obj->isDirty());

        $query = new ParseQuery('TestObject');
        $queriedObj = $query->get($obj->getObjectId());
        $this->assertEquals($value1, $queriedObj->get($key1));
        $this->assertFalse($queriedObj->get($key2) === $value2);

        // check dirtiness of queried item
        $this->assertFalse($queriedObj->isKeyDirty($key1));
        $this->assertFalse($queriedObj->isKeyDirty($key2));
        $this->assertFalse($queriedObj->isDirty());

        $obj->save();
        $queriedObj = $query->get($obj->getObjectId());
        $this->assertEquals($value1, $queriedObj->get($key1));
        $this->assertEquals($value2, $queriedObj->get($key2));
        $this->assertFalse($queriedObj->isKeyDirty($key1));
        $this->assertFalse($queriedObj->isKeyDirty($key2));
        $this->assertFalse($queriedObj->isDirty());

//        // check array
//        $obj->add($key3, [$value1, $value2, $value1]);
//        $this->assertTrue($obj->isDirty());
//
//        $obj->save();
//        $this->assertFalse($obj->isDirty());
    }

    public function dtestObjectIsDirtyWithChildren()
    {
        $obj = ParseObject::create('Sito');
        $key = 'testKey';
        $childKey = 'testChildKey';
        $childSimultaneousKey = 'testChildKeySimultaneous';
        $value = 'someRandomValue';
        $child = ParseObject::create('Sito');
        $childSimultaneous = ParseObject::create('Sito');
        $childArray1 = ParseObject::create('Sito');
        $childArray2 = ParseObject::create('Sito');

        $child->set('randomKey', 'randomValue');
        $this->assertTrue($child->isDirty());

        $obj->set($key, $value);
        $this->assertTrue($obj->isDirty());

        $obj->save();
        $this->assertFalse($obj->isDirty());

        $obj->set($childKey, $child);
        $this->assertTrue($obj->isKeyDirty($childKey));
        $this->assertTrue($obj->isDirty());

        // check when child is saved, parent should still be dirty
        $child->save();
        $this->assertFalse($child->isDirty());
        $this->assertTrue($obj->isDirty());

        $obj->save();
        $this->assertFalse($child->isDirty());
        $this->assertFalse($obj->isDirty());

        $childSimultaneous->set('randomKey', 'randomValue');
        $obj->set($childSimultaneousKey, $childSimultaneous);
        $this->assertTrue($obj->isDirty());

        // check simultaneous save
        $obj->save();
        $this->assertFalse($obj->isDirty());
        $this->assertFalse($childSimultaneous->isDirty());
    }

    public function dtestSaveAll()
    {
        ParseTestHelper::clearClass("TestObject");
        $objs = [];
        for ($i = 1; $i <= 90; $i++) {
            $obj = ParseObject::create('TestObject');
            $obj->set('test', 'test');
            $objs[] = $obj;
        }
        ParseObject::saveAll($objs);
        $query = new ParseQuery('TestObject');
        $result = $query->find();
        $this->assertEquals(90, count($result));
    }

    public function nytestEmptyObjectsAndArrays()
    {
        $obj = ParseObject::create('TestObject');
        $obj->setArray('arr', []);
        $obj->setAssociativeArray('obj', []);
        $saveOpArray = new SetOperation([]);
        $saveOpAssoc = new SetOperation([], true);
        $this->assertTrue(
            is_array($saveOpArray->_encode()), "Value should be array."
        );
        $this->assertTrue(
            is_object($saveOpAssoc->_encode()), "Value should be object."
        );
        $obj->save();
        $obj->setAssociativeArray(
            'obj', [
            'foo' => 'bar',
            'baz' => 'yay',
            ]
        );
        $obj->save();
        $query = new ParseQuery('TestObject');
        $objAgain = $query->get($obj->getObjectId());
        $this->assertTrue(is_array($objAgain->get('arr')));
        $this->assertTrue(is_array($objAgain->get('obj')));
        $this->assertEquals('bar', $objAgain->get('obj')['foo']);
        $this->assertEquals('yay', $objAgain->get('obj')['baz']);
    }

    public function dtestBatchSaveExceptions()
    {
        $obj1 = ParseObject::create("TestObject");
        $obj2 = ParseObject::create("TestObject");
        $obj1->set("fos^^co", "hi");
        $obj2->set("fo^^mo", "hi");
        try {
            ParseObject::saveAll([$obj1, $obj2]);
            $this->fail("Save should have failed.");
        } catch (\Parse\ParseAggregateException $ex) {
            $errors = $ex->getErrors();
            $this->assertContains("invalid field name", $errors[0]['error']);
            $this->assertContains("invalid field name", $errors[1]['error']);
        }
    }

    public function dtestFetchAll()
    {
        $obj1 = ParseObject::create("TestObject");
        $obj2 = ParseObject::create("TestObject");
        $obj3 = ParseObject::create("TestObject");
        $obj1->set("foo", "bar");
        $obj2->set("foo", "bar");
        $obj3->set("foo", "bar");
        ParseObject::saveAll([$obj1, $obj2, $obj3]);
        $newObj1 = ParseObject::create("TestObject", $obj1->getObjectId());
        $newObj2 = ParseObject::create("TestObject", $obj2->getObjectId());
        $newObj3 = ParseObject::create("TestObject", $obj3->getObjectId());
        $results = ParseObject::fetchAll([$newObj1, $newObj2, $newObj3]);
        $this->assertEquals(3, count($results));
        $this->assertEquals("bar", $results[0]->get("foo"));
        $this->assertEquals("bar", $results[1]->get("foo"));
        $this->assertEquals("bar", $results[2]->get("foo"));
    }

    public function dtestDatetimeHandling()
    {
        $date = new DateTime('2014-04-30T12:34:56Z');
        $obj = ParseObject::create('TestObject');
        $obj->set('f8', $date);
        $obj->save();
        $query = new ParseQuery('TestObject');
        $objAgain = $query->get($obj->getObjectId());
        $dateAgain = $objAgain->get('f8');
        $this->assertTrue($date->getTimestamp() == $dateAgain->getTimestamp());
    }
}
