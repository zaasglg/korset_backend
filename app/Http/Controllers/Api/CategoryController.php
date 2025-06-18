<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(): JsonResponse
    {
        $categories = Category::with(['children.children'])
            ->whereNull('parent_id')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'photo' => $category->photo,
                    'level' => $category->getLevel(),
                    'children' => $category->children->map(function ($subcategory) {
                        return [
                            'id' => $subcategory->id,
                            'name' => $subcategory->name,
                            'description' => $subcategory->description,
                            'photo' => $subcategory->photo,
                            'level' => $subcategory->getLevel(),
                            'children' => $subcategory->children->map(function ($subsubcategory) {
                                return [
                                    'id' => $subsubcategory->id,
                                    'name' => $subsubcategory->name,
                                    'description' => $subsubcategory->description,
                                    'photo' => $subsubcategory->photo,
                                    'level' => $subsubcategory->getLevel(),
                                ];
                            })
                        ];
                    })
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): JsonResponse
    {
        $category->load(['children', 'parameters']);

        return response()->json([
            'status' => 'success',
            'data' => $category
        ]);
    }

    /**
     * Get parameters for the specified category.
     */
    public function parameters(Category $category): JsonResponse
    {
        $parameters = $category->parameters;

        return response()->json([
            'status' => 'success',
            'data' => $parameters
        ]);
    }

    /**
     * Get categories by level.
     */
    public function byLevel(int $level): JsonResponse
    {
        $query = Category::query();

        switch ($level) {
            case 1:
                $query->whereNull('parent_id');
                break;
            case 2:
                $query->whereHas('parent', fn($q) => $q->whereNull('parent_id'));
                break;
            case 3:
                $query->whereHas('parent.parent', fn($q) => $q->whereNull('parent_id'));
                break;
            default:
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid level. Allowed levels: 1, 2, 3'
                ], 400);
        }

        $categories = $query->with(['parent', 'children'])->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'photo' => $category->photo,
                'level' => $category->getLevel(),
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                ] : null,
                'children_count' => $category->children->count(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $categories,
            'meta' => [
                'level' => $level,
                'count' => $categories->count()
            ]
        ]);
    }

    /**
     * Get full hierarchy path for a category.
     */
    public function hierarchy(Category $category): JsonResponse
    {
        $path = [];
        $current = $category;

        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'name' => $current->name,
                'level' => $current->getLevel(),
            ]);
            $current = $current->parent;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'photo' => $category->photo,
                    'level' => $category->getLevel(),
                ],
                'hierarchy_path' => $path,
                'main_category' => $category->getMainCategory() ? [
                    'id' => $category->getMainCategory()->id,
                    'name' => $category->getMainCategory()->name,
                ] : null
            ]
        ]);
    }

    /**
     * Get all descendants of a category.
     */
    public function descendants(Category $category): JsonResponse
    {
        $descendants = $category->getAllDescendants()->map(function ($descendant) {
            return [
                'id' => $descendant->id,
                'name' => $descendant->name,
                'description' => $descendant->description,
                'photo' => $descendant->photo,
                'level' => $descendant->getLevel(),
                'parent_id' => $descendant->parent_id,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $descendants,
            'meta' => [
                'parent_category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'level' => $category->getLevel(),
                ],
                'total_descendants' => $descendants->count()
            ]
        ]);
    }
}
