<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Major;
use App\Models\Subject;
use App\Models\SubjectRequisite;
use Illuminate\Support\Collection;

class SubjectProgressionGraph
{
    private const COLUMN_GAP = 144;

    private const COMPONENT_GAP = 96;

    private const GOAL_NODE_HEIGHT = 156;

    private const GOAL_NODE_WIDTH = 256;

    private const GOAL_X_GAP = 184;

    private const NODE_HEIGHT = 104;

    private const NODE_WIDTH = 208;

    private const PADDING_X = 48;

    private const PADDING_Y = 40;

    private const ROW_GAP = 72;

    /**
     * @var array<int, array{chip:string,line:string}>
     */
    private const TRACK_PALETTE = [
        [
            'chip' => 'border-sky-200 text-sky-700 dark:border-sky-500/40 dark:text-sky-300',
            'line' => 'text-sky-500 stroke-sky-500 dark:text-sky-400 dark:stroke-sky-400',
        ],
        [
            'chip' => 'border-rose-200 text-rose-700 dark:border-rose-500/40 dark:text-rose-300',
            'line' => 'text-rose-500 stroke-rose-500 dark:text-rose-400 dark:stroke-rose-400',
        ],
        [
            'chip' => 'border-amber-200 text-amber-700 dark:border-amber-500/40 dark:text-amber-300',
            'line' => 'text-amber-500 stroke-amber-500 dark:text-amber-400 dark:stroke-amber-400',
        ],
        [
            'chip' => 'border-teal-200 text-teal-700 dark:border-teal-500/40 dark:text-teal-300',
            'line' => 'text-teal-500 stroke-teal-500 dark:text-teal-400 dark:stroke-teal-400',
        ],
        [
            'chip' => 'border-orange-200 text-orange-700 dark:border-orange-500/40 dark:text-orange-300',
            'line' => 'text-orange-500 stroke-orange-500 dark:text-orange-400 dark:stroke-orange-400',
        ],
        [
            'chip' => 'border-cyan-200 text-cyan-700 dark:border-cyan-500/40 dark:text-cyan-300',
            'line' => 'text-cyan-500 stroke-cyan-500 dark:text-cyan-400 dark:stroke-cyan-400',
        ],
    ];

    /**
     * @return array{
    *     canvasHeight:int,
    *     canvasWidth:int,
     *     components:Collection<int, array<string, mixed>>,
     *     componentCount:int,
     *     edgeCount:int,
    *     goal:array<string, mixed>|null,
     *     nodeCount:int
     * }
     */
    public function build(Course $course, ?Major $major = null, array $options = []): array
    {
        $course->loadMissing('department.college', 'majors');
        $gradeLabels = collect($options['gradeLabels'] ?? [])
            ->mapWithKeys(fn (mixed $grade, mixed $subjectId) => [(int) $subjectId => trim((string) $grade)]);

        $departmentSubjects = Subject::query()
            ->where('department_id', $course->department_id)
            ->with('department.college')
            ->orderBy('subject_code')
            ->get()
            ->keyBy(fn (Subject $subject) => (int) $subject->id);

        $applicableRequisites = SubjectRequisite::query()
            ->with([
                'subject.department.college',
                'requisiteSubject.department.college',
                'course',
                'major.course',
            ])
            ->get()
            ->filter(fn (SubjectRequisite $requisite) => $this->requisiteAppliesToScope($requisite, $course, $major))
            ->values();

        $includedSubjectIds = $this->includedSubjectIds(
            $this->seedSubjectIds($departmentSubjects, $applicableRequisites, $major),
            $applicableRequisites,
        );

        $subjects = $this->subjectMapForIds($includedSubjectIds, $departmentSubjects, $applicableRequisites);
        $edges = $this->edgeCollection($applicableRequisites, $includedSubjectIds);
        $components = $this->buildComponents($subjects, $edges);
        $graph = $this->renderGraph($course, $major, $subjects, $edges, $components, $gradeLabels);

        return [
            'canvasHeight' => $graph['canvasHeight'],
            'canvasWidth' => $graph['canvasWidth'],
            'components' => $components,
            'componentCount' => $components->count(),
            'edgeCount' => $graph['edgeCount'],
            'goal' => $graph['goal'],
            'graphEdges' => $graph['edges'],
            'graphNodes' => $graph['nodes'],
            'nodeCount' => $subjects->count(),
        ];
    }

