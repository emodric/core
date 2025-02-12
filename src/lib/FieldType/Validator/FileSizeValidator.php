<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\FieldType\Validator;

use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Validator for checking max. size of binary files.
 *
 * @property int $maxFileSize The maximum allowed size of file, in bytes.
 */
class FileSizeValidator extends Validator
{
    protected $constraints = [
        'maxFileSize' => false,
    ];

    protected $constraintsSchema = [
        'maxFileSize' => [
            'type' => 'int',
            'default' => false,
        ],
    ];

    public function validateConstraints($constraints)
    {
        $validationErrors = [];

        foreach ($constraints as $name => $value) {
            switch ($name) {
                case 'maxFileSize':
                    if ($value !== false && !is_int($value)) {
                        $validationErrors[] = new ValidationError(
                            "Validator parameter '%parameter%' value must be of integer type",
                            null,
                            [
                                '%parameter%' => $name,
                            ]
                        );
                    }
                    break;
                default:
                    $validationErrors[] = new ValidationError(
                        "Validator parameter '%parameter%' is unknown",
                        null,
                        [
                            '%parameter%' => $name,
                        ]
                    );
            }
        }

        return $validationErrors;
    }

    /**
     * Checks if $value->file has the appropriate size.
     *
     * @param \Ibexa\Core\FieldType\BinaryFile\Value $value
     *
     * @return bool
     */
    public function validate(BaseValue $value)
    {
        $isValid = true;

        if ($this->constraints['maxFileSize'] !== false && $value->file->size > $this->constraints['maxFileSize']) {
            $this->errors[] = new ValidationError(
                'The file size cannot exceed %size% byte.',
                'The file size cannot exceed %size% bytes.',
                [
                    '%size%' => $this->constraints['maxFileSize'],
                ]
            );
            $isValid = false;
        }

        return $isValid;
    }
}

class_alias(FileSizeValidator::class, 'eZ\Publish\Core\FieldType\Validator\FileSizeValidator');
