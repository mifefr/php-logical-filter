<?php
namespace JClaveau\LogicalFilter;

use JClaveau\VisibilityViolator\VisibilityViolator;

use JClaveau\LogicalFilter\Rule\AbstractOperationRule;
use JClaveau\LogicalFilter\Rule\OrRule;
use JClaveau\LogicalFilter\Rule\AndRule;
use JClaveau\LogicalFilter\Rule\NotRule;
use JClaveau\LogicalFilter\Rule\InRule;
use JClaveau\LogicalFilter\Rule\EqualRule;
use JClaveau\LogicalFilter\Rule\AboveRule;
use JClaveau\LogicalFilter\Rule\BelowRule;

require  __DIR__ . "/LogicalFilterTest_rules_manipulation_trait.php";
require  __DIR__ . "/LogicalFilterTest_rules_simplification_trait.php";

class LogicalFilterTest extends \AbstractTest
{
    use LogicalFilterTest_rules_manipulation_trait;
    use LogicalFilterTest_rules_simplification_trait;

    /**
     */
    public function test_construct()
    {
        $filter = new LogicalFilter(['field', 'above', 3]);

        $this->assertEquals(
            new AboveRule('field', 3),
            $filter->getRules()
        );
    }

    /**
     */
    public function test_and_simple()
    {
        $filter = new LogicalFilter();

        $filter->and_('field', 'in', ['a', 'b', 'c']);
        // $filter->addRule('field', 'not_in', ['a', 'b', 'c']);
        $filter->and_('field', 'above', 3);
        $filter->and_('field', 'below', 5);

        $rules = VisibilityViolator::getHiddenProperty(
            $filter,
            'rules'
        );

        $this->assertEquals(
            (new AndRule([
                new InRule('field', ['a', 'b', 'c']),
                // new NotInRule(['a', 'b', 'c']),
                new AboveRule('field', 3),
                new BelowRule('field', 5)
            ]))->toArray(),
            $rules->toArray()
        );
    }

    /**
     */
    public function test_getRules()
    {
        $filter = new LogicalFilter();
        $filter->and_('field', 'in', ['a', 'b', 'c']);

        $this->assertEquals(
            new InRule('field', ['a', 'b', 'c']),
            $filter->getRules()
        );
    }

    /**
     */
    public function test_addOrRule()
    {
        $filter = new LogicalFilter();

        $filter->and_([
            ['field', 'in', ['a', 'b', 'c']],
            'or',
            ['field', 'equal', 'e']
        ]);

        $this->assertEquals(
            (new OrRule([
                new InRule('field', ['a', 'b', 'c']),
                new EqualRule('field', 'e')
            ]))->toArray(),
            $filter->getRules()->toArray()
        );
    }

    /**
     */
    public function test_addRules_with_nested_operations()
    {
        $filter = new LogicalFilter();
        $filter->and_([
            ['field', 'in', ['a', 'b', 'c']],
            'or',
            [
                ['field', 'in', ['d', 'e']],
                'and',
                [
                    ['field_2', 'above', 3],
                    'or',
                    ['field_3', 'below', -2],
                ],
            ],
        ]);

        $this->assertEquals(
            (new OrRule([
                new InRule('field', ['a', 'b', 'c']),
                new AndRule([
                    new InRule('field', ['d', 'e']),
                    new OrRule([
                        new AboveRule('field_2', 3),
                        new BelowRule('field_3', -2),
                    ]),
                ]),
            ]))->toArray(),
            $filter->toArray()
        );
    }

    /**
     */
    public function test_addRules_with_different_operators()
    {
        $filter = new LogicalFilter();

        // exception if different operators in the same operation
        try {
            $filter->and_([
                ['field', 'in', ['a', 'b', 'c']],
                'or',
                [
                    ['field', 'in', ['d', 'e']],
                    'and',
                    [
                        ['field_2', 'above', 3],
                        'or',
                        ['field_3', 'below', -2],
                        'and',
                        ['field_3', 'equal', 0],
                    ],
                ],
            ]);

            $this->assertTrue(
                false,
                'No exception thrown for different operators in one operation'
            );
        }
        catch (\InvalidArgumentException $e) {

            $this->assertTrue(
                (bool) preg_match(
                    "/^Mixing different operations in the same rule level not implemented:/",
                    $e->getMessage()
                )
            );
            return;
        }
    }

