<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['category', 'author'])
            ->where('status', 'published');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->language) {
            $query->where('language', $request->language);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('content', 'like', "%{$request->search}%");
            });
        }

        $articles = $query->orderBy('views', 'desc')->paginate(20);
        return response()->json($articles);
    }

    public function show(string $id)
    {
        $article = Article::with(['category', 'author'])
            ->where('status', 'published')
            ->findOrFail($id);
        $article->increment('views');
        return response()->json($article);
    }

    public function getBySlug(string $slug)
    {
        $article = Article::with(['category', 'author'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();
        $article->increment('views');
        return response()->json($article);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'slug' => 'required|string|unique:articles,slug',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'sometimes|in:draft,published,archived',
            'language' => 'sometimes|in:en,es,fr,de,pt,ja,zh',
        ]);

        $article = Article::create([
            'title' => $request->title,
            'content' => $request->content,
            'slug' => $request->slug ?? Str::slug($request->title),
            'category_id' => $request->category_id,
            'author_id' => auth()->id(),
            'status' => $request->status ?? 'draft',
            'language' => $request->language ?? 'en',
        ]);

        return response()->json($article, 201);
    }

    public function update(Request $request, string $id)
    {
        $article = Article::findOrFail($id);
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'slug' => 'sometimes|string|unique:articles,slug,' . $id,
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'sometimes|in:draft,published,archived',
        ]);
        $article->update($request->only(['title', 'content', 'slug', 'category_id', 'status']));
        return response()->json($article);
    }
}
