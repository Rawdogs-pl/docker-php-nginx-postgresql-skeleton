<?php

declare(strict_types=1);

/* @license MIT */

namespace App\PHPStan\Rules;

use Doctrine\ORM\Mapping\Entity;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * @implements Rule<Class_>
 */
class DoctrineEntitySetterReturnTypeRule implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        // Sprawdź, czy klasa jest encją Doctrine z atrybutami PHP
        if (!$this->isDoctrineEntity($node)) {
            return [];
        }

        // Przeglądaj wszystkie metody klasy
        foreach ($node->getMethods() as $method) {
            if ($this->isSetterMethod($method)) {
                $returnType = $method->returnType;

                if ($returnType === null || $returnType->toString() !== 'static') {
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Setter method %s::%s() in Doctrine entity should have a return type of "static".',
                        $node->name,
                        $method->name->toString(),
                    ))->line($method->getLine())->build();
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

    private function isSetterMethod(ClassMethod $method): bool
    {
        return strpos($method->name->toString(), 'set') === 0;
    }
}
