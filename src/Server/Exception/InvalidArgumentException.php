<?php

namespace ZF\Apigility\Doctrine\Server\Exception;

use InvalidArgumentExcpetion as PHPInvalidArgumentException;

/**
 * Class InvalidArgumentException
 *
 * @package ZF\Apigility\Doctrine\Server\Exception
 */
class InvalidArgumentException extends PHPInvalidArgumentException implements ExceptionInterface
{
}
