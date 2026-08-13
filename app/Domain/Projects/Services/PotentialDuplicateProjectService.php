<?php

namespace App\Domain\Projects\Services;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PotentialDuplicateProjectService
{
    /**
     * @return array<int, array{
     *     potential_duplicate: bool,
     *     duplicate_group: string|null,
     *     duplicate_group_size: int,
     *     duplicate_match_reasons: list<string>
     * }>
     */
    public function analyze(Collection $projects): array
    {
        $projects = $projects->values();
        $parents = $projects->isEmpty() ? [] : range(0, $projects->count() - 1);
        $candidateIndexes = [];
        $titleIndexes = [];

        foreach ($projects as $index => $project) {
            $candidate = $this->normalize($project->candidate_name);
            $title = $this->normalize($project->title);

            if ($candidate !== '') {
                $candidateIndexes[$candidate][] = $index;
            }

            if ($title !== '') {
                $titleIndexes[$title][] = $index;
            }
        }

        foreach ([$candidateIndexes, $titleIndexes] as $indexes) {
            foreach ($indexes as $matchingIndexes) {
                foreach (array_slice($matchingIndexes, 1) as $index) {
                    $this->union($parents, $matchingIndexes[0], $index);
                }
            }
        }

        $groups = [];
        foreach ($projects as $index => $project) {
            $groups[$this->findRoot($parents, $index)][] = $project;
        }

        $duplicates = [];
        $groupNumber = 1;
        foreach ($groups as $group) {
            $groupSize = count($group);
            if ($groupSize < 2) {
                continue;
            }

            $groupName = 'DUP-'.str_pad((string) $groupNumber++, 3, '0', STR_PAD_LEFT);
            foreach ($group as $project) {
                $reasons = [];
                $candidate = $this->normalize($project->candidate_name);
                $title = $this->normalize($project->title);

                if ($candidate !== '' && collect($group)->contains(
                    fn (Project $other): bool => $other->id !== $project->id
                        && $this->normalize($other->candidate_name) === $candidate
                )) {
                    $reasons[] = 'nome do candidato';
                }

                if ($title !== '' && collect($group)->contains(
                    fn (Project $other): bool => $other->id !== $project->id
                        && $this->normalize($other->title) === $title
                )) {
                    $reasons[] = 'título do projeto';
                }

                $duplicates[$project->id] = [
                    'potential_duplicate' => true,
                    'duplicate_group' => $groupName,
                    'duplicate_group_size' => $groupSize,
                    'duplicate_match_reasons' => $reasons,
                ];
            }
        }

        foreach ($projects as $project) {
            $duplicates[$project->id] ??= [
                'potential_duplicate' => false,
                'duplicate_group' => null,
                'duplicate_group_size' => 1,
                'duplicate_match_reasons' => [],
            ];
        }

        return $duplicates;
    }

    private function normalize(?string $value): string
    {
        return (string) Str::of($value ?? '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }

    private function findRoot(array &$parents, int $index): int
    {
        if ($parents[$index] !== $index) {
            $parents[$index] = $this->findRoot($parents, $parents[$index]);
        }

        return $parents[$index];
    }

    private function union(array &$parents, int $left, int $right): void
    {
        $leftRoot = $this->findRoot($parents, $left);
        $rightRoot = $this->findRoot($parents, $right);

        if ($leftRoot !== $rightRoot) {
            $parents[$rightRoot] = $leftRoot;
        }
    }
}
