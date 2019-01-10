<?php
/**
 * This file is part of the MathExecutor package
 *
 * (c) Alexander Kiryukhin
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace NXP\Classes;

use NXP\Classes\Token\InterfaceOperator;
use NXP\Classes\Token\TokenFunction;
use NXP\Classes\Token\TokenNumber;
use NXP\Classes\Token\TokenStringSingleQuoted;
use NXP\Classes\Token\TokenStringDoubleQuoted;
use NXP\Classes\Token\TokenVariable;
use NXP\Exception\IncorrectExpressionException;
use NXP\Exception\UnknownVariableException;

/**
 * @author Alexander Kiryukhin <a.kiryukhin@mail.ru>
 */
class Calculator
{
    /**
     * Calculate array of tokens in reverse polish notation
     * @param  array                                       $tokens    Array of tokens
     * @param  array                                       $variables Array of variables
     * @return number                                      Result
     * @throws \NXP\Exception\IncorrectExpressionException
     * @throws \NXP\Exception\UnknownVariableException
     */
    public function calculate($tokens, $variables)
    {
        $stack = [];
        foreach ($tokens as $token) {
            if ($token instanceof TokenNumber) {
                array_push($stack, $token);
            }
            if ($token instanceof TokenStringDoubleQuoted) {
                array_push($stack, $token);
            }
            if ($token instanceof TokenStringSingleQuoted) {
                array_push($stack, $token);
            }
            if ($token instanceof TokenVariable) {
                $variable = $token->getValue();
                if (!array_key_exists($variable, $variables)) {
                    throw new UnknownVariableException($variable);
                }
                $value = $variables[$variable];
                array_push($stack, new TokenNumber($value));
            }
            if ($token instanceof InterfaceOperator || $token instanceof TokenFunction) {
                array_push($stack, $token->execute($stack));
            }
        }
        $result = array_pop($stack);
        if ($result === null || ! empty($stack)) {
            throw new IncorrectExpressionException();
        }

        return $result->getValue();
    }
}
