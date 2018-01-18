<?php

namespace ZFTestApigilityDb\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class RevType extends Type
{
    const NAME = 'rev';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getGuidTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (! $value) {
            return null;
        }

        return strrev($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (! $value) {
            return null;
        }

        return strrev($value);
    }

    public function getName()
    {
        return static::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