    /**
     */
    public function test_addRules_without_operator()
    {
        $filter = new LogicalFilter();

        // exception if no operator in an operation
        try {
            $filter->and_([
                ['field_2', 'above', 3],
                ['field_3', 'below', -2],
                ['field_3', 'equal', 0],
            ]);

            $this->assertTrue(
                false,
                'No exception thrown while operator is missing in an operation'
            );
        }
        catch (\InvalidArgumentException $e) {

            $this->assertTrue(
                (bool) preg_match(
                    "/^Please provide an operator for the operation: /",
                    $e->getMessage()
                )
            );
            return;
        }
    }

    /**
     */
    public function test_addRules_requiring_strict_check_of_operators()
    {
        $this->assertEquals(
            ['not', ['depth', '=', 0]],
            (new LogicalFilter(['not', ['depth', '=', 0]]))
            // ->dump(true)
            ->toArray()
        );
    }

    /**
     */
    public function test_addRules_with_negation()
    {
        $filter = new LogicalFilter();

        $filter->and_([
            'not',
            ['field_2', 'above', 3],
        ]);

        $this->assertEquals(
            (new NotRule(
                new AboveRule('field_2', 3)
            ))->toArray(),
            $filter->getRules()->toArray()
        );

        // not with too much operands
        try {
            $filter->and_([
                'not',
                ['field_2', 'above', 3],
                ['field_2', 'equal', 5],
            ]);

            $this->assertTrue(
                false,
                'No exception thrown if two operands for a negation'
            );
        }
        catch (\InvalidArgumentException $e) {

            $this->assertTrue(
                (bool) preg_match(
                    "/^Negations can have only one operand: /",
                    $e->getMessage()
                )
            );
            return;
        }
    }

    /**
     * @see https://secure.php.net/manual/en/jsonserializable.jsonserialize.php
     */
    public function test_jsonSerialize()
    {
        $this->assertEquals(
            '["or",["and",["field_5",">","a"],["field_5","<","a"]],["field_6","=","b"]]',
            json_encode(
                new LogicalFilter([
                    'or',
                    [
                        'and',
                        ['field_5', 'above', 'a'],
                        ['field_5', 'below', 'a'],
                    ],
                    ['field_6', 'equal', 'b'],
                ])
            )
        );
    }

    /**
     */
    public function test_copy()
    {
        $filter = new LogicalFilter([
            'or',
            [
                'and',
                ['field_5', 'above', 'a'],
                ['field_5', 'below', 'a'],
            ],
            ['field_6', 'equal', 'b'],
        ]);

        $filter2 = $filter->copy();

        $this->assertEquals($filter, $filter2);

        $this->assertNotEquals(
            spl_object_hash($filter->getRules(false)),
            spl_object_hash($filter2->getRules(false))
        );

        // copy filter with no rule
        $filter = new LogicalFilter();
        $filter->copy();

        $this->assertNull( $filter->getRules() );
    }

    /**
     */
    public function test_addRules_on_noSolution_filter()
    {
        // and root
        $filter = (new LogicalFilter([
            'and'
        ]))
        ;

        try {
            $filter->and_('a', '<', 'b');
            $this->assertFalse(false, "Adding rule to an invalid filter not forbidden");
        }
        catch (\Exception $e) {
            $this->assertTrue(true);
            $e->getMessage() ==  "You are trying to add rules to a LogicalFilter which had "
                ."only contradictory rules that have been simplified.";
        }

        // or root
        $filter = (new LogicalFilter([
            'or',
        ]))
        ;

        try {
            $filter->and_('a', '<', 'b');
            $this->assertFalse(false, "Adding rule to an invalid filter not forbidden");
        }
        catch (\Exception $e) {
            $this->assertTrue(true);
            $e->getMessage() ==  "You are trying to add rules to a LogicalFilter which had "
                ."only contradictory rules that have been simplified.";
        }
    }

