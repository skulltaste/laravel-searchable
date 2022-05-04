<?php

namespace Spatie\Searchable;

class AdvancedValues
{
    /** @var string */
    protected $value;

    public function __construct(string $value)
    {
        $this->value = $value;

    }

    public static function create(string $value): self
    {
        return new self($value);
    }


    public static function createMany(array $values): array
    {

        return collect($values)
            ->map(function ($value) {
                return new self($value);
            })
            ->toArray();

    }

    public function getvalue(): string
    {
        return $this->value;
    }

}