    /**
     * @param  Collection<int, Subject>  $departmentSubjects
     * @param  Collection<int, SubjectRequisite>  $applicableRequisites
     * @return Collection<int, int>
     */
    private function seedSubjectIds(Collection $departmentSubjects, Collection $applicableRequisites, ?Major $major): Collection
    {
        $departmentSubjectIds = $departmentSubjects
            ->keys()
            ->map(fn (int|string $subjectId) => (int) $subjectId)
            ->values();

        if ($major === null) {
            return $departmentSubjectIds;
        }

        $departmentSubjectIdsWithAnyRelation = SubjectRequisite::query()
            ->whereIn('subject_id', $departmentSubjectIds->all())
            ->orWhereIn('requisite_subject_id', $departmentSubjectIds->all())
            ->get(['subject_id', 'requisite_subject_id'])
            ->flatMap(fn (SubjectRequisite $requisite) => [(int) $requisite->subject_id, (int) $requisite->requisite_subject_id])
            ->filter(fn (int $subjectId) => $departmentSubjects->has($subjectId))
            ->unique()
            ->values();

        $contextSubjectIds = $applicableRequisites
            ->flatMap(fn (SubjectRequisite $requisite) => [(int) $requisite->subject_id, (int) $requisite->requisite_subject_id])
            ->filter(fn (int $subjectId) => $departmentSubjects->has($subjectId))
            ->unique()
            ->values();
        $singletonDepartmentSubjectIds = $departmentSubjectIds->diff($departmentSubjectIdsWithAnyRelation)->values();

        return $contextSubjectIds
            ->merge($singletonDepartmentSubjectIds)
            ->unique()
            ->values();
    }

    private function requisiteAppliesToScope(SubjectRequisite $requisite, Course $course, ?Major $major): bool
    {
        if ($major !== null) {
            return $requisite->appliesTo($course->id, $major->id);
        }

        if ($requisite->major_id !== null) {
            return $requisite->major?->course_id === $course->id;
        }

        if ($requisite->course_id !== null) {
            return $requisite->course_id === $course->id;
        }

        return true;
    }

    /**
     * @param  Collection<int, int>  $seedSubjectIds
     * @param  Collection<int, SubjectRequisite>  $requisites
     * @return Collection<int, int>
     */
    private function includedSubjectIds(Collection $seedSubjectIds, Collection $requisites): Collection
    {
        $adjacency = [];

        foreach ($requisites as $requisite) {
            $subjectId = (int) $requisite->subject_id;
            $requisiteSubjectId = (int) $requisite->requisite_subject_id;

            $adjacency[$subjectId] ??= [];
            $adjacency[$requisiteSubjectId] ??= [];
            $adjacency[$subjectId][] = $requisiteSubjectId;
            $adjacency[$requisiteSubjectId][] = $subjectId;
        }

        $visited = [];
        $queue = $seedSubjectIds->values()->all();

        foreach ($queue as $subjectId) {
            $visited[(int) $subjectId] = true;
        }

        while ($queue !== []) {
            $subjectId = (int) array_shift($queue);

            foreach ($adjacency[$subjectId] ?? [] as $adjacentSubjectId) {
                $adjacentSubjectId = (int) $adjacentSubjectId;

                if (isset($visited[$adjacentSubjectId])) {
                    continue;
                }

                $visited[$adjacentSubjectId] = true;
                $queue[] = $adjacentSubjectId;
            }
        }

        return collect(array_keys($visited))
            ->map(fn (int|string $subjectId) => (int) $subjectId)
            ->values();
    }