    /**
     */
    public function test_addRules_with_symbolic_operators()
    {
        $filter = new LogicalFilter([
            'and',
            ['field_5', '>', 'a'],
            ['field_5', '<', 'a'],
            [
                '!',
                ['field_5', '=', 'a'],
            ],
        ]);

        $this->assertEquals(
            [
                'and',
                ['field_5', '>', 'a'],
                ['field_5', '<', 'a'],
                [
                    'not',
                    ['field_5', '=', 'a'],
                ],
            ],
            $filter->toArray()
        );
    }

    /**
     */
    public function test_addRules_from_toArray()
    {
        $filter = new LogicalFilter([
            'and',
            ['field_5', '>', 'a'],
            ['field_5', '<', 'a'],
            [
                '!',
                ['field_5', '=', 'a'],
            ],
        ]);

        $this->assertEquals(
            $filter->toArray(),
            (new LogicalFilter( $filter->toArray() ))->toArray()
        );
    }

    /**
     */
    public function test_renameFields()
    {
        $filter = new LogicalFilter(
            ['and',
                ['or',
                    ['field_5', '>', 'a'],
                    ['field_3', '<', 'a'],
                ],
                ['not',
                    ['and',
                        ['field_5', '>', 'a'],
                        ['field_4', '=', 'a'],
                    ],
                ],
                ['field_5', 'in', ['a', 'b', 'c']],
            ]
        );

        $this->assertEquals(
            ['and',
                ['or',
                    ['field_five', '>', 'a'],
                    ['field_three', '<', 'a'],
                ],
                ['not',
                    ['and',
                        ['field_five', '>', 'a'],
                        ['field_4', '=', 'a'],
                    ],
                ],
                ['field_five', 'in', ['a', 'b', 'c']],
            ],
            $filter
                ->copy()
                ->renameFields([
                    'field_5' => 'field_five',
                    'field_3' => 'field_three',
                ])
                // ->dump(true)
                ->toArray()
        );

        $this->assertEquals(
            ['and',
                ['or',
                    ['property_5', '>', 'a'],
                    ['property_3', '<', 'a'],
                ],
                ['not',
                    ['and',
                        ['property_5', '>', 'a'],
                        ['property_4', '=', 'a'],
                    ],
                ],
                ['property_5', 'in', ['a', 'b', 'c']],
            ],
            $filter
                ->copy()
                ->renameFields(function($field) {
                    return str_replace('field_', 'property_', $field);
                })
                // ->dump(true)
                ->toArray()
        );

        try {
            $filter->renameFields('sdfghjk');
            $this->assertTrue(false, "An exception should be throw here");
        }
        catch (\InvalidArgumentException $e) {
            // InvalidArgumentException: Minimum parameter must be a scalar
            $this->assertTrue(true, "Exception thrown: ".$e->getMessage());
        }
    }

    /**
     */
    public function test_add_InRule()
    {
        $filter = new LogicalFilter(
            ['field_1', 'in', ['a', 'b', 'c']]
        );

        // toArray must be iso to the provided descrition
        $this->assertEquals(
            ['field_1', 'in', ['a', 'b', 'c']],
            $filter->toArray()
        );

        $filter->getRules(false)->addPossibilities(['d', 'e']);

        $this->assertEquals(
            ['a', 'b', 'c', 'd', 'e'],
            $filter->getRules()->getPossibilities()
        );

        $this->assertEquals(
            [
                'or',
                ['field_1', '=', 'a'],
                ['field_1', '=', 'b'],
                ['field_1', '=', 'c'],
                ['field_1', '=', 'd'],
                ['field_1', '=', 'e'],
            ],
            $filter
                // ->dump(!true)
                ->simplify([
                    // 'stop_after' =>
                    // AbstractOperationRule::remove_negations,
                    // AbstractOperationRule::rootify_disjunctions,
                    // AbstractOperationRule::unify_atomic_operands,
                    // AbstractOperationRule::remove_invalid_branches,
                ])
                // ->dump(true)
                ->toArray()
        );
    }

    /**
     */
    public function test_add_NotEqualRule()
    {
        $filter = new LogicalFilter(
            ['field_1', '!=', 'a']
        );

        // toArray must be iso to the provided descrition
        $this->assertEquals(
            ['field_1', '!=', 'a'],
            $filter->toArray()
        );

        $this->assertEquals(
            [
                'or',
                ['field_1', '>', 'a'],
                ['field_1', '<', 'a'],
            ],
            $filter->simplify()->toArray()
        );
    }

