<?php

namespace App\Support;

use App\Models\Prospectus;

class ProspectusPrerequisiteChecker
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function check(Prospectus $prospectus): array
    {
        $prospectus->loadMissing('terms.subjects.requisites.requisiteSubject');

        $subjectPlacements = [];

        foreach ($prospectus->terms as $term) {
            foreach ($term->subjects as $subject) {
                $placementOrder = $this->placementOrder($term->year_level, $term->term_name);

                if (! isset($subjectPlacements[$subject->id]) || $placementOrder < $subjectPlacements[$subject->id]['order']) {
                    $subjectPlacements[$subject->id] = [
                        'order' => $placementOrder,
                        'label' => 'Year '.$term->year_level.' - '.$term->term_name,
                    ];
                }
            }
        }

        $issues = [];

        foreach ($prospectus->terms as $term) {
            $currentPlacementOrder = $this->placementOrder($term->year_level, $term->term_name);

            foreach ($term->subjects as $subject) {
                foreach ($subject->applicableRequisites($prospectus->course_id, $prospectus->major_id, 'prerequisite') as $requisite) {
                    $requiredPlacement = $subjectPlacements[$requisite->requisite_subject_id] ?? null;

                    if ($requiredPlacement === null) {
                        $issues[] = [
                            'subject' => $subject->subject_code.' - '.$subject->subject_title,
                            'required_subject' => $requisite->requisiteSubject->subject_code.' - '.$requisite->requisiteSubject->subject_title,
                            'message' => $subject->subject_code.' requires '.$requisite->requisiteSubject->subject_code.' as a prerequisite, but that prerequisite is not placed in this semestral distribution.',
                            'placement' => 'Year '.$term->year_level.' - '.$term->term_name,
                        ];

                        continue;
                    }

                    if ($requiredPlacement['order'] >= $currentPlacementOrder) {
                        $issues[] = [
                            'subject' => $subject->subject_code.' - '.$subject->subject_title,
                            'required_subject' => $requisite->requisiteSubject->subject_code.' - '.$requisite->requisiteSubject->subject_title,
                            'message' => $subject->subject_code.' requires '.$requisite->requisiteSubject->subject_code.', but '.$requisite->requisiteSubject->subject_code.' is scheduled in '.$requiredPlacement['label'].' instead of an earlier term.',
                            'placement' => 'Year '.$term->year_level.' - '.$term->term_name,
                        ];
                    }
                }
            }
        }

        return collect($issues)
            ->unique(fn (array $issue) => $issue['subject'].'|'.$issue['required_subject'].'|'.$issue['placement'])
            ->values()
            ->all();
    }

    private function placementOrder(int $yearLevel, string $termName): int
    {
        return ($yearLevel * 10) + match ($termName) {
            '1st Semester' => 1,
            '2nd Semester' => 2,
            'Summer Term' => 3,
            default => 9,
        };
    }
}
