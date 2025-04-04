<?php

declare(strict_types=1);

/* @license MIT */

namespace App\PHPStan\Rules;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;

/**
 * @implements Rule<Class_>
 */
class DoctrineEntityFieldTypeRule implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        if (!$this->isDoctrineEntity($node)) {
            return [];
        }

        foreach ($node->getProperties() as $property) {
            foreach ($property->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attr) {
                    $attrName = $attr->name->toString();

                    if ($attrName === Column::class) {
                        foreach ($attr->args as $arg) {
                            if ($arg->name && mb_strtolower($arg->name->toString()) === 'type') {
                                $value = $arg->value->value ?? null;
                                if (is_string($value) && mb_strtolower($value) === 'string') {
                                    $errors[] = RuleErrorBuilder::message(
                                        sprintf(
                                            'Field `%s` in class `%s` should use "text" type instead of "string".',
                                            $this->getPropertyName($property),
                                            $node->name,
                                        ),
                                    )->line($property->getLine())->build();
                                }
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    private function isDoctrineEntity(Class_ $class): bool
    {
        foreach ($class->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() === Entity::class) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getPropertyName(Property $property): string
    {
        // Zwróć nazwę właściwości, zakładając, że jest tylko jedna
        return $property->props[0]->name->toString();
    }
}
