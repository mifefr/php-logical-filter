<?php
namespace JClaveau\LogicalFilter\Rule;

use JClaveau\VisibilityViolator\VisibilityViolator;
use JClaveau\LogicalFilter\LogicalFilter;

class InRuleTest extends \AbstractTest
{
    /**
     */
    public function test_addPossibilities()
    {
        $rule = new InRule('field', [4, 5, 6]);
        $rule->addPossibilities([7, 8, new EqualRule('field', 9)]);

        $this->assertEquals(
            [4, 5, 6, 7, 8, 9],
            $rule->getPossibilities()
        );

        try {
            $rule->addPossibilities([ new BelowRule('field', 3) ]);
            $this->assertTrue(
                false,
                 "An exception explaining that you cannot add something else "
                ."than values or an EqualRule having the same field"
            );
        }
        catch (\Exception $e) {
            $this->assertTrue(true, "Exception thrown: ".$e->getMessage());
        }

        try {
            $rule->addPossibilities([ new EqualRule('field_2', 3) ]);
            $this->assertTrue(
                false,
                 "An exception explaining that you cannot add something else "
                ."than values or an EqualRule having the same field"
            );
        }
        catch (\Exception $e) {
            $this->assertTrue(true, "Exception thrown: ".$e->getMessage());
        }
    }

    /**
     */
    public function test_getField()
    {
        $rule = new InRule('field', [4, 5, 6]);
        $this->assertEquals('field', $rule->getField());
    }

    /**
     */
    public function test_setOperands()
    {
        $rule = new InRule('field', [4, 5, 6]);

        $operands = $rule->getOperands();
        $operands[0] = new BelowRule('field_2', 3);

        try {
            $rule->setOperands($operands);
            // $this->assertFalse( $rule->getField() );
            $this->assertTrue(
                false,
                "An exception explaining that an invalid operand is set"
            );
        }
        catch (\Exception $e) {
            $this->assertTrue(true, "Exception thrown: ".$e->getMessage());
        }
    }

    /**/
}
