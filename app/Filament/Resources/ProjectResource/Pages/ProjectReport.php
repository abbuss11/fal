<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ProjectReport extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.project-report';

    protected static ?string $title = 'Rapport Projet';

    public array $report = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->refreshReport();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Modifier projet')
                ->icon('heroicon-o-pencil-square')
                ->url(ProjectResource::getUrl('edit', ['record' => $this->record])),
            Action::make('download')
                ->label('Telecharger JSON')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('client.projects.report.download', ['project' => $this->record]))
                ->openUrlInNewTab(),
            Action::make('download_pdf')
                ->label('Exporter PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (): string => route('client.projects.report.download-pdf', ['project' => $this->record]))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'echo-private:admin.tasks,ProjectWorkspaceUpdated' => 'refreshReport',
        ];
    }

    public function refreshReport(): void
    {
        $this->report = app(ProjectReportService::class)->build($this->getRecord());
    }

    public function getRecord(): Project
    {
        /** @var Project $record */
        $record = $this->record;

        return $record;
    }
}
