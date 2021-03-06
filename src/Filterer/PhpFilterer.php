<?php
/**
 * PhpFilterer
 *
 * @package php-logical-filter
 * @author  Jean Claveau
 */
namespace JClaveau\LogicalFilter\Filterer;
use       JClaveau\LogicalFilter\LogicalFilter;
use       JClaveau\LogicalFilter\Rule\EqualRule;
use       JClaveau\LogicalFilter\Rule\BelowRule;
use       JClaveau\LogicalFilter\Rule\AboveRule;
use       JClaveau\LogicalFilter\Rule\NotEqualRule;

/**
 */
class PhpFilterer extends Filterer
{
    /**
     */
    public function validateRule ($field, $operator, $value, $row, $depth, $all_operands, $options)
    {
        if ($operator === EqualRule::operator) {
            if ($value === null) {
                $result = !isset($row[$field]);
            }
            elseif (!isset($row[$field])) {
                $result = false;
            }
            else {
                // TODO support strict comparisons
                $result = $row[$field] == $value;
            }
        }
        elseif ($operator === BelowRule::operator) {
            if (!isset($row[$field])) {
                $result = false;
            }
            else {
                $result = $row[$field] < $value;
            }
        }
        elseif ($operator === AboveRule::operator) {
            if (!isset($row[$field])) {
                $result = false;
            }
            else {
                $result = $row[$field] > $value;
            }
        }
        elseif ($operator === NotEqualRule::operator) {
            if ($value === null) {
                $result = isset($row[$field]);
            }
            else {
                throw new \InvalidArgumentException(
                    "This case shouldn't occure with teh current simplification strategy"
                );
                // return $row[$field] == $operand->getValue();
            }
        }
        else {
            throw new \InvalidArgumentException(
                "Unhandled operator: " . $operator
            );
        }

        // var_dump(
            // "$field, $operator, " . var_export($value, true)
             // . ' vs ' . var_export($row, true) . ' => ' . var_export($result, true)
        // );
        return $result;
    }

    /**/
}