    /**
     * @param  Collection<int, int>  $includedSubjectIds
     * @param  Collection<int, Subject>  $departmentSubjects
     * @param  Collection<int, SubjectRequisite>  $requisites
     * @return Collection<int, Subject>
     */
    private function subjectMapForIds(Collection $includedSubjectIds, Collection $departmentSubjects, Collection $requisites): Collection
    {
        $subjectPool = collect();

        foreach ($departmentSubjects as $subject) {
            $subjectPool->put((int) $subject->id, $subject);
        }

        foreach ($requisites as $requisite) {
            if ($requisite->subject !== null) {
                $subjectPool->put((int) $requisite->subject->id, $requisite->subject);
            }

            if ($requisite->requisiteSubject !== null) {
                $subjectPool->put((int) $requisite->requisiteSubject->id, $requisite->requisiteSubject);
            }
        }

        return $includedSubjectIds
            ->mapWithKeys(fn (int $subjectId) => [$subjectId => $subjectPool->get($subjectId)])
            ->filter();
    }

    /**
     * @param  Collection<int, SubjectRequisite>  $requisites
     * @param  Collection<int, int>  $includedSubjectIds
    * @return Collection<int, array{id:string,labels:array<int, string>,majorIds:array<int, int>,scopeLabels:array<int, string>,sourceId:int,targetId:int,type:string}>
     */
    private function edgeCollection(Collection $requisites, Collection $includedSubjectIds): Collection
    {
        $includedLookup = $includedSubjectIds->flip();
        $edges = [];

        foreach ($requisites as $requisite) {
            $sourceId = (int) $requisite->requisite_subject_id;
            $targetId = (int) $requisite->subject_id;

            if (! $includedLookup->has($sourceId) || ! $includedLookup->has($targetId)) {
                continue;
            }

            if ($requisite->type === 'corequisite' && $sourceId > $targetId) {
                [$sourceId, $targetId] = [$targetId, $sourceId];
            }

            $edgeKey = $requisite->type.'|'.$sourceId.'|'.$targetId;
            $scopeLabel = $this->displayScopeLabel($requisite);

            if (! isset($edges[$edgeKey])) {
                $edges[$edgeKey] = [
                    'id' => 'edge-'.md5($edgeKey),
                    'labels' => [],
                    'majorIds' => [],
                    'scopeLabels' => [],
                    'sourceId' => $sourceId,
                    'targetId' => $targetId,
                    'type' => $requisite->type,
                ];
            }

            if ($requisite->major_id !== null && ! in_array((int) $requisite->major_id, $edges[$edgeKey]['majorIds'], true)) {
                $edges[$edgeKey]['majorIds'][] = (int) $requisite->major_id;
            }

            if ($scopeLabel !== null && ! in_array($scopeLabel, $edges[$edgeKey]['scopeLabels'], true)) {
                $edges[$edgeKey]['scopeLabels'][] = $scopeLabel;
            }
        }

        return collect(array_values($edges));
    }

    private function displayScopeLabel(SubjectRequisite $requisite): ?string
    {
        $label = $requisite->scopeLabel();

        return $label === 'All programs and majors' ? null : $label;
    }

