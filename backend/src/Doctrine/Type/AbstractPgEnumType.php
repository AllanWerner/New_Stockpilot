<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * Base DBAL type for the native PostgreSQL ENUM types created by the Jalon 3 DDL
 * (role_employe, poste_employe, statut_commande, type_mouvement). Each concrete
 * subclass just binds a PHP backed enum to the matching Postgres enum type name.
 */
abstract class AbstractPgEnumType extends Type
{
    /** @return class-string<\BackedEnum> */
    abstract protected function enumClass(): string;

    abstract protected function typeName(): string;

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $this->typeName();
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if (is_string($value)) {
            return $value;
        }

        throw InvalidType::new($value, $this->typeName(), ['null', 'string', $this->enumClass()]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\BackedEnum
    {
        if (null === $value || $value instanceof \BackedEnum) {
            return $value;
        }

        $enumClass = $this->enumClass();

        try {
            return $enumClass::from((string) $value);
        } catch (\ValueError $e) {
            throw ValueNotConvertible::new($value, $this->typeName(), null, $e);
        }
    }

    public function getName(): string
    {
        return $this->typeName();
    }
}
