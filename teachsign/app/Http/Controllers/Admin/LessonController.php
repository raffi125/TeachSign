<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $category = $request->string('category')->trim()->toString();

        $lessons = Lesson::query()
            ->with('creator:id,username')
            ->withCount('quizzes')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.lessons.index', compact('lessons', 'search', 'category'));
    }

    public function create(): View
    {
        return view('admin.lessons.create');
    }

    public function store(StoreLessonRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        Lesson::create($data);

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Pelajaran berhasil ditambahkan.');
    }

    public function show(Lesson $lesson): View
    {
        $lesson->load(['creator:id,username', 'quizzes' => fn ($query) => $query->latest('id')]);

        return view('admin.lessons.show', compact('lesson'));
    }

    public function edit(Lesson $lesson): View
    {
        return view('admin.lessons.edit', compact('lesson'));
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $lesson->update($request->validated());

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Pelajaran berhasil diperbarui.');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $lesson->delete();

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Pelajaran berhasil dihapus.');
    }
}