    /**
    * @param  Collection<int, Subject>  $subjects
    * @param  Collection<int, array{id:string,labels:array<int, string>,majorIds:array<int, int>,scopeLabels:array<int, string>,sourceId:int,targetId:int,type:string}>  $edges
     * @param  Collection<int, array<string, mixed>>  $components
    * @param  Collection<int, string>  $gradeLabels
     * @return array{canvasHeight:int,canvasWidth:int,edgeCount:int,edges:array<int, array<string, mixed>>,goal:array<string, mixed>|null,nodes:array<int, array<string, mixed>>}
     */
    private function renderGraph(Course $course, ?Major $major, Collection $subjects, Collection $edges, Collection $components, Collection $gradeLabels): array
    {
        if ($subjects->isEmpty()) {
            return [
                'canvasHeight' => 0,
                'canvasWidth' => 0,
                'edgeCount' => 0,
                'edges' => [],
                'goal' => null,
                'nodes' => [],
            ];
        }

        $nodes = [];
        $renderedEdges = [];
        $componentOffsets = [];
        $topOffset = self::PADDING_Y;
        $maxComponentWidth = 0;
        $trackPalette = $this->trackPalette($course);

        foreach ($components as $component) {
            $componentOffsets[$component['id']] = $topOffset;
            $maxComponentWidth = max($maxComponentWidth, (int) $component['canvasWidth']);

            foreach ($component['nodes'] as $node) {
                $nodes[] = [
                    ...$node,
                    'gradeLabel' => $gradeLabels->get($node['id'], ''),
                    'type' => 'subject',
                    'y' => $node['y'] + $topOffset,
                ];
            }

            foreach ($component['edges'] as $edge) {
                $displayClasses = $this->edgeDisplayClasses($edge['majorIds'], $trackPalette);

                $renderedEdges[] = [
                    ...$edge,
                    'chipClasses' => $displayClasses['chip'],
                    'labelY' => $edge['labelY'] + $topOffset,
                    'lineClasses' => $displayClasses['line'],
                    'y1' => $edge['y1'] + $topOffset,
                    'y2' => $edge['y2'] + $topOffset,
                ];
            }

            $topOffset += (int) $component['canvasHeight'] + self::COMPONENT_GAP;
        }

        $graphHeight = max($topOffset - self::COMPONENT_GAP + self::PADDING_Y, self::GOAL_NODE_HEIGHT + (self::PADDING_Y * 2));
        $goalX = $maxComponentWidth + self::GOAL_X_GAP;
        $goalY = (int) floor(($graphHeight - self::GOAL_NODE_HEIGHT) / 2);
        $goalId = 'graduation-goal';
        $goal = [
            'caption' => $major !== null
                ? 'Major in '.$major->name
                : $this->goalCaption($course),
            'iconLabel' => 'Graduation Cap',
            'id' => $goalId,
            'title' => $course->name,
            'type' => 'goal',
            'x' => $goalX,
            'y' => $goalY,
        ];

        $nodeLayouts = collect($nodes)->keyBy('id');
        $terminalSubjectIds = $this->terminalSubjectIds($subjects, $edges);

        foreach ($terminalSubjectIds as $subjectId) {
            $subjectNode = $nodeLayouts->get($subjectId);

            if ($subjectNode === null) {
                continue;
            }

            $lineStartX = $subjectNode['x'] + self::NODE_WIDTH;
            $lineEndX = $goalX;
            $lineStartY = $subjectNode['y'] + (self::NODE_HEIGHT / 2);
            $lineEndY = $goalY + (self::GOAL_NODE_HEIGHT / 2);

            $renderedEdges[] = [
                'chipClasses' => '',
                'id' => 'goal-edge-'.$subjectId,
                'labelX' => ($lineStartX + $lineEndX) / 2,
                'labelY' => (($lineStartY + $lineEndY) / 2) - 14,
                'lineClasses' => 'text-emerald-500 stroke-emerald-500 dark:text-emerald-400 dark:stroke-emerald-400',
                'majorIds' => [],
                'scopeLabel' => '',
                'type' => 'goal',
                'x1' => $lineStartX,
                'x2' => $lineEndX,
                'y1' => $lineStartY,
                'y2' => $lineEndY,
            ];
        }

        $canvasWidth = $goalX + self::GOAL_NODE_WIDTH + self::PADDING_X;

        return [
            'canvasHeight' => $graphHeight,
            'canvasWidth' => $canvasWidth,
            'edgeCount' => $edges->count(),
            'edges' => $renderedEdges,
            'goal' => $goal,
            'nodes' => $nodes,
        ];
    }

