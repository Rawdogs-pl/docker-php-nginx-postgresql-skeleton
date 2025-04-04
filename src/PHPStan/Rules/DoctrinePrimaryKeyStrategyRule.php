<?php

declare(strict_types=1);

/* @license: MIT */

namespace App\PHPStan\Rules;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 */
class DoctrinePrimaryKeyStrategyRule implements Rule
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

        /** @var Property $property */
        foreach ($node->getProperties() as $property) {
            $isPrimaryKey = false;
            $hasStrategy = false;
            $strategyNotEqualsIdentity = false;

            foreach ($property->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attr) {
                    $attrName = $attr->name->toString();

                    if ($attrName === Id::class) {
                        $isPrimaryKey = true;
                    }

                    if ($attrName === GeneratedValue::class) {
                        foreach ($attr->args as $arg) {
                            if ($arg->name && mb_strtolower($arg->name->toString()) === 'strategy') {
                                $hasStrategy = true;

                                $value = $arg->value->value ?? null;
                                if ($value !== 'IDENTITY') {
                                    $strategyNotEqualsIdentity = true;
                                }
                            }
                        }
                    }
                }
            }

            if ($isPrimaryKey && !$hasStrategy) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Primary key $%s in class %s does not have a generation strategy defined.',
                        $property->props[0]->name,
                        $node->name,
                    ),
                )->line($property->getLine())->build();
            }
            if ($strategyNotEqualsIdentity) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Primary key $%s in class %s has a generation strategy that is not "IDENTITY".',
                        $property->props[0]->name,
                        $node->name,
                    ),
                )->line($property->getLine())->build();
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
