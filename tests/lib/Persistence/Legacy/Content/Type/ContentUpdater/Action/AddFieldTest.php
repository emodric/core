<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Mapper as ContentMapper;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;
use Ibexa\Core\Persistence\Legacy\Content\StorageHandler;
use Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action\AddField;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

/**
 * Test case for Content Type Updater.
 */
class AddFieldTest extends TestCase
{
    /**
     * Content gateway mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Gateway
     */
    protected $contentGatewayMock;

    /**
     * Content gateway mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\StorageHandler
     */
    protected $contentStorageHandlerMock;

    /**
     * FieldValue converter mock.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter
     */
    protected $fieldValueConverterMock;

    /** @var \Ibexa\Core\Persistence\Legacy\Content\Mapper */
    protected $contentMapperMock;

    /**
     * AddField action to test.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action\AddField
     */
    protected $addFieldAction;

    /**
     * @covers \Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater::__construct
     */
    public function testConstructor()
    {
        $action = new AddField(
            $this->getContentGatewayMock(),
            $this->getFieldDefinitionFixture(),
            $this->getFieldValueConverterMock(),
            $this->getContentStorageHandlerMock(),
            $this->getContentMapperMock()
        );

        $this->assertInstanceOf(AddField::class, $action);
    }

    public function testApplySingleVersionSingleTranslation()
    {
        $contentId = 42;
        $versionNumbers = [1];
        $content = $this->getContentFixture(1, ['cro-HR']);
        $action = $this->getMockedAction(['insertField']);

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->with($this->equalTo($contentId))
            ->will($this->returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->with($this->equalTo([['id' => $contentId, 'version' => 1]]))
            ->will($this->returnValue([]));

        $this->getContentGatewayMock()
            ->expects($this->at(2))
            ->method('load')
            ->with($contentId, 1)
            ->will($this->returnValue([]));

        $this->getContentMapperMock()
            ->expects($this->once())
            ->method('extractContentFromRows')
            ->with([], [])
            ->will($this->returnValue([$content]));

        $action
            ->expects($this->once())
            ->method('insertField')
            ->with($content, $this->getFieldReference(null, 1, 'cro-HR'))
            ->will($this->returnValue('fieldId1'));

        $action->apply($contentId);
    }

    public function testApplySingleVersionMultipleTranslations()
    {
        $contentId = 42;
        $versionNumbers = [1];
        $content = $this->getContentFixture(1, ['eng-GB', 'ger-DE']);
        $action = $this->getMockedAction(['insertField']);

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->with($this->equalTo($contentId))
            ->will($this->returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->with($this->equalTo([['id' => $contentId, 'version' => 1]]))
            ->will($this->returnValue([]));

        $this->getContentGatewayMock()
            ->expects($this->at(2))
            ->method('load')
            ->with($contentId, 1)
            ->will($this->returnValue([]));

        $this->getContentMapperMock()
            ->expects($this->once())
            ->method('extractContentFromRows')
            ->with([], [])
            ->will($this->returnValue([$content]));

        $action
            ->expects($this->at(0))
            ->method('insertField')
            ->with($content, $this->getFieldReference(null, 1, 'eng-GB'))
            ->will($this->returnValue('fieldId1'));

        $action
            ->expects($this->at(1))
            ->method('insertField')
            ->with($content, $this->getFieldReference(null, 1, 'ger-DE'))
            ->will($this->returnValue('fieldId2'));

        $action->apply($contentId);
    }

    public function testApplyMultipleVersionsSingleTranslation()
    {
        $contentId = 42;
        $versionNumbers = [1, 2];
        $content1 = $this->getContentFixture(1, ['eng-GB']);
        $content2 = $this->getContentFixture(2, ['eng-GB']);
        $action = $this->getMockedAction(['insertField']);

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->with($this->equalTo($contentId))
            ->will($this->returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->with($this->equalTo([['id' => $contentId, 'version' => 1], ['id' => $contentId, 'version' => 2]]))
            ->will($this->returnValue([]));

        $this->getContentGatewayMock()
            ->expects($this->at(2))
            ->method('load')
            ->with($contentId, 1)
            ->will($this->returnValue([]));

        $this->getContentMapperMock()
            ->expects($this->at(0))
            ->method('extractContentFromRows')
            ->with([], [])
            ->will($this->returnValue([$content1]));

        $this->getContentGatewayMock()
            ->expects($this->at(3))
            ->method('load')
            ->with($contentId, 2)
            ->will($this->returnValue([]));

        $this->getContentMapperMock()
            ->expects($this->at(1))
            ->method('extractContentFromRows')
            ->with([], [])
            ->will($this->returnValue([$content2]));

        $action
            ->expects($this->at(0))
            ->method('insertField')
            ->with($content1, $this->getFieldReference(null, 1, 'eng-GB'))
            ->will($this->returnValue('fieldId1'));

        $action
            ->expects($this->at(1))
            ->method('insertField')
            ->with($content2, $this->getFieldReference('fieldId1', 2, 'eng-GB'))
            ->will($this->returnValue('fieldId1'));

        $action->apply($contentId);
    }

    public function testApplyMultipleVersionsMultipleTranslations()
    {
        $contentId = 42;
        $versionNumbers = [1, 2];
        $content1 = $this->getContentFixture(1, ['eng-GB', 'ger-DE']);
        $content2 = $this->getContentFixture(2, ['eng-GB', 'ger-DE']);
        $action = $this->getMockedAction(['insertField']);

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('listVersionNumbers')
            ->with($this->equalTo($contentId))
            ->will($this->returnValue($versionNumbers));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->with($this->equalTo([['id' => $contentId, 'version' => 1], ['id' => $contentId, 'version' => 2]]))
            ->will($this->returnValue([]));

        $this->getContentGatewayMock()
            ->expects($this->at(2))
            ->method('load')
            ->with($contentId, 1)
            ->will($this->returnValue([]));

        $this->getContentMapperMock()
            ->expects($this->at(0))
            ->method('extractContentFromRows')
            ->with([], [])
            ->will($this->returnValue([$content1]));

        $this->getContentGatewayMock()
            ->expects($this->at(3))
            ->method('load')
            ->with($contentId, 2)
            ->will($this->returnValue([]));

        $this->getContentMapperMock()
            ->expects($this->at(1))
            ->method('extractContentFromRows')
            ->with([], [])
            ->will($this->returnValue([$content2]));

        $action
            ->expects($this->at(0))
            ->method('insertField')
            ->with($content1, $this->getFieldReference(null, 1, 'eng-GB'))
            ->will($this->returnValue('fieldId1'));

        $action
            ->expects($this->at(1))
            ->method('insertField')
            ->with($content1, $this->getFieldReference(null, 1, 'ger-DE'))
            ->will($this->returnValue('fieldId2'));

        $action
            ->expects($this->at(2))
            ->method('insertField')
            ->with($content2, $this->getFieldReference('fieldId1', 2, 'eng-GB'))
            ->will($this->returnValue('fieldId1'));

        $action
            ->expects($this->at(3))
            ->method('insertField')
            ->with($content2, $this->getFieldReference('fieldId2', 2, 'ger-DE'))
            ->will($this->returnValue('fieldId2'));

        $action->apply($contentId);
    }

    public function testInsertNewField()
    {
        $versionInfo = new Content\VersionInfo();
        $content = new Content();
        $content->versionInfo = $versionInfo;

        $value = new Content\FieldValue();

        $field = new Field();
        $field->id = null;
        $field->value = $value;

        $this->getFieldValueConverterMock()
            ->expects($this->once())
            ->method('toStorageValue')
            ->with(
                $value,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('insertNewField')
            ->with(
                $content,
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            )
            ->will($this->returnValue(23));

        $this->getContentStorageHandlerMock()
            ->expects($this->once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will($this->returnValue(false));

        $this->getContentGatewayMock()->expects($this->never())->method('updateField');

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        $this->assertEquals(23, $fieldId);
        $this->assertEquals(23, $field->id);
    }

    public function testInsertNewFieldUpdating()
    {
        $versionInfo = new Content\VersionInfo();
        $content = new Content();
        $content->versionInfo = $versionInfo;

        $value = new Content\FieldValue();

        $field = new Field();
        $field->id = null;
        $field->value = $value;

        $this->getFieldValueConverterMock()
            ->expects($this->exactly(2))
            ->method('toStorageValue')
            ->with(
                $value,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('insertNewField')
            ->with(
                $content,
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            )
            ->will($this->returnValue(23));

        $this->getContentStorageHandlerMock()
            ->expects($this->once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will($this->returnValue(true));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('updateField')
            ->with(
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        $this->assertEquals(23, $fieldId);
        $this->assertEquals(23, $field->id);
    }

    public function testInsertExistingField()
    {
        $versionInfo = new Content\VersionInfo();
        $content = new Content();
        $content->versionInfo = $versionInfo;

        $value = new Content\FieldValue();

        $field = new Field();
        $field->id = 32;
        $field->value = $value;

        $this->getFieldValueConverterMock()
            ->expects($this->once())
            ->method('toStorageValue')
            ->with(
                $value,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('insertExistingField')
            ->with(
                $content,
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentStorageHandlerMock()
            ->expects($this->once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will($this->returnValue(false));

        $this->getContentGatewayMock()->expects($this->never())->method('updateField');

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        $this->assertEquals(32, $fieldId);
        $this->assertEquals(32, $field->id);
    }

    public function testInsertExistingFieldUpdating()
    {
        $versionInfo = new Content\VersionInfo();
        $content = new Content();
        $content->versionInfo = $versionInfo;

        $value = new Content\FieldValue();

        $field = new Field();
        $field->id = 32;
        $field->value = $value;

        $this->getFieldValueConverterMock()
            ->expects($this->exactly(2))
            ->method('toStorageValue')
            ->with(
                $value,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('insertExistingField')
            ->with(
                $content,
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $this->getContentStorageHandlerMock()
            ->expects($this->once())
            ->method('storeFieldData')
            ->with($versionInfo, $field)
            ->will($this->returnValue(true));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('updateField')
            ->with(
                $field,
                $this->isInstanceOf(StorageFieldValue::class)
            );

        $action = $this->getMockedAction();

        $refAction = new ReflectionObject($action);
        $refMethod = $refAction->getMethod('insertField');
        $refMethod->setAccessible(true);
        $fieldId = $refMethod->invoke($action, $content, $field);

        $this->assertEquals(32, $fieldId);
        $this->assertEquals(32, $field->id);
    }

    /**
     * Returns a Content fixture.
     *
     * @param int $versionNo
     * @param array $languageCodes
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content
     */
    protected function getContentFixture($versionNo, array $languageCodes)
    {
        $contentInfo = new Content\ContentInfo();
        $contentInfo->id = 'contentId';
        $versionInfo = new Content\VersionInfo();
        $versionInfo->contentInfo = $contentInfo;

        $content = new Content();
        $content->versionInfo = $versionInfo;
        $content->versionInfo->versionNo = $versionNo;

        $fields = [];
        foreach ($languageCodes as $languageCode) {
            $fields[] = new Field(['languageCode' => $languageCode]);
        }

        $content->fields = $fields;

        return $content;
    }

    /**
     * Returns a Content Gateway mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Gateway
     */
    protected function getContentGatewayMock()
    {
        if (!isset($this->contentGatewayMock)) {
            $this->contentGatewayMock = $this->createMock(Gateway::class);
        }

        return $this->contentGatewayMock;
    }

    /**
     * Returns a FieldValue converter mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter
     */
    protected function getFieldValueConverterMock()
    {
        if (!isset($this->fieldValueConverterMock)) {
            $this->fieldValueConverterMock = $this->createMock(Converter::class);
        }

        return $this->fieldValueConverterMock;
    }

    /**
     * Returns a Content StorageHandler mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\StorageHandler
     */
    protected function getContentStorageHandlerMock()
    {
        if (!isset($this->contentStorageHandlerMock)) {
            $this->contentStorageHandlerMock = $this->createMock(StorageHandler::class);
        }

        return $this->contentStorageHandlerMock;
    }

    /**
     * Returns a Content mapper mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Mapper
     */
    protected function getContentMapperMock()
    {
        if (!isset($this->contentMapperMock)) {
            $this->contentMapperMock = $this->createMock(ContentMapper::class);
        }

        return $this->contentMapperMock;
    }

    /**
     * Returns a FieldDefinition fixture.
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition
     */
    protected function getFieldDefinitionFixture()
    {
        $fieldDef = new Content\Type\FieldDefinition();
        $fieldDef->id = 42;
        $fieldDef->isTranslatable = true;
        $fieldDef->fieldType = 'ezstring';
        $fieldDef->defaultValue = new Content\FieldValue();

        return $fieldDef;
    }

    /**
     * Returns a reference Field.
     *
     * @param int $id
     * @param int $versionNo
     * @param string $languageCode
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\Field
     */
    public function getFieldReference($id, $versionNo, $languageCode)
    {
        $field = new Field();

        $field->id = $id;
        $field->fieldDefinitionId = 42;
        $field->type = 'ezstring';
        $field->value = new Content\FieldValue();
        $field->versionNo = $versionNo;
        $field->languageCode = $languageCode;

        return $field;
    }

    /**
     * @param $methods
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Type\ContentUpdater\Action\AddField
     */
    protected function getMockedAction($methods = [])
    {
        return $this
            ->getMockBuilder(AddField::class)
            ->setMethods((array)$methods)
            ->setConstructorArgs(
                [
                    $this->getContentGatewayMock(),
                    $this->getFieldDefinitionFixture(),
                    $this->getFieldValueConverterMock(),
                    $this->getContentStorageHandlerMock(),
                    $this->getContentMapperMock(),
                ]
            )
            ->getMock();
    }
}

class_alias(AddFieldTest::class, 'eZ\Publish\Core\Persistence\Legacy\Tests\Content\Type\ContentUpdater\Action\AddFieldTest');
