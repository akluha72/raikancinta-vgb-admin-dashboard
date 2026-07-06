<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Show the create-project form for a specific lead. The lead is passed as
     * a query param from the lead detail page.
     */
    public function create(Request $request): View
    {
        $lead = Lead::findOrFail($request->query('lead'));

        return view('projects.create', [
            'lead' => $lead,
            'events' => $this->linkableEvents(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['closed_at'] = $this->closedAtFor($data['stage'], null);

        $project = Project::create($data);

        return redirect()
            ->route('leads.show', $project->lead_id)
            ->with('status', "Project \"{$project->title}\" added.");
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', [
            'project' => $project,
            'events' => $this->linkableEvents(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $data['closed_at'] = $this->closedAtFor($data['stage'], $project);

        $project->update($data);

        return redirect()
            ->route('leads.show', $project->lead_id)
            ->with('status', 'Project updated.');
    }

    /**
     * Quick stage change from the lead detail page (a small inline form per
     * project). Keeps closed_at in sync with terminal stages.
     */
    public function updateStage(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'stage' => ['required', Rule::in(array_keys(config('crm.pipeline')))],
        ]);

        $project->update([
            'stage' => $validated['stage'],
            'closed_at' => $this->closedAtFor($validated['stage'], $project),
        ]);

        return redirect()
            ->route('leads.show', $project->lead_id)
            ->with('status', "Moved \"{$project->title}\" to {$project->stage_label}.");
    }

    /**
     * Events that can be linked to a project: the newest events, so the owner
     * can attach a provisioned guestbook to the deal. Kept small for the
     * dropdown.
     *
     * @return \Illuminate\Support\Collection<int, Event>
     */
    private function linkableEvents(): \Illuminate\Support\Collection
    {
        return Event::query()
            ->latest('id')
            ->limit(200)
            ->get(['id', 'couple_name', 'wedding_date']);
    }

    /**
     * Resolve closed_at for a stage transition: stamp now() when entering a
     * terminal stage (and it wasn't already closed), clear it when reopening.
     */
    private function closedAtFor(string $stage, ?Project $project): ?\Illuminate\Support\Carbon
    {
        $isClosed = ! empty(config("crm.pipeline.{$stage}.closed"));

        if (! $isClosed) {
            return null;
        }

        // Preserve an existing close timestamp; otherwise stamp now.
        return $project?->closed_at ?? now();
    }
}
