<?php

namespace Spatie\Searchable;

class AdvancedAttribute
{
    /** @var string */
    protected $advancedAttribute;


    public function __construct(string $advancedAttribute)
    {
        $this->advancedAttribute = $advancedAttribute;

    }

    public static function create(string $advancedAttribute): self
    {
        return new self($advancedAttribute);
    }

    public static function createMany(array $advancedAttributes): array
    {

        return collect($advancedAttributes)
            ->map(function ($advancedAttribute) {
                return new self($advancedAttribute);
            })
            ->toArray();

    }

    public function getAdvancedAttribute(): string
    {
        return $this->advancedAttribute;
    }

}
