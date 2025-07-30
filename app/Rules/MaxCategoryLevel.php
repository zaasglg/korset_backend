<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxCategoryLevel implements ValidationRule
{
    protected int $maxLevel;

    public function __construct(int $maxLevel = 3)
    {
        $this->maxLevel = $maxLevel;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) {
            return; // Allow null values (main categories)
        }

        $parent = Category::find($value);
        
        if (!$parent) {
            $fail('Выбранная родительская категория не существует.');
            return;
        }

        $parentLevel = $parent->getLevel();
        
        if ($parentLevel >= $this->maxLevel) {
            $fail("Нельзя создать категорию ниже {$this->maxLevel}-го уровня. Родительская категория уже находится на уровне {$parentLevel}.");
        }
    }
}
