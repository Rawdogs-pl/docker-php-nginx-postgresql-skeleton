<?php

declare(strict_types=1);

/* @license: MIT */

namespace App\PHPStan\Rules;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 */
class DoctrineNullableRule implements Rule
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
            $isPrimaryKey = false;
            $hasNullable = false;
            $isColumn = false;

            foreach ($property->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attr) {
                    $attrName = $attr->name->toString();

                    if ($attrName === Id::class) {
                        $isPrimaryKey = true;
                    }

                    if ($attrName === Column::class || $attrName === JoinColumn::class) {
                        $isColumn = true;
                        foreach ($attr->args as $arg) {
                            if ($arg->name->toString() === 'nullable') {
                                $hasNullable = true;
                            }
                        }
                    }
                }
            }
            if ($isPrimaryKey && $hasNullable) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Primary key $%s in class %s should not have "nullable" explicitly defined.',
                    $property->props[0]->name,
                    $node->name,
                ))->line($property->getLine())->build();
            }

            if (!$isPrimaryKey && !$hasNullable && $isColumn) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Property $%s in class %s does not have "nullable" explicitly defined.',
                    $property->props[0]->name,
                    $node->name,
                ))->line($property->getLine())->build();
            }
        }

        return $errors;
    }

    private function isDoctrineEntity(Class_ $class): bool
    {
        // Sprawdź, czy klasa ma atrybut Doctrine\ORM\Mapping\Entity
        foreach ($class->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() === Entity::class) {
                    return true;
                }
            }
        }

        return false;
    }
}
