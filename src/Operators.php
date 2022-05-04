<?php

namespace Spatie\Searchable;

class Operators
{
    /** @var string */
    protected $operator;


    public function __construct(string $operator)
    {
        $this->operator = $operator;

    }

    public static function create(string $operator): self
    {
        return new self($operator);
    }

    public static function createMany(array $operators): array
    {

        return collect($operators)
            ->map(function ($operator) {
                return new self($operator);
            })
            ->toArray();

    }

    public function getoperator(): string
    {
        return $this->operator;
    }

}
