<?php

declare(strict_types=1);

namespace RectorLaravel\Rector\PropertyFetch;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Scalar;
use Rector\Core\NodeAnalyzer\ArgsAnalyzer;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\Util\MultiInstanceofChecker;
use Rector\Core\ValueObject\PhpVersion;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \RectorLaravel\Tests\Rector\PropertyFetch\DataGetToNullsafeOperatorRector\DataGetToNullsafeOperatorRectorTest
 */
final class DataGetToNullsafeOperatorRector extends AbstractRector implements MinPhpVersionInterface
{
    /**
     * @var string
     */
    private const DATA_GET = 'data_get';
    /**
     * @var array<class-string<Expr>>
     */
    private const SKIP_VALUE_TYPES = [ConstFetch::class, Scalar::class, Array_::class, ClassConstFetch::class];


    public function __construct(
        private readonly MultiInstanceofChecker $multiInstanceofChecker,
        private readonly ArgsAnalyzer           $argsAnalyzer
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert simple calls to data_get helper to use the nullsafe operator',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
data_get($user,'role.name');
data_get($user,'role.name', '');
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$user->role?->name;
$user->role?->name ?? '';
CODE_SAMPLE
                    ,
                    [
                    ]
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param  FuncCall  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isName($node->name, self::DATA_GET)) {
            return null;
        }

        if (! isset($node->args[0])) {
            return null;
        }

        if (! $this->argsAnalyzer->isArgInstanceInArgsPosition($node->args, 0)) {
            return null;
        }

        /** @var Arg $firstArg */
        $firstArg = $node->args[0];

        // skip if the first arg cannot be used as variable directly
        if ($this->multiInstanceofChecker->isInstanceOf($firstArg->value, self::SKIP_VALUE_TYPES)) {
            return null;
        }
        $properties = explode(".", $node->args[1]->value->value);
        $result = new NullsafePropertyFetch($firstArg->value, $properties[0]);
        unset($properties[0]);
        foreach ($properties as $item) {
            $result = new NullsafePropertyFetch($result, $item);
        }
        if (count($node->args) === 2) {
            return $result;
        }

        return new Coalesce($result, $node->args[2]->value);

    }

    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_80;
    }

}
