<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;

use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\FieldType\FieldSettings;
use Ibexa\Core\FieldType\User\Type as UserType;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;

class UserConverter implements Converter
{
    private const PASSWORD_VALIDATOR_IDENTIFIER = 'PasswordValueValidator';

    private const REQUIRE_AT_LEAST_ONE_UPPER_CASE_CHAR = 1;
    private const REQUIRE_AT_LEAST_ONE_LOWER_CASE_CHAR = 2;
    private const REQUIRE_AT_LEAST_ONE_NUMERIC_CHAR = 4;
    private const REQUIRE_AT_LEAST_ONE_NON_ALPHANUMERIC_CHAR = 8;
    private const REQUIRE_NEW_PASSWORD = 16;
    private const REQUIRE_UNIQUE_EMAIL = 32;

    /**
     * {@inheritdoc}
     */
    public function toStorageValue(FieldValue $value, StorageFieldValue $storageFieldValue): void
    {
        // There is no contained data. All data is external. So we just do nothing here.
    }

    /**
     * {@inheritdoc}
     */
    public function toFieldValue(StorageFieldValue $value, FieldValue $fieldValue): void
    {
        // There is no contained data. All data is external. So we just do nothing here.
    }

    /**
     * {@inheritdoc}
     */
    public function toStorageFieldDefinition(FieldDefinition $fieldDef, StorageFieldDefinition $storageDef): void
    {
        $validatorParameters = [];
        if (isset($fieldDef->fieldTypeConstraints->validators[self::PASSWORD_VALIDATOR_IDENTIFIER])) {
            $validatorParameters = $fieldDef->fieldTypeConstraints->validators[self::PASSWORD_VALIDATOR_IDENTIFIER];
        }

        $rules = [
            'requireAtLeastOneUpperCaseCharacter' => self::REQUIRE_AT_LEAST_ONE_UPPER_CASE_CHAR,
            'requireAtLeastOneLowerCaseCharacter' => self::REQUIRE_AT_LEAST_ONE_LOWER_CASE_CHAR,
            'requireAtLeastOneNumericCharacter' => self::REQUIRE_AT_LEAST_ONE_NUMERIC_CHAR,
            'requireAtLeastOneNonAlphanumericCharacter' => self::REQUIRE_AT_LEAST_ONE_NON_ALPHANUMERIC_CHAR,
            'requireNewPassword' => self::REQUIRE_NEW_PASSWORD,
        ];

        $fieldSettings = $fieldDef->fieldTypeConstraints->fieldSettings;

        $storageDef->dataInt1 = 0;
        foreach ($rules as $rule => $flag) {
            if (isset($validatorParameters[$rule]) && $validatorParameters[$rule]) {
                $storageDef->dataInt1 |= $flag;
            }
        }

        $storageDef->dataInt1 |= $fieldSettings[UserType::REQUIRE_UNIQUE_EMAIL]
            ? self::REQUIRE_UNIQUE_EMAIL
            : 0;

        $storageDef->dataInt2 = null;
        if (isset($validatorParameters['minLength'])) {
            $storageDef->dataInt2 = $validatorParameters['minLength'];
        }

        $storageDef->dataInt3 = $fieldSettings[UserType::PASSWORD_TTL_SETTING] ?? null;
        $storageDef->dataInt4 = $fieldSettings[UserType::PASSWORD_TTL_WARNING_SETTING] ?? null;

        $storageDef->dataText2 = $fieldSettings[UserType::USERNAME_PATTERN];
    }

    /**
     * {@inheritdoc}
     */
    public function toFieldDefinition(StorageFieldDefinition $storageDef, FieldDefinition $fieldDef): void
    {
        $validatorParameters = [];

        $rules = [
            self::REQUIRE_AT_LEAST_ONE_UPPER_CASE_CHAR => 'requireAtLeastOneUpperCaseCharacter',
            self::REQUIRE_AT_LEAST_ONE_LOWER_CASE_CHAR => 'requireAtLeastOneLowerCaseCharacter',
            self::REQUIRE_AT_LEAST_ONE_NUMERIC_CHAR => 'requireAtLeastOneNumericCharacter',
            self::REQUIRE_AT_LEAST_ONE_NON_ALPHANUMERIC_CHAR => 'requireAtLeastOneNonAlphanumericCharacter',
            self::REQUIRE_NEW_PASSWORD => 'requireNewPassword',
        ];

        foreach ($rules as $flag => $rule) {
            $validatorParameters[$rule] = (bool) ($storageDef->dataInt1 & $flag);
        }

        $validatorParameters['minLength'] = $storageDef->dataInt2;

        $fieldDef->fieldTypeConstraints->validators[self::PASSWORD_VALIDATOR_IDENTIFIER] = $validatorParameters;
        $fieldDef->fieldTypeConstraints->fieldSettings = new FieldSettings([
            UserType::PASSWORD_TTL_SETTING => $storageDef->dataInt3,
            UserType::PASSWORD_TTL_WARNING_SETTING => $storageDef->dataInt4,
            UserType::REQUIRE_UNIQUE_EMAIL => (bool)($storageDef->dataInt1 & self::REQUIRE_UNIQUE_EMAIL),
            UserType::USERNAME_PATTERN => $storageDef->dataText2,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexColumn(): bool
    {
        return false;
    }
}

class_alias(UserConverter::class, 'eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter\UserConverter');
