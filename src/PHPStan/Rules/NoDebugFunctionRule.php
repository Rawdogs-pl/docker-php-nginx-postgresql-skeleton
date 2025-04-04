<?php

declare(strict_types=1);

/* @license: MIT */

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class NoDebugFunctionRule implements Rule
{
    /** @var string[] */
    private $forbiddenFunctions;

    /**
     * @param string[] $forbiddenFunctions
     */
    public function __construct(array $forbiddenFunctions)
    {
        $this->forbiddenFunctions = $forbiddenFunctions;
    }

    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Name) {
            return [];
        }

        $functionName = (string) $node->name;
        if (in_array($functionName, $this->forbiddenFunctions, true)) {
            return [
                RuleErrorBuilder::message(sprintf('Calling %s() function is forbidden.', $functionName))->build(),
            ];
        }

        return [];
    }
}
