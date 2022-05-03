<?php

namespace Spatie\Searchable;

class SearchableAttribute
{
    /** @var string */
    protected $attribute;

    /** @var string */
    protected $opperator;

    /** @var bool */
    protected $partial;

    public function __construct(string $attribute, string $opperator = null, bool $partial = true)
    {
        $this->attribute = $attribute;
        $this->opperator = $opperator;
        $this->partial = $partial;

    }

    public static function create(string $attribute, string $opperator = null, bool $partial = true): self
    {
        return new self($attribute, $opperator, $partial);
    }

    public static function createExact(string $attribute,string $opperator = null): self
    {
        return static::create($attribute, $opperator,false);
    }

    public static function createMany(array $attributes, array $opperators = null): array
    {

        return collect($attributes)
            ->map(function ($attribute) {
                return new self($attribute);
            })
            ->toArray();

    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getOpperator(): string
    {
        return $this->opperator;
    }

    public function isPartial(): bool
    {
        return $this->partial;
    }
}
