<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadOwnerRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    /**
     * List leads, newest first, with a name/phone/email search and optional
     * status + source filters. Mirrors EventController@index.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $source = (string) $request->query('source', '');

        $leads = Lead::query()
            ->withCount('projects')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($source !== '', fn ($q) => $q->where('source', $source))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('leads.index', [
            'leads' => $leads,
            'search' => $search,
            'status' => $status,
            'source' => $source,
        ]);
    }

    public function create(): View
    {
        return view('leads.create');
    }

    public function store(StoreLeadOwnerRequest $request): RedirectResponse
    {
        $lead = Lead::create($request->validated());

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', "Lead \"{$lead->name}\" created.");
    }

    /**
     * Show one lead with all its projects (and any linked event) so the page
     * can render per-project progress.
     */
    public function show(Lead $lead): View
    {
        $lead->load(['projects' => fn ($q) => $q->latest('id'), 'projects.event']);

        return view('leads.show', ['lead' => $lead]);
    }

    public function edit(Lead $lead): View
    {
        return view('leads.edit', ['lead' => $lead]);
    }

    public function update(StoreLeadOwnerRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'Lead updated.');
    }
}
