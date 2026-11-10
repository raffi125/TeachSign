<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();
        $category = $request->string('category')->trim()->toString();

        $lessons = Lesson::query()
            ->with('creator:id,username')
            ->withCount('quizzes')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->latest('id')
            ->paginate(10);

        return response()->json($lessons);
    }

    public function store(StoreLessonRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $lesson = Lesson::create($data);

        return response()->json($lesson, 201);
    }

    public function show(Lesson $lesson): JsonResponse
    {
        $lesson->load(['creator:id,username', 'quizzes' => fn ($query) => $query->latest('id')]);

        return response()->json($lesson);
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson): JsonResponse
    {
        $lesson->update($request->validated());

        return response()->json($lesson->fresh());
    }

    public function destroy(Lesson $lesson): JsonResponse
    {
        $lesson->delete();

        return response()->json(['message' => 'Pelajaran berhasil dihapus.']);
    }
}