    /**
     */
    public function test_add_AboveOrEqualRule()
    {
        $filter = new LogicalFilter(
            ['field_1', '>=', 2]
        );

        // toArray must be iso to the provided descrition
        $this->assertEquals(
            ['field_1', '>=', 2],
            $filter->toArray()
        );

        $this->assertEquals(
            [
                'or',
                ['field_1', '>', 2],
                ['field_1', '=', 2],
            ],
            $filter->simplify()->toArray()
        );
    }

    /**
     */
    public function test_add_BelowOrEqualRule()
    {
        $filter = new LogicalFilter(
            ['field_1', '<=', 2]
        );

        // toArray must be iso to the provided descrition
        $this->assertEquals(
            ['field_1', '<=', 2],
            $filter->toArray()
        );

        $this->assertEquals(
            [
                'or',
                ['field_1', '<', 2],
                ['field_1', '=', 2],
            ],
            $filter->simplify()->toArray()
        );
    }

    /**
     */
    public function test_add_NotInRule()
    {
        $filter = new LogicalFilter(
            ['field_1', '!in', [2, 3]]
        );

        // toArray must be iso to the provided descrition
        $this->assertEquals(
            ['field_1', '!in', [2, 3]],
            $filter->toArray()
        );

        $this->assertEquals(
            [
                'or',
                ['field_1', '>', 3],
                ['field_1', '<', 2],
                [
                    'and',
                    ['field_1', '>', 2],
                    ['field_1', '<', 3],
                ],
            ],
            $filter->simplify()
                // ->dump(true)
                ->toArray()
        );
    }

    /**
     */
    public function test_AboveRule_with_non_scalar()
    {
        $filter = (new LogicalFilter([
            'and',
            ['field_1', '>', null],
            ['field_2', '>', 'a'],
            ['field_5', '>', 3],
            ['field_5', '>', new \DateTime('2018-06-11')],
            ['field_5', '>', new \DateTimeImmutable('2018-06-11')],
        ]));

        try {
            $filter = (new LogicalFilter(
                ['field_1', '>', [12, 45]]
            ));
            $this->assertTrue(false, "An exception should be throw here");
        }
        catch (\InvalidArgumentException $e) {
            // InvalidArgumentException: Minimum parameter must be a scalar
            $this->assertTrue(true, "Exception thrown: ".$e->getMessage());
        }
    }

    /**
     */
    public function test_BelowRule_with_non_scalar()
    {
        $filter = (new LogicalFilter([
            'and',
            ['field_1', '<', null],
            ['field_2', '<', 'a'],
            ['field_5', '<', 3],
            ['field_5', '<', new \DateTime('2018-06-11')],
            ['field_5', '<', new \DateTimeImmutable('2018-06-11')],
        ]));

        try {
            $filter = (new LogicalFilter(
                ['field_1', '<', ['lalala', 2]]
            ));
            $this->assertTrue(false, "An exception should be throw here");
        }
        catch (\InvalidArgumentException $e) {
            // InvalidArgumentException: Maximum parameter must be a scalar
            $this->assertTrue(true, "Exception thrown: ".$e->getMessage());
        }
    }

    /**
     */
    public function test_NotRule_of_long_and()
    {
        $filter = (new LogicalFilter(
            ['not',
                ['and',
                    ['field_1', '=', 2],
                    ['field_2', '<', 3],
                    ['field_3', '>', 4],
                ],
            ]
        ))
        // ->dump()
        ;

        $this->assertEquals(
            ['or',
                ['and', // 1
                    ['not', ['field_1', '=', 2]],
                    ['field_2', '<', 3],
                    ['field_3', '>',  4],
                ],

                ['and', // 2
                    ['field_1', '=', 2],
                    ['not', ['field_2', '<', 3]],
                    ['field_3', '>',  4],
                ],

                ['and', // 3
                    ['not', ['field_1', '=', 2]],
                    ['not', ['field_2', '<', 3]],
                    ['field_3', '>',  4],
                ],

                ['and', // 4
                    ['field_1', '=', 2],
                    ['field_2', '<', 3],
                    ['not', ['field_3', '>',  4]],
                ],

                ['and', // 5
                    ['not', ['field_1', '=', 2]],
                    ['field_2', '<', 3],
                    ['not', ['field_3', '>',  4]],
                ],

                ['and', // 6
                    ['field_1', '=', 2],
                    ['not', ['field_2', '<', 3]],
                    ['not', ['field_3', '>',  4]],
                ],

                ['and', // 7
                    ['not', ['field_1', '=', 2]],
                    ['not', ['field_2', '<', 3]],
                    ['not', ['field_3', '>',  4]],
                ],
            ],
            $filter
                ->getRules()
                ->negateOperand()
                // ->dump(true)
                ->toArray()
        );
    }

