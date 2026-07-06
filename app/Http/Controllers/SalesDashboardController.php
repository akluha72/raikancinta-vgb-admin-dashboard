<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SalesDashboardController extends Controller
{
    /**
     * The sales analytics landing page. All month grouping is done in PHP (not
     * SQL date functions) so the same code runs on SQLite (dev) and MySQL
     * (prod) — see the plan's portability note.
     */
    public function index(): View
    {
        $months = $this->recentMonths(6);
        $projects = Project::query()->get();
        $leads = Lead::query()->get(['id', 'source', 'created_at']);

        return view('sales.dashboard', [
            'kpis' => $this->kpis($projects, $leads),
            'funnel' => $this->funnel($projects),
            'revenueSeries' => $this->revenueSeries($projects, $months),
            'winLoss' => $this->winLoss($projects),
            'leadSource' => $this->leadSource($leads),
            'leadVolume' => $this->leadVolume($leads, $months),
        ]);
    }

    /**
     * Headline KPI tiles.
     *
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, Lead>  $leads
     * @return array<string, mixed>
     */
    private function kpis(Collection $projects, Collection $leads): array
    {
        $won = $projects->where('stage', 'won');
        $lost = $projects->where('stage', 'lost');
        $open = $projects->reject(fn (Project $p) => $p->isClosed());

        $closedCount = $won->count() + $lost->count();

        return [
            'total_leads' => $leads->count(),
            'open_projects' => $open->count(),
            'pipeline_value' => (float) $open->sum('value'),
            'revenue' => (float) $won->sum('value'),
            'profit' => (float) $won->sum(fn (Project $p) => (float) $p->value - (float) $p->cost),
            // Deal win rate among closed deals.
            'win_rate' => $closedCount > 0 ? round($won->count() / $closedCount * 100) : 0,
        ];
    }

    /**
     * Pipeline funnel: count + total value per configured stage, in pipeline
     * order.
     *
     * @param  Collection<int, Project>  $projects
     * @return array<string, mixed>
     */
    private function funnel(Collection $projects): array
    {
        $labels = [];
        $counts = [];
        $values = [];
        $colors = [];

        foreach (config('crm.pipeline') as $key => $meta) {
            $inStage = $projects->where('stage', $key);
            $labels[] = $meta['label'];
            $counts[] = $inStage->count();
            $values[] = (float) $inStage->sum('value');
            $colors[] = $meta['hex'] ?? '#9ca3af';
        }

        return compact('labels', 'counts', 'values', 'colors');
    }

    /**
     * Monthly revenue / cost / profit from WON projects, keyed by the month the
     * deal closed (closed_at, falling back to updated_at).
     *
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, array{key: string, label: string}>  $months
     * @return array<string, mixed>
     */
    private function revenueSeries(Collection $projects, Collection $months): array
    {
        $won = $projects->where('stage', 'won');

        $revenue = [];
        $cost = [];
        $profit = [];

        foreach ($months as $month) {
            $inMonth = $won->filter(function (Project $p) use ($month) {
                $when = $p->closed_at ?? $p->updated_at ?? $p->created_at;

                return $when && $when->format('Y-m') === $month['key'];
            });

            $r = (float) $inMonth->sum('value');
            $c = (float) $inMonth->sum('cost');
            $revenue[] = $r;
            $cost[] = $c;
            $profit[] = $r - $c;
        }

        return [
            'labels' => $months->pluck('label')->all(),
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
        ];
    }

    /**
     * Won vs lost counts and values.
     *
     * @param  Collection<int, Project>  $projects
     * @return array<string, mixed>
     */
    private function winLoss(Collection $projects): array
    {
        $won = $projects->where('stage', 'won');
        $lost = $projects->where('stage', 'lost');

        return [
            'won_count' => $won->count(),
            'lost_count' => $lost->count(),
            'won_value' => (float) $won->sum('value'),
            'lost_value' => (float) $lost->sum('value'),
        ];
    }

    /**
     * Lead count by source (doughnut).
     *
     * @param  Collection<int, Lead>  $leads
     * @return array<string, mixed>
     */
    private function leadSource(Collection $leads): array
    {
        $labels = [];
        $counts = [];

        foreach (config('crm.lead_sources') as $key => $label) {
            $labels[] = $label;
            $counts[] = $leads->where('source', $key)->count();
        }

        return compact('labels', 'counts');
    }

    /**
     * New leads per month (line).
     *
     * @param  Collection<int, Lead>  $leads
     * @param  Collection<int, array{key: string, label: string}>  $months
     * @return array<string, mixed>
     */
    private function leadVolume(Collection $leads, Collection $months): array
    {
        $counts = [];

        foreach ($months as $month) {
            $counts[] = $leads->filter(
                fn (Lead $l) => $l->created_at && $l->created_at->format('Y-m') === $month['key']
            )->count();
        }

        return [
            'labels' => $months->pluck('label')->all(),
            'counts' => $counts,
        ];
    }

    /**
     * Build an ordered list of the last N months (oldest first) as
     * ['key' => 'YYYY-MM', 'label' => 'Mon YY'].
     *
     * @return Collection<int, array{key: string, label: string}>
     */
    private function recentMonths(int $count): Collection
    {
        $start = now()->startOfMonth()->subMonths($count - 1);

        return collect(range(0, $count - 1))->map(function (int $i) use ($start) {
            $month = (clone $start)->addMonths($i);

            return [
                'key' => $month->format('Y-m'),
                'label' => $month->format('M y'),
            ];
        });
    }
}
