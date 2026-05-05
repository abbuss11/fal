<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectReportController extends Controller
{
    public function __construct(private readonly ProjectReportService $reportService) {}

    public function show(Request $request, Project $project): View
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);

        $report = $this->reportService->build($project);

        return view('client.projects.report', [
            'project' => $project,
            'report' => $report,
            'statusLabels' => Task::statusOptions(),
            'priorityLabels' => Task::priorityOptions(),
            'roleLabels' => User::roleOptions(),
        ]);
    }

    public function download(Request $request, Project $project): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);

        $report = $this->reportService->build($project);
        $filename = 'project-report-'.$project->id.'-'.now()->format('Ymd-His').'.json';

        return response()->streamDownload(function () use ($report): void {
            echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function downloadPdf(Request $request, Project $project): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessProject($project), 403);

        $report = $this->reportService->build($project);
        $filename = 'project-report-'.$project->id.'-'.now()->format('Ymd-His').'.pdf';

        $pdf = Pdf::loadView('client.projects.report-pdf', [
            'project' => $project,
            'report' => $report,
            'statusLabels' => Task::statusOptions(),
            'priorityLabels' => Task::priorityOptions(),
            'roleLabels' => User::roleOptions(),
        ])->setPaper('a4');

        return $pdf->download($filename);
    }
}
