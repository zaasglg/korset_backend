<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class ManageCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:manage 
                           {action : Action to perform (list|stats|validate|create|delete)}
                           {--level=1 : Category level to filter (1,2,3)}
                           {--parent= : Parent category ID for filtering}
                           {--name= : Category name for create/delete actions}
                           {--description= : Category description for create action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage categories: list, show statistics, validate hierarchy, create or delete categories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        match($action) {
            'list' => $this->listCategories(),
            'stats' => $this->showStatistics(),
            'validate' => $this->validateHierarchy(),
            'create' => $this->createCategory(),
            'delete' => $this->deleteCategory(),
            default => $this->error("Unknown action: {$action}. Available actions: list, stats, validate, create, delete")
        };
    }

    /**
     * List categories with optional filtering.
     */
    private function listCategories(): void
    {
        $level = $this->option('level');
        $parentId = $this->option('parent');

        $query = Category::query();

        // Filter by level
        if ($level) {
            $query = match((int)$level) {
                1 => $query->whereNull('parent_id'),
                2 => $query->whereHas('parent', fn($q) => $q->whereNull('parent_id')),
                3 => $query->whereHas('parent.parent', fn($q) => $q->whereNull('parent_id')),
                default => $query
            };
        }

        // Filter by parent
        if ($parentId) {
            $query->where('parent_id', $parentId);
        }

        $categories = $query->with('parent')->orderBy('name')->get();

        if ($categories->isEmpty()) {
            $this->info('No categories found with the specified criteria.');
            return;
        }

        $headers = ['ID', 'Name', 'Description', 'Level', 'Parent', 'Children Count'];
        $rows = [];

        foreach ($categories as $category) {
            $rows[] = [
                $category->id,
                $category->name,
                str($category->description ?? '')->limit(50),
                $category->getLevel(),
                $category->parent?->name ?? '-',
                $category->children()->count(),
            ];
        }

        $this->table($headers, $rows);
        $this->info("Total: {$categories->count()} categories");
    }

    /**
     * Show category statistics.
     */
    private function showStatistics(): void
    {
        $totalCategories = Category::count();
        $mainCategories = Category::whereNull('parent_id')->count();
        $subcategories = Category::whereHas('parent', fn($q) => $q->whereNull('parent_id'))->count();
        $thirdLevel = Category::whereHas('parent.parent', fn($q) => $q->whereNull('parent_id'))->count();

        $this->info('📊 Category Statistics:');
        $this->line('');
        $this->line("📁 Total Categories: {$totalCategories}");
        $this->line("🔹 Level 1 (Main): {$mainCategories}");
        $this->line("🔸 Level 2 (Sub): {$subcategories}");
        $this->line("🔹 Level 3 (Sub-sub): {$thirdLevel}");
        $this->line('');

        // Show top main categories by children count
        $topCategories = Category::whereNull('parent_id')
            ->withCount('children')
            ->orderByDesc('children_count')
            ->limit(5)
            ->get();

        if ($topCategories->isNotEmpty()) {
            $this->line('🏆 Top Categories by Subcategories:');
            foreach ($topCategories as $category) {
                $this->line("   • {$category->name}: {$category->children_count} subcategories");
            }
        }
    }

    /**
     * Validate category hierarchy.
     */
    private function validateHierarchy(): void
    {
        $this->info('🔍 Validating category hierarchy...');
        
        $errors = [];
        $warnings = [];

        // Check for categories exceeding 3 levels
        $invalidLevels = Category::whereHas('parent.parent.parent')->get();
        if ($invalidLevels->isNotEmpty()) {
            $errors[] = "Found {$invalidLevels->count()} categories exceeding 3 levels";
            foreach ($invalidLevels as $category) {
                $errors[] = "  • Category '{$category->name}' (ID: {$category->id}) is at level 4+";
            }
        }

        // Check for orphaned categories
        $orphaned = Category::whereNotNull('parent_id')
            ->whereDoesntHave('parent')
            ->get();
        if ($orphaned->isNotEmpty()) {
            $errors[] = "Found {$orphaned->count()} orphaned categories";
            foreach ($orphaned as $category) {
                $errors[] = "  • Category '{$category->name}' (ID: {$category->id}) has invalid parent_id: {$category->parent_id}";
            }
        }

        // Check for empty main categories
        $emptyMain = Category::whereNull('parent_id')
            ->doesntHave('children')
            ->get();
        if ($emptyMain->isNotEmpty()) {
            $warnings[] = "Found {$emptyMain->count()} main categories without subcategories";
            foreach ($emptyMain as $category) {
                $warnings[] = "  • '{$category->name}' has no subcategories";
            }
        }

        // Display results
        if (empty($errors) && empty($warnings)) {
            $this->info('✅ Hierarchy validation passed! No issues found.');
        } else {
            if (!empty($errors)) {
                $this->error('❌ Errors found:');
                foreach ($errors as $error) {
                    $this->line($error);
                }
            }

            if (!empty($warnings)) {
                $this->warn('⚠️  Warnings:');
                foreach ($warnings as $warning) {
                    $this->line($warning);
                }
            }
        }
    }

    /**
     * Create a new category.
     */
    private function createCategory(): void
    {
        $name = $this->option('name') ?? $this->ask('Category name');
        $description = $this->option('description') ?? $this->ask('Category description (optional)');
        $parentId = $this->option('parent');

        if (!$name) {
            $this->error('Category name is required');
            return;
        }

        // Check if category already exists
        $query = Category::where('name', $name);
        if ($parentId) {
            $query->where('parent_id', $parentId);
        }

        if ($query->exists()) {
            $this->error("Category '{$name}' already exists" . ($parentId ? " under parent ID {$parentId}" : ''));
            return;
        }

        // Validate parent if provided
        $parent = null;
        if ($parentId) {
            $parent = Category::find($parentId);
            if (!$parent) {
                $this->error("Parent category with ID {$parentId} not found");
                return;
            }

            // Check level limit
            if ($parent->getLevel() >= 3) {
                $this->error('Cannot create category: maximum hierarchy level (3) would be exceeded');
                return;
            }
        }

        try {
            $category = Category::create([
                'name' => $name,
                'description' => $description,
                'parent_id' => $parentId,
            ]);

            $this->info("✅ Category '{$category->name}' created successfully (ID: {$category->id})");
            if ($parent) {
                $this->line("   Parent: {$parent->name}");
                $this->line("   Level: {$category->getLevel()}");
            }
        } catch (\Exception $e) {
            $this->error("Failed to create category: {$e->getMessage()}");
        }
    }

    /**
     * Delete a category.
     */
    private function deleteCategory(): void
    {
        $name = $this->option('name') ?? $this->ask('Category name to delete');
        
        if (!$name) {
            $this->error('Category name is required');
            return;
        }

        $category = Category::where('name', $name)->first();
        
        if (!$category) {
            $this->error("Category '{$name}' not found");
            return;
        }

        // Check if category has children
        $childrenCount = $category->children()->count();
        if ($childrenCount > 0) {
            if (!$this->confirm("Category '{$name}' has {$childrenCount} subcategories. Delete anyway? This will also delete all subcategories.")) {
                $this->info('Operation cancelled');
                return;
            }
        }

        // Show category info before deletion
        $this->line("Category to delete:");
        $this->line("  • Name: {$category->name}");
        $this->line("  • ID: {$category->id}");
        $this->line("  • Level: {$category->getLevel()}");
        $this->line("  • Children: {$childrenCount}");

        if (!$this->confirm('Are you sure you want to delete this category?')) {
            $this->info('Operation cancelled');
            return;
        }

        try {
            $deletedCount = $this->deleteRecursively($category);
            $this->info("✅ Successfully deleted {$deletedCount} categories");
        } catch (\Exception $e) {
            $this->error("Failed to delete category: {$e->getMessage()}");
        }
    }

    /**
     * Recursively delete a category and all its children.
     */
    private function deleteRecursively(Category $category): int
    {
        $count = 0;

        // Delete all children first
        foreach ($category->children as $child) {
            $count += $this->deleteRecursively($child);
        }

        // Delete the category itself
        $category->delete();
        $count++;

        return $count;
    }
}
