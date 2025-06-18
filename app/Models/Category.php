<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'photo',
        'parent_id',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get the products in this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the parameters for this category.
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(ProductParameter::class);
    }

    /**
     * Get the level of this category (1, 2, or 3).
     */
    public function getLevel(): int
    {
        if (!$this->parent_id) {
            return 1; // Main category
        }
        
        if (!$this->parent?->parent_id) {
            return 2; // Subcategory
        }
        
        return 3; // Sub-subcategory
    }

    /**
     * Check if this is a main category (level 1).
     */
    public function isMainCategory(): bool
    {
        return $this->getLevel() === 1;
    }

    /**
     * Check if this is a subcategory (level 2).
     */
    public function isSubcategory(): bool
    {
        return $this->getLevel() === 2;
    }

    /**
     * Check if this is a sub-subcategory (level 3).
     */
    public function isSubSubcategory(): bool
    {
        return $this->getLevel() === 3;
    }

    /**
     * Get the main category (root parent).
     */
    public function getMainCategory(): ?Category
    {
        if ($this->isMainCategory()) {
            return $this;
        }
        
        if ($this->isSubcategory()) {
            return $this->parent;
        }
        
        return $this->parent?->parent;
    }

    /**
     * Get all descendants (children and grandchildren).
     */
    public function getAllDescendants(): \Illuminate\Database\Eloquent\Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }
        
        return $descendants;
    }

    /**
     * Scope for main categories only.
     */
    public function scopeMainCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for subcategories only.
     */
    public function scopeSubcategories($query)
    {
        return $query->whereHas('parent', function ($query) {
            $query->whereNull('parent_id');
        });
    }

    /**
     * Scope for sub-subcategories only.
     */
    public function scopeSubSubcategories($query)
    {
        return $query->whereHas('parent.parent', function ($query) {
            $query->whereNull('parent_id');
        });
    }
}
