<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ImportProjectPdfRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Services\PortfolioPdfImportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortfolioPdfController extends Controller
{
    public function download(Request $request, User $student): Response
    {
        if (! $student->isStudent() || ! $student->is_active) {
            abort(404);
        }
        $this->authorize('downloadPortfolioPdf', $student);
        $viewer = $request->user();
        if (! $viewer) {
            abort(401);
        }

        $ids = $student->projects()->pluck('projects.id');
        $projects = Project::query()
            ->whereIn('projects.id', $ids)
            ->visibleFor($viewer)
            ->orderBy('projects.id')
            ->get();

        $pdf = Pdf::loadView('pdf.portfolio', [
            'user' => $student,
            'projects' => $projects,
        ])->setPaper('a4', 'portrait');

        $filename = 'portfolio-'.$student->id.'-'.date('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function import(ImportProjectPdfRequest $request, PortfolioPdfImportService $extractor): ProjectResource
    {
        $user = $request->user();
        if (! $user?->isStudent()) {
            abort(403);
        }
        $this->authorize('importProjectFromPdf', $user);
        $path = $request->file('pdf')->store('tmp-imports', 'local');
        $full = storage_path('app/'.$path);
        try {
            $data = $extractor->extractFromPath($full);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        Storage::disk('local')->delete($path);

        $project = Project::query()->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'github_url' => null,
            'technologies' => [],
            'is_published' => false,
        ]);
        $project->syncStudents([$user->id], false, $user);
        $project->load('students');
        $project->loadCount('likes');
        $project->setAttribute('liked_by_me', false);

        return new ProjectResource($project);
    }

    public function downloadProject(Request $request, Project $project): Response
    {
        $this->authorize('downloadPdf', $project);

        $pdf = Pdf::loadView('pdf.project', [
            'project' => $project,
        ])->setPaper('a4', 'portrait');

        $base = Str::slug(Str::limit($project->title, 48, ''));
        $filename = ($base !== '' ? $base : 'project').'-'.$project->id.'-'.date('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