    /**
     */
    public function test_NotRule_of_null()
    {
        $filter = (new LogicalFilter(
            ['field_1', '!=', null]
        ));

        $this->assertEquals(
            ['field_1', '!=', null],
            $filter->toArray()
        );

        $this->assertEquals(
            ['field_1', '!=', null],
            $filter
                ->simplify()
                // ->dump(!true)
                ->toArray()
        );

        $this->assertEquals(
            ['or',
                ['and',
                    ['field_1', '!=', null],
                ],
            ],
            $filter
                ->simplify(['force_logical_core' => true])
                // ->dump(!true)
                ->toArray()
        );

        $filter = (new LogicalFilter(
            ['not', ['field_1', '=', null]]
        ))
        // ->dump(true)
        ;

        $this->assertEquals(
            ['not', ['field_1', '=', null]],
            $filter
                ->toArray()
        );

        $this->assertEquals(
            ['field_1', '!=', null],
            $filter
                ->simplify()
                // ->dump()
                ->toArray()
        );
    }

    /**
     */
    public function test_add_BetweenRule()
    {
        $filter = new LogicalFilter(
            ['field_1', '><', [2, 3]]
        );

        // toArray must be iso to the provided descrition
        $this->assertEquals(
            ['field_1', '><', [2, 3]],
            $filter->toArray()
        );

        $this->assertEquals(
            [
                'and',
                ['field_1', '>', 2],
                ['field_1', '<', 3],
            ],
            $filter->simplify()
                // ->dump(true)
                ->toArray()
        );
    }

    /**
     */
    public function test_BelowRule_and_AboveRule_are_strictly_compared()
    {
        $this->assertFalse(
            (new LogicalFilter([
                'and',
                ['field_1', '=', 3],
                ['field_1', '<', 3],
            ]))
            ->hasSolution()
        );

        $this->assertFalse(
            (new LogicalFilter([
                'and',
                ['field_1', '=', 3],
                ['field_1', '>', 3],
            ]))
            ->hasSolution()
        );
    }

    /**
     */
    public function test_and_of_LogicalFilter()
    {
        $filter  = new LogicalFilter( ['field_1', '=', 3] );
        $filter2 = new LogicalFilter( ['field_2', '=', 12] );

        $this->assertEquals(
            [
                'and',
                ['field_1', '=', 3],
                ['field_2', '=', 12],
            ],
            $filter
                ->and_( $filter2 )
                // ->dump()
                ->toArray()
        );
    }

    /**
     */
    public function test_and_of_AbstractRules()
    {
        $filter = new LogicalFilter( ['field_1', '=', 3] );
        $rule1  = new EqualRule( 'field_2', 12 );
        $rule2  = new AboveRule( 'field_3', 'abc' );

        $this->assertEquals(
            [
                'and',
                ['field_1', '=', 3],
                ['field_2', '=', 12],
                ['field_3', '>', 'abc'],
            ],
            $filter
                ->and_( $rule1, $rule2 )
                // ->dump()
                ->toArray()
        );
    }