    private function goalCaption(Course $course): string
    {
        $trackLabels = $course->majors
            ->pluck('name')
            ->filter()
            ->map(fn (string $name) => 'Major in '.$name)
            ->values();

        if ($trackLabels->isEmpty()) {
            return 'General program completion';
        }

        return 'Tracks: '.$trackLabels->implode(' | ');
    }

    /**
     * @return array<int, array{chip:string,line:string}>
     */
    private function trackPalette(Course $course): array
    {
        $palette = [];

        foreach ($course->majors->sortBy('id')->values() as $index => $major) {
            $palette[(int) $major->id] = self::TRACK_PALETTE[$index % count(self::TRACK_PALETTE)];
        }

        return $palette;
    }

    /**
     * @param  array<int, int>  $majorIds
     * @param  array<int, array{chip:string,line:string}>  $trackPalette
     * @return array{chip:string,line:string}
     */
    private function edgeDisplayClasses(array $majorIds, array $trackPalette): array
    {
        if (count($majorIds) !== 1) {
            return [
                'chip' => 'border-zinc-200 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300',
                'line' => 'text-zinc-400 stroke-zinc-400 dark:text-zinc-600 dark:stroke-zinc-600',
            ];
        }

        return $trackPalette[array_values($majorIds)[0]] ?? [
            'chip' => 'border-zinc-200 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300',
            'line' => 'text-zinc-400 stroke-zinc-400 dark:text-zinc-600 dark:stroke-zinc-600',
        ];
    }

    /**
    * @param  Collection<int, Subject>  $subjects
    * @param  Collection<int, array{id:string,labels:array<int, string>,majorIds:array<int, int>,scopeLabels:array<int, string>,sourceId:int,targetId:int,type:string}>  $edges
     * @return Collection<int, int>
     */
    private function terminalSubjectIds(Collection $subjects, Collection $edges): Collection
    {
        $outgoingCounts = [];

        foreach ($edges->where('type', 'prerequisite') as $edge) {
            $outgoingCounts[$edge['sourceId']] = ($outgoingCounts[$edge['sourceId']] ?? 0) + 1;
        }

        return $subjects
            ->keys()
            ->map(fn (int|string $subjectId) => (int) $subjectId)
            ->filter(fn (int $subjectId) => ($outgoingCounts[$subjectId] ?? 0) === 0)
            ->values();
    }

    /**
    * @param  Collection<int, Subject>  $subjects
    * @param  Collection<int, array{id:string,labels:array<int, string>,majorIds:array<int, int>,scopeLabels:array<int, string>,sourceId:int,targetId:int,type:string}>  $edges
     * @return Collection<int, array<string, mixed>>
     */
    private function buildComponents(Collection $subjects, Collection $edges): Collection
    {
        $adjacency = [];

        foreach ($edges as $edge) {
            $adjacency[$edge['sourceId']] ??= [];
            $adjacency[$edge['targetId']] ??= [];
            $adjacency[$edge['sourceId']][] = $edge['targetId'];
            $adjacency[$edge['targetId']][] = $edge['sourceId'];
        }

        $visited = [];
        $components = collect();

        foreach ($subjects->sortBy(fn (Subject $subject) => $subject->subject_code) as $subject) {
            $subjectId = (int) $subject->id;

            if (isset($visited[$subjectId])) {
                continue;
            }

            $queue = [$subjectId];
            $componentSubjectIds = [];
            $visited[$subjectId] = true;

            while ($queue !== []) {
                $currentSubjectId = (int) array_shift($queue);
                $componentSubjectIds[] = $currentSubjectId;

                foreach ($adjacency[$currentSubjectId] ?? [] as $adjacentSubjectId) {
                    $adjacentSubjectId = (int) $adjacentSubjectId;

                    if (isset($visited[$adjacentSubjectId])) {
                        continue;
                    }

                    $visited[$adjacentSubjectId] = true;
                    $queue[] = $adjacentSubjectId;
                }
            }

            $componentSubjects = collect($componentSubjectIds)
                ->mapWithKeys(fn (int $currentSubjectId) => [$currentSubjectId => $subjects->get($currentSubjectId)])
                ->filter();
            $componentEdges = $edges
                ->filter(fn (array $edge) => $componentSubjects->has($edge['sourceId']) && $componentSubjects->has($edge['targetId']))
                ->values();

            $components->push($this->buildComponent($components->count() + 1, $componentSubjects, $componentEdges));
        }

        return $components;
    }

