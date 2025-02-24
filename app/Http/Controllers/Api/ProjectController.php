<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\ProjectResource;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user) {
            $projects = Project::whereHas('company', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->with(['company', 'client'])->latest()->paginate(10);
        } else {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request)
    {
        $validatedData = $request->validated();
        $project = Project::create($validatedData);
        return new ProjectResource($project);
    }

    public function show(Project $project)
    {
        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $validatedData = $request->validated();
        $project->update($validatedData);
        return new ProjectResource($project);
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(null, 204);
    }
}