    /**
     */
    public function test_and_of_invalid_rules_description_throws_exception()
    {
        $filter = new LogicalFilter( ['field_1', '=', 3] );

        try {
            $filter->and_('a', '=', '3', 'lalalalala');
            $this->assertTrue(
                false,
                "An exception claiming that bad arguments are provided "
                ."should have been thrown here"
            );
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true, "InvalidArgumentException throw: ".$e->getMessage());
        }
    }

    /**
     */
    public function test_and_of_invalid_rules_description_containing_unhandled_operation()
    {
        try {
            $filter = new LogicalFilter( ['operator_of_unhandled_operation', ['filed_1', '=', 3]] );
            $this->assertTrue(
                false,
                "An exception claiming that an unhandled operation is described "
                ."into a rules description should have been thrown here"
            );
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true, "InvalidArgumentException throw: ".$e->getMessage());
        }
    }

    /**
     */
    public function test_addRule_with_bad_operation()
    {
        $filter = new LogicalFilter( ['field_1', '=', 3] );

        try {
            VisibilityViolator::callHiddenMethod(
                $filter, 'addRule', [new EqualRule('field', 2), 'bad_operator']
            );

            $this->assertTrue(
                false,
                "An exception claiming that an invaid operator is given "
                ."should have been thrown here"
            );
        }
        catch (\InvalidArgumentException $e) {
            $this->assertTrue(true, "InvalidArgumentException throw: ".$e->getMessage());
        }
    }

    /**
     */
    public function test_forceLogicalCore_with_AtomicRule_at_root()
    {
        $filter = new LogicalFilter( ['field_1', '=', 3] );

        $this->assertEquals(
            [
                'or',
                [
                    'and',
                    ['field_1', '=', 3],
                ],
            ],
            $filter
                ->simplify(['force_logical_core' => true])
                ->toArray()
        );
    }

    /**
     */
    public function test_forceLogicalCore_with_AndRule_at_root()
    {
        $filter = (new LogicalFilter( ['field_1', '=', 3] ))
            ->and_(['field_2', '=', 4])
            // ->dump()
            ;

        $this->assertEquals(
            [
                'or',
                [
                    'and',
                    ['field_1', '=', 3],
                    ['field_2', '=', 4],
                ],
            ],
            $filter
                ->simplify(['force_logical_core' => true])
                // ->dump()
                ->toArray()
        );
    }

    /**
     */
    public function test_forceLogicalCore_with_OrRule_at_root()
    {
        $filter = (new LogicalFilter( ['field_1', '=', 3] ))
            ->or_(['field_2', '=', 4])
            // ->dump()
            ;

        $this->assertEquals(
            [
                'or',
                [
                    'and',
                    ['field_1', '=', 3],
                ],
                [
                    'and',
                    ['field_2', '=', 4],
                ],
            ],
            $filter
                ->simplify(['force_logical_core' => true])
                // ->dump()
                ->toArray()
        );
    }

    /**
     */
    public function test_forceLogicalCore_with_NotRule_at_root()
    {
        $filter = (new LogicalFilter( ['not', ['field_1', '=', 3]] ))
            // ->dump()
            ;

        try {
            VisibilityViolator::callHiddenMethod(
                $filter->getRules(), 'forceLogicalCore'
            );

            $this->assertTrue(false, "forceLogicalCore() must throw an exception here");
        }
        catch (\LogicException $e) {
            $this->assertTrue(true, "Exception thrown: " . $e->getMessage());
        }
    }

    /**
     * @todo debug the runInseparateProcess of php to test the exit call.
     * @ runInSeparateProcess
     */
    public function test_dump()
    {
        ob_start();
        $filter = (new LogicalFilter( ['field_1', '=', 3] ))
            ->dump()
            ;
        $dump = ob_get_clean();

        // simple chained dump
        $this->assertEquals(
            str_replace('    ', '', "
                ". __FILE__ .":XX
                array (
                  0 => 'field_1',
                  1 => '=',
                  2 => 3,
                )

                "
            ),
            preg_replace('/:\d+/', ':XX', $dump)
        );

        // instance debuging enabled
        ob_start();
        $filter = (new LogicalFilter( ['field_1', '=', 3] ))
            ->dump(false, true)
            ;
        $dump = ob_get_clean();
        $this->assertEquals(
            str_replace('    ', '', "
                ". __FILE__ .":XX
                array (
                  0 => 'field_1',
                  1 => 'JClaveau\\\\LogicalFilter\\\\Rule\\\\EqualRule:XX',
                  2 => 3,
                )

                "
            ),
            preg_replace('/:\d+/', ':XX', $dump)
        );

        // exit once dumped
        // TODO this makes phpunit bug while echoing text before calling exit;
        // ob_start();
        // $filter = (new LogicalFilter( ['field_1', '=', 3] ))
            // ->dump(true)
            // ;
            // echo 'plop';
            // exit;
        // $dump = ob_get_clean();
        // $this->assertEquals(
            // str_replace('    ', '', "
                // /home/jean/dev/mediabong/apps/php-logical-filter/tests/public api/LogicalFilterTest.php:XX
                // array (
                  // 0 => 'field_1',
                  // 1 => '=',
                  // 2 => 3,
                // )

                // "
            // ),
            // preg_replace('/:\d+/', ':XX', $dump)
        // );

    }

    /**
     */
    public function test_add_RegexpRule()
    {
        $filter = new LogicalFilter(
            ['field_1', 'regexp', "/^prefix-[^-]+-suffix$/"]
        );

        // toArray must be iso to the provided descrition
        $this->assertEquals(
            ['field_1', 'regexp', "/^prefix-[^-]+-suffix$/"],
            $filter->toArray()
        );
    }

    /**
     */
    public function test_toString()
    {
        $filter = (new LogicalFilter(
            ['and',
                ['or',
                    ['field_1', '=', 3],
                    ['field_1', '!=', 100],
                    ['field_1', '>', 20],
                ],
                ['not',
                    ['field_2', '<', -5],
                ],
                ['field_1', 'regexp', "/^prefix-[^-]+-suffix$/"],
                ['field_3', 'in', [2, null]],
                ['field_4', '!in', [4, 12]],
                ['field_5', '<=', 3],
                ['field_5', '>=', 12],
                ['field_6', '><', [20, 30]],
                ['date', '>', new \DateTime("2018-07-19")],
            ]
        ))
        // ->dump(true)
        ;

        // This call is just meant to expose possible cache collision with toArray
        $filter->toArray();

        $this->assertEquals(
"['and',
    ['or',
        ['field_1', '=', 3],
        ['field_1', '!=', 100],
        ['field_1', '>', 20],
    ],
    ['not', ['field_2', '<', -5]],
    ['field_1', 'regexp', '/^prefix-[^-]+-suffix$/'],
    ['field_3', 'in', [2, NULL]],
    ['field_4', '!in', [4, 12]],
    ['field_5', '<=', 3],
    ['field_5', '>=', 12],
    ['field_6', '><', [20, 30]],
    ['date', '>', DateTime::__set_state(array(
       'date' => '2018-07-19 00:00:00.000000',
       'timezone_type' => 3,
       'timezone' => 'UTC',
    ))],
]",
            $filter->toString(['indent_unit' => "    "])
        );

        // toArray must be iso to the provided descrition
        $this->assertEquals(
"['and',['or',['field_1', '=', 3],['field_1', '!=', 100],['field_1', '>', 20],],['not', ['field_2', '<', -5]],['field_1', 'regexp', '/^prefix-[^-]+-suffix$/'],['field_3', 'in', [2, NULL]],['field_4', '!in', [4, 12]],['field_5', '<=', 3],['field_5', '>=', 12],['field_6', '><', [20, 30]],['date', '>', DateTime::__set_state(array(
   'date' => '2018-07-19 00:00:00.000000',
   'timezone_type' => 3,
   'timezone' => 'UTC',
))],]",
            $filter->toString()
        );

        // toArray must be iso to the provided descrition
        $this->assertEquals(
"['and',['or',['field_1', '=', 3],['field_1', '>', 20],],['not', ['field_2', '<', -5]],['field_1', 'regexp', '/^prefix-[^-]+-suffix$/'],['field_3', 'in', [2, NULL]],['field_4', '!in', [4, 12]],['field_5', '<=', 3],['field_5', '>=', 12],['field_6', '><', [20, 30]],['date', '>', DateTime::__set_state(array(
   'date' => '2018-07-19 00:00:00.000000',
   'timezone_type' => 3,
   'timezone' => 'UTC',
))],]",
            $filter . ''
        );


    }

    /**/
}