    /**
    * @param  Collection<int, Subject>  $subjects
    * @param  Collection<int, array{id:string,labels:array<int, string>,majorIds:array<int, int>,scopeLabels:array<int, string>,sourceId:int,targetId:int,type:string}>  $edges
     * @return array<string, mixed>
     */
    private function buildComponent(int $componentNumber, Collection $subjects, Collection $edges): array
    {
        $levels = $this->levels($subjects, $edges);
        $rowScores = $this->rowScores($subjects, $edges);
        $nodeLayouts = collect();
        $incomingCounts = $this->incomingCounts($edges);
        $maxRows = 1;
        $maxLevel = max($levels->all() ?: [0]);

        foreach (range(0, $maxLevel) as $level) {
            $levelSubjects = $subjects
                ->filter(fn (Subject $subject) => $levels->get((int) $subject->id, 0) === $level)
                ->sortBy([
                    fn (Subject $subject) => $rowScores->get((int) $subject->id, 0.0),
                    fn (Subject $subject) => $subject->subject_code,
                ])
                ->values();

            $maxRows = max($maxRows, $levelSubjects->count());

            foreach ($levelSubjects as $rank => $subject) {
                $subjectId = (int) $subject->id;
                $x = self::PADDING_X + ($level * (self::NODE_WIDTH + self::COLUMN_GAP));
                $y = self::PADDING_Y + ($rank * (self::NODE_HEIGHT + self::ROW_GAP));

                $nodeLayouts->put($subjectId, [
                    'departmentLabel' => $subject->department->abbreviation ?: $subject->department->name,
                    'id' => $subjectId,
                    'title' => $subject->subject_title,
                    'unitsLabel' => rtrim(rtrim(number_format((float) $subject->credit_units, 2, '.', ''), '0'), '.'),
                    'x' => $x,
                    'y' => $y,
                    'subjectCode' => $subject->subject_code,
                ]);
            }
        }

        $canvasWidth = self::PADDING_X * 2 + (($maxLevel + 1) * self::NODE_WIDTH) + ($maxLevel * self::COLUMN_GAP);
        $canvasHeight = self::PADDING_Y * 2 + ($maxRows * self::NODE_HEIGHT) + (max($maxRows - 1, 0) * self::ROW_GAP);
        $componentId = 'component-'.$componentNumber;
        $rootSubjects = $subjects
            ->filter(fn (Subject $subject) => ($incomingCounts[(int) $subject->id] ?? 0) === 0)
            ->sortBy(fn (Subject $subject) => $subject->subject_code)
            ->values();

        if ($rootSubjects->isEmpty()) {
            $rootSubjects = $subjects->sortBy(fn (Subject $subject) => $subject->subject_code)->values();
        }

        return [
            'canvasHeight' => $canvasHeight,
            'canvasWidth' => $canvasWidth,
            'edgeCount' => $edges->count(),
            'edges' => $this->renderEdges($edges, $nodeLayouts),
            'id' => $componentId,
            'nodeCount' => $subjects->count(),
            'nodes' => $nodeLayouts->values()->all(),
            'rootLabel' => $rootSubjects->take(3)->pluck('subject_code')->implode(', '),
            'title' => 'Path '.$componentNumber,
        ];
    }

