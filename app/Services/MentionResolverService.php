<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MentionResolverService
{
    /**
     * @return Collection<int, User>
     */
    public function resolveUsers(string $text, Project $project, ?int $authorId = null): Collection
    {
        preg_match_all('/@([a-zA-Z0-9._-]{2,60})/', $text, $matches);
        $handles = collect($matches[1] ?? [])
            ->map(fn (string $handle): string => strtolower(trim($handle)))
            ->filter()
            ->unique()
            ->values();

        if ($handles->isEmpty()) {
            return collect();
        }

        $members = $project->members()
            ->wherePivot('is_active', true)
            ->get();

        if ($project->owner && ! $members->contains('id', $project->owner->id)) {
            $members->push($project->owner);
        }

        return $members
            ->filter(function (User $user) use ($handles, $authorId): bool {
                if ($authorId && $authorId === $user->id) {
                    return false;
                }

                $candidates = $this->buildHandlesForUser($user);

                return $handles->intersect($candidates)->isNotEmpty();
            })
            ->unique('id')
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function buildHandlesForUser(User $user): Collection
    {
        $nameRaw = Str::lower($user->name ?? '');
        $nameCompact = Str::of($nameRaw)
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
        $nameDashed = Str::of($nameRaw)
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->toString();
        $emailLocal = Str::before(Str::lower($user->email ?? ''), '@');

        return collect([
            $nameCompact,
            $nameDashed,
            $emailLocal,
        ])->filter()->unique()->values();
    }
}

