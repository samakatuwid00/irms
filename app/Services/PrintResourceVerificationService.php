<?php

namespace App\Services;

use App\Models\PrintResource;
use App\Models\PrintResourceVerificationLog;
use App\Models\SubjectGradeLevel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PrintResourceVerificationService
{
    public function snapshot(PrintResource $resource): array
    {
        $resource->loadMissing(['printTitle.authors', 'type']);

        return [
            'title' => $resource->printTitle?->title,
            'authors' => $resource->printTitle?->authors?->pluck('author_name')->values()->all() ?? [],
            'type_id' => $resource->print_type_id,
            'type' => $resource->type?->type_name,
            'publisher' => $resource->publisher,
            'volume' => $resource->volume,
            'edition' => $resource->edition,
            'copyright' => $resource->copyright,
            'pages' => $resource->pages,
            'isbn' => $resource->isbn,
            'subject_grade_level_ids' => $resource->subject_grade_level_ids
                ? array_values(array_filter(array_map('trim', explode(',', $resource->subject_grade_level_ids))))
                : [],
            'subjects' => $this->formatSubjects($resource),
            'cover' => $resource->cover,
            'verified' => (bool) $resource->verified,
            'verified_by' => $resource->verified_by,
            'verified_at' => $resource->verified_at?->toDateTimeString(),
        ];
    }

    public function log(
        PrintResource $resource,
        User $user,
        string $actionType,
        ?string $comment,
        ?array $previousMetadata,
        ?array $newMetadata
    ): PrintResourceVerificationLog {
        return PrintResourceVerificationLog::create([
            'id' => (string) Str::uuid(),
            'print_resource_id' => $resource->id,
            'user_id' => $user->id,
            'user_level' => $user->userType?->level,
            'user_role' => $user->userType?->type_name,
            'action_type' => $actionType,
            'comment' => $comment,
            'previous_metadata' => $previousMetadata,
            'new_metadata' => $newMetadata,
            'created_at' => now(),
        ]);
    }

    public function formatHistory(PrintResource $resource): array
    {
        $resource->loadMissing(['verifiedBy.userType', 'verificationLogs.user.userType']);

        $history = $resource->verificationLogs
            ->sortBy('created_at')
            ->map(fn (PrintResourceVerificationLog $log) => $this->formatLog($log, $resource))
            ->values();

        if ($resource->verified && $resource->verifiedBy && ! $this->hasFirstVerification($history)) {
            $history->prepend([
                'name' => $this->userName($resource->verifiedBy),
                'role' => $resource->verifiedBy->userType?->type_name ?? 'User',
                'level' => $resource->verifiedBy->userType?->level,
                'action_type' => 'first_verification',
                'action_label' => $this->actionLabel('first_verification'),
                'comment' => null,
                'created_at' => $resource->verified_at?->format('M d, Y h:i A') ?? '-',
            ]);
        }

        return $history->values()->all();
    }

    private function formatLog(PrintResourceVerificationLog $log, PrintResource $resource): array
    {
        $actionType = $log->action_type;
        if ($actionType === 'edit_after_verification' && $log->user_id === $resource->verified_by) {
            $actionType = 'first_verifier_update';
        }

        return [
            'name' => $log->user ? $this->userName($log->user) : 'Unknown user',
            'role' => $log->user_role ?? $log->user?->userType?->type_name ?? 'User',
            'level' => $log->user_level ?? $log->user?->userType?->level,
            'action_type' => $actionType,
            'action_label' => $this->actionLabel($actionType),
            'comment' => $log->comment,
            'created_at' => $log->created_at?->format('M d, Y h:i A') ?? '-',
        ];
    }

    private function hasFirstVerification(Collection $history): bool
    {
        return $history->contains(fn (array $item) => $item['action_type'] === 'first_verification');
    }

    private function actionLabel(string $actionType): string
    {
        return match ($actionType) {
            'first_verification' => 'First Verification',
            'edit_after_verification' => 'Edit After Verification',
            'first_verifier_update' => 'First Verifier Updated Verified LR',
            're_verification' => 'Re-verification',
            default => Str::headline($actionType),
        };
    }

    private function userName(User $user): string
    {
        return trim(implode(' ', array_filter([
            $user->firstname,
            $user->middlename,
            $user->lastname,
            $user->extension_name,
        ])));
    }

    private function formatSubjects(PrintResource $resource): array
    {
        if (! $resource->subject_grade_level_ids) {
            return [];
        }

        $ids = array_values(array_filter(array_map('trim', explode(',', $resource->subject_grade_level_ids))));

        return SubjectGradeLevel::with(['subject', 'gradeLevel'])
            ->whereIn('id', $ids)
            ->get()
            ->map(fn ($sgl) => [
                'id' => $sgl->id,
                'subject' => $sgl->subject->subject_name ?? null,
                'grade' => $sgl->gradeLevel->grade ?? null,
            ])
            ->values()
            ->all();
    }
}
