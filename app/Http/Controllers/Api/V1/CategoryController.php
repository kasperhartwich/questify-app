<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Categories
 */
class CategoryController extends Controller
{
    /**
     * List categories
     *
     * Get all quest categories ordered alphabetically.
     *
     * @unauthenticated
     *
     * @response 200 {"data": [{"id": 1, "name": "History", "slug": "history", "icon": "castle", "color": "#8B5CF6", "sort_order": 0}]}
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()->orderBy('name')->get()
        );
    }
}
