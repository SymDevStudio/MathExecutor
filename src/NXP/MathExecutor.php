<?php

/**
 * This file is part of the MathExecutor package
 *
 * (c) Alexander Kiryukhin
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace NXP;

use NXP\Classes\Calculator;
use NXP\Classes\Lexer;
use NXP\Classes\Token;
use NXP\Classes\TokenFactory;
use NXP\Exception\UnknownVariableException;

/**
 * Class MathExecutor
 * @package NXP
 */
class MathExecutor
{
    /**
     * @var TokenFactory
     */
    protected $tokenFactory;
    
    /**
     * Available variables
     *
     * @var array
     */
    private $variables = [];

    /**
     * @var array
     */
    private $cache = [];

    /**
     * Base math operators
     */
    public function __construct()
    {
        $this->addDefaults();
    }

    public function __clone()
    {
        $this->addDefaults();
    }

    /**
     * Set default operands and functions
     */
    protected function addDefaults()
    {
        $this->tokenFactory = new TokenFactory();

        $this->tokenFactory->addOperator('NXP\Classes\Token\TokenPlus');
        $this->tokenFactory->addOperator('NXP\Classes\Token\TokenMinus');
        $this->tokenFactory->addOperator('NXP\Classes\Token\TokenMultiply');
        $this->tokenFactory->addOperator('NXP\Classes\Token\TokenDivision');
        $this->tokenFactory->addOperator('NXP\Classes\Token\TokenDegree');

        $this->tokenFactory->addFunction('sin', 'sin');
        $this->tokenFactory->addFunction('cos', 'cos');
        $this->tokenFactory->addFunction('tn', 'tan');
        $this->tokenFactory->addFunction('asin', 'asin');
        $this->tokenFactory->addFunction('acos', 'acos');
        $this->tokenFactory->addFunction('atn', 'atan');
        $this->tokenFactory->addFunction('min', 'min', 2);
        $this->tokenFactory->addFunction('max', 'max', 2);
        $this->tokenFactory->addFunction('avg', function($arg1, $arg2) { return ($arg1 + $arg2) / 2; }, 2);

        $this->setVars([
            'pi' => 3.14159265359,
            'e'  => 2.71828182846
        ]);
    }

    /**
     * Get all vars
     *
     * @return array
     */
    public function getVars()
    {
        return $this->variables;
    }

    /**
     * Get a specific var
     *
     * @param  string        $variable
     * @return integer|float
     * @throws UnknownVariableException
     */
    public function getVar($variable)
    {
        if (! isset($this->variables[$variable])) {
            throw new UnknownVariableException("Variable ({$variable}) not set");
        }

        return $this->variables[$variable];
    }

    /**
     * Add variable to executor
     *
     * @param  string        $variable
     * @param  integer|float $value
     * @return MathExecutor
     */
    public function setVar($variable, $value)
    {
        if (!is_numeric($value)) {
            throw new \Exception("Variable ({$variable}) value must be a number ({$value}) type ({gettype($value)})");
        }

        $this->variables[$variable] = $value;

        return $this;
    }

    /**
     * Add variables to executor
     *
     * @param  array        $variables
     * @param  bool         $clear     Clear previous variables
     * @return MathExecutor
     */
    public function setVars(array $variables, $clear = true)
    {
        if ($clear) {
            $this->removeVars();
        }

        foreach ($variables as $name => $value) {
            $this->setVar($name, $value);
        }

        return $this;
    }

    /**
     * Remove variable from executor
     *
     * @param  string       $variable
     * @return MathExecutor
     */
    public function removeVar($variable)
    {
        unset ($this->variables[$variable]);

        return $this;
    }

    /**
     * Remove all variables
     */
    public function removeVars()
    {
        $this->variables = [];

        return $this;
    }

    /**
     * Add operator to executor
     *
     * @param  string       $operatorClass Class of operator token
     * @return MathExecutor
     */
    public function addOperator($operatorClass)
    {
        $this->tokenFactory->addOperator($operatorClass);

        return $this;
    }

    /**
     * Get all registered operators to executor
     *
     * @return array of operator class names
     */
    public function getOperators()
    {
        return $this->tokenFactory->getOperators();
    }

    /**
     * Add function to executor
     *
     * @param  string       $name     Name of function
     * @param  callable     $function Function
     * @param  int          $places   Count of arguments
     * @return MathExecutor
     */
    public function addFunction($name, $function = null, $places = 1)
    {
        $this->tokenFactory->addFunction($name, $function, $places);

        return $this;
    }

    /**
     * Get all registered functions
     *
     * @return array containing callback and places indexed by
     *         function name
     */
    public function getFunctions()
    {
        return $this->tokenFactory->getFunctions();
    }

    /**
     * Set division by zero exception reporting
     *
     * @param bool $exception default true
     *
     * @return MathExecutor
     */
    public function setDivisionByZeroException($exception = true)
    {
        $this->tokenFactory->setDivisionByZeroException($exception);
        return $this;
    }

    /**
     * Get division by zero exception status
     *
     * @return bool
     */
    public function getDivisionByZeroException()
    {
        return $this->tokenFactory->getDivisionByZeroException();
    }

    /**
     * Execute expression
     *
     * @param $expression
     * @return number
     */
    public function execute($expression)
    {
        if (!array_key_exists($expression, $this->cache)) {
            $lexer = new Lexer($this->tokenFactory);
            $tokensStream = $lexer->stringToTokensStream($expression);
            $tokens = $lexer->buildReversePolishNotation($tokensStream);
            $this->cache[$expression] = $tokens;
        } else {
            $tokens = $this->cache[$expression];
        }
        $calculator = new Calculator();
        $result = $calculator->calculate($tokens, $this->variables);

        return $result;
    }
}