    /**
    * @param  Collection<int, Subject>  $subjects
    * @param  Collection<int, array{id:string,labels:array<int, string>,majorIds:array<int, int>,scopeLabels:array<int, string>,sourceId:int,targetId:int,type:string}>  $edges
     * @return Collection<int, int>
     */
    private function levels(Collection $subjects, Collection $edges): Collection
    {
        $levels = $subjects->mapWithKeys(fn (Subject $subject) => [(int) $subject->id => 0]);

        for ($iteration = 0; $iteration < 50; $iteration++) {
            $changed = false;

            foreach ($edges->where('type', 'prerequisite') as $edge) {
                $candidateLevel = $levels->get($edge['sourceId'], 0) + 1;

                if ($candidateLevel > $levels->get($edge['targetId'], 0)) {
                    $levels->put($edge['targetId'], $candidateLevel);
                    $changed = true;
                }
            }

            foreach ($edges->where('type', 'corequisite') as $edge) {
                $candidateLevel = max($levels->get($edge['sourceId'], 0), $levels->get($edge['targetId'], 0));

                if ($candidateLevel !== $levels->get($edge['sourceId'], 0) || $candidateLevel !== $levels->get($edge['targetId'], 0)) {
                    $levels->put($edge['sourceId'], $candidateLevel);
                    $levels->put($edge['targetId'], $candidateLevel);
                    $changed = true;
                }
            }

            if (! $changed) {
                break;
            }
        }

        return $levels;
    }

    /**
    * @param  Collection<int, Subject>  $subjects
    * @param  Collection<int, array{id:string,labels:array<int, string>,majorIds:array<int, int>,scopeLabels:array<int, string>,sourceId:int,targetId:int,type:string}>  $edges
     * @return Collection<int, float>
     */
    private function rowScores(Collection $subjects, Collection $edges): Collection
    {
        $adjacency = [];
        $incomingCounts = $this->incomingCounts($edges);

        foreach ($edges->where('type', 'prerequisite') as $edge) {
            $adjacency[$edge['sourceId']] ??= [];
            $adjacency[$edge['sourceId']][] = $edge['targetId'];
        }

        foreach ($adjacency as $subjectId => $childIds) {
            usort($childIds, fn (int $leftId, int $rightId) => strcmp(
                $subjects->get($leftId)?->subject_code ?? '',
                $subjects->get($rightId)?->subject_code ?? '',
            ));

            $adjacency[$subjectId] = $childIds;
        }

        $rootSubjectIds = $subjects
            ->filter(fn (Subject $subject) => ($incomingCounts[(int) $subject->id] ?? 0) === 0)
            ->sortBy(fn (Subject $subject) => $subject->subject_code)
            ->keys()
            ->map(fn (int|string $subjectId) => (int) $subjectId)
            ->values();

        if ($rootSubjectIds->isEmpty()) {
            $rootSubjectIds = $subjects
                ->sortBy(fn (Subject $subject) => $subject->subject_code)
                ->keys()
                ->map(fn (int|string $subjectId) => (int) $subjectId)
                ->values();
        }

        $paths = [];

        foreach ($rootSubjectIds as $rootSubjectId) {
            $this->collectPaths($rootSubjectId, $adjacency, $paths, []);
        }

        if ($paths === []) {
            $paths = $subjects
                ->sortBy(fn (Subject $subject) => $subject->subject_code)
                ->keys()
                ->map(fn (int|string $subjectId) => [(int) $subjectId])
                ->all();
        }

        $scoreBuckets = [];

        foreach ($paths as $pathIndex => $path) {
            foreach (array_unique($path) as $subjectId) {
                $scoreBuckets[$subjectId] ??= [];
                $scoreBuckets[$subjectId][] = $pathIndex;
            }
        }

        $rowScores = collect($scoreBuckets)
            ->map(fn (array $scoreBucket) => array_sum($scoreBucket) / max(count($scoreBucket), 1));

        foreach ($subjects->keys() as $subjectId) {
            $subjectId = (int) $subjectId;

            if (! $rowScores->has($subjectId)) {
                $rowScores->put($subjectId, (float) $rowScores->count());
            }
        }

        for ($iteration = 0; $iteration < 10; $iteration++) {
            foreach ($edges->where('type', 'corequisite') as $edge) {
                $averageScore = ($rowScores->get($edge['sourceId'], 0.0) + $rowScores->get($edge['targetId'], 0.0)) / 2;
                $rowScores->put($edge['sourceId'], $averageScore);
                $rowScores->put($edge['targetId'], $averageScore);
            }
        }

        return $rowScores;
    }

    /**
     * @param  array<int, array<int, int>>  $adjacency
     * @param  array<int, array<int, int>>  $paths
     * @param  array<int, int>  $path
     */
    private function collectPaths(int $subjectId, array $adjacency, array &$paths, array $path, array $visiting = []): void
    {
        if (in_array($subjectId, $visiting, true)) {
            $paths[] = $path;

            return;
        }

        $path[] = $subjectId;
        $childIds = $adjacency[$subjectId] ?? [];

        if ($childIds === []) {
            $paths[] = $path;

            return;
        }

        $visiting[] = $subjectId;

        foreach ($childIds as $childId) {
            if (count($paths) > 1000) {
                return;
            }

            $this->collectPaths($childId, $adjacency, $paths, $path, $visiting);
        }
    }

    /**
    * @param  Collection<int, array{id:string,labels:array<int, string>,majorIds:array<int, int>,scopeLabels:array<int, string>,sourceId:int,targetId:int,type:string}>  $edges
     * @return array<int, int>
     */
    private function incomingCounts(Collection $edges): array
    {
        $incomingCounts = [];

        foreach ($edges->where('type', 'prerequisite') as $edge) {
            $incomingCounts[$edge['targetId']] = ($incomingCounts[$edge['targetId']] ?? 0) + 1;
        }

        return $incomingCounts;
    }

    /**
    * @param  Collection<int, array{id:string,labels:array<int, string>,majorIds:array<int, int>,scopeLabels:array<int, string>,sourceId:int,targetId:int,type:string}>  $edges
     * @param  Collection<int, array<string, mixed>>  $nodeLayouts
     * @return array<int, array<string, mixed>>
     */
    private function renderEdges(Collection $edges, Collection $nodeLayouts): array
    {
        return $edges
            ->map(function (array $edge) use ($nodeLayouts): array {
                $sourceNode = $nodeLayouts->get($edge['sourceId']);
                $targetNode = $nodeLayouts->get($edge['targetId']);

                if ($sourceNode === null || $targetNode === null) {
                    return [];
                }

                $isSourceBeforeTarget = $sourceNode['x'] <= $targetNode['x'];
                $lineStartX = $isSourceBeforeTarget
                    ? $sourceNode['x'] + self::NODE_WIDTH
                    : $sourceNode['x'];
                $lineEndX = $isSourceBeforeTarget
                    ? $targetNode['x']
                    : $targetNode['x'] + self::NODE_WIDTH;

                if ($edge['type'] === 'prerequisite') {
                    $lineStartX = $sourceNode['x'] + self::NODE_WIDTH;
                    $lineEndX = $targetNode['x'];
                }

                $lineStartY = $sourceNode['y'] + (self::NODE_HEIGHT / 2);
                $lineEndY = $targetNode['y'] + (self::NODE_HEIGHT / 2);

                return [
                    'id' => $edge['id'],
                    'labelX' => ($lineStartX + $lineEndX) / 2,
                    'labelY' => (($lineStartY + $lineEndY) / 2) - 14,
                    'majorIds' => $edge['majorIds'],
                    'scopeLabel' => implode(' / ', $edge['scopeLabels']),
                    'type' => $edge['type'],
                    'x1' => $lineStartX,
                    'x2' => $lineEndX,
                    'y1' => $lineStartY,
                    'y2' => $lineEndY,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
