<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Normalize key stages and align the subject/grade matrix with the
     * curriculum matrix supplied for Kindergarten through Grade 12.
     */
    public function up(): void
    {
        $this->createKeyStagesTable();

        $keyStageIds = $this->seedKeyStages();
        $gradeLevelIds = $this->alignGradeLevels($keyStageIds);

        $this->prepareSubjectGradeLevelsTable();

        $subjectIds = $this->alignSubjects();
        $keptMappingIds = $this->alignSubjectGradeMappings($gradeLevelIds, $subjectIds);

        $this->removeObsoleteMappings($keptMappingIds);
        $this->removeObsoleteLegacySubjects($subjectIds);
        $this->finishSubjectGradeLevelsTable();
    }

    /**
     * This rollback restores the legacy schema shape. The curated subject
     * rows and mappings are intentionally retained so rolling back cannot
     * silently detach learning resources from their subject-grade UUIDs.
     */
    public function down(): void
    {
        if ($this->hasIndex('subject_grade_levels', 'subject_grade_level_unique')) {
            Schema::table('subject_grade_levels', function (Blueprint $table) {
                $table->dropUnique('subject_grade_level_unique');
            });
        }

        if (! Schema::hasColumn('subject_grade_levels', 'key_stage')) {
            Schema::table('subject_grade_levels', function (Blueprint $table) {
                $table->string('key_stage')->nullable();
            });
        }

        foreach ($this->gradeDefinitions() as $grade => $definition) {
            DB::table('subject_grade_levels')
                ->whereIn('grade_level_id', DB::table('grade_levels')->select('id')->where('grade', $grade))
                ->update(['key_stage' => $definition['legacy_stage']]);
        }

        $this->repointKeyStageInReportingViews(false);

        if (Schema::hasColumn('subject_grade_levels', 'resource_ratio')) {
            Schema::table('subject_grade_levels', function (Blueprint $table) {
                $table->dropColumn('resource_ratio');
            });
        }

        if ($this->hasForeign('grade_levels', 'grade_levels_key_stage_id_foreign')) {
            Schema::table('grade_levels', function (Blueprint $table) {
                $table->dropForeign('grade_levels_key_stage_id_foreign');
            });
        }

        if ($this->hasIndex('grade_levels', 'grade_levels_key_stage_id_index')) {
            Schema::table('grade_levels', function (Blueprint $table) {
                $table->dropIndex('grade_levels_key_stage_id_index');
            });
        }

        if (Schema::hasColumn('grade_levels', 'key_stage_id')) {
            Schema::table('grade_levels', function (Blueprint $table) {
                $table->dropColumn('key_stage_id');
            });
        }

        Schema::dropIfExists('key_stages');

        if (! $this->hasIndex('subject_grade_levels', 'subject_grade_unique')) {
            Schema::table('subject_grade_levels', function (Blueprint $table) {
                $table->unique(
                    ['subject_id', 'grade_level_id', 'curriculum_id', 'key_stage'],
                    'subject_grade_unique'
                );
            });
        }
    }

    private function createKeyStagesTable(): void
    {
        if (! Schema::hasTable('key_stages')) {
            Schema::create('key_stages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 10)->unique();
                $table->string('name')->unique();
                $table->unsignedTinyInteger('sort_order')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('grade_levels', 'key_stage_id')) {
            Schema::table('grade_levels', function (Blueprint $table) {
                $table->uuid('key_stage_id')->nullable();
            });
        }
    }

    /**
     * @return array<string, string>
     */
    private function seedKeyStages(): array
    {
        $ids = [];
        $now = now();

        foreach ($this->keyStageDefinitions() as $code => $definition) {
            $existing = DB::table('key_stages')->where('code', $code)->first();
            $id = $existing?->id ?? (string) Str::uuid();

            if ($existing) {
                DB::table('key_stages')->where('id', $id)->update([
                    'name' => $definition['name'],
                    'sort_order' => $definition['sort_order'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('key_stages')->insert([
                    'id' => $id,
                    'code' => $code,
                    'name' => $definition['name'],
                    'sort_order' => $definition['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $ids[$code] = $id;
        }

        return $ids;
    }

    /**
     * @param  array<string, string>  $keyStageIds
     * @return array<string, string>
     */
    private function alignGradeLevels(array $keyStageIds): array
    {
        $ids = [];
        $now = now();

        foreach ($this->gradeDefinitions() as $grade => $definition) {
            $existing = DB::table('grade_levels')->where('grade', $grade)->first();
            $id = $existing?->id ?? (string) Str::uuid();

            $values = [
                'grade' => $grade,
                'classification' => $existing?->classification ?: $definition['classification'],
                'sort_order' => $definition['sort_order'],
                'key_stage_id' => $keyStageIds[$definition['key_stage']],
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('grade_levels')->where('id', $id)->update($values);
            } else {
                DB::table('grade_levels')->insert($values + [
                    'id' => $id,
                    'created_at' => $now,
                ]);
            }

            $ids[$grade] = $id;
        }

        if (! $this->hasIndex('grade_levels', 'grade_levels_key_stage_id_index')) {
            Schema::table('grade_levels', function (Blueprint $table) {
                $table->index('key_stage_id', 'grade_levels_key_stage_id_index');
            });
        }

        if (! $this->hasForeign('grade_levels', 'grade_levels_key_stage_id_foreign')) {
            Schema::table('grade_levels', function (Blueprint $table) {
                $table->foreign('key_stage_id', 'grade_levels_key_stage_id_foreign')
                    ->references('id')
                    ->on('key_stages')
                    ->restrictOnDelete();
            });
        }

        return $ids;
    }

    private function prepareSubjectGradeLevelsTable(): void
    {
        if ($this->hasIndex('subject_grade_levels', 'subject_grade_unique')) {
            Schema::table('subject_grade_levels', function (Blueprint $table) {
                $table->dropUnique('subject_grade_unique');
            });
        }

        if (! $this->columnIsNullable('subject_grade_levels', 'curriculum_id')) {
            Schema::table('subject_grade_levels', function (Blueprint $table) {
                $table->uuid('curriculum_id')->nullable()->change();
            });
        }

        if (! Schema::hasColumn('subject_grade_levels', 'resource_ratio')) {
            Schema::table('subject_grade_levels', function (Blueprint $table) {
                $table->string('resource_ratio', 5)->nullable();
            });
        }
    }

    /**
     * Reuse the three legacy subject UUIDs whose labels changed. This keeps
     * existing resource associations stable while correcting their names.
     *
     * @return array<string, string>
     */
    private function alignSubjects(): array
    {
        $ids = [];
        $now = now();

        foreach ($this->subjectDefinitions() as $name => $definition) {
            $subject = DB::table('subjects')->where('subject_name', $name)->first();

            if (! $subject) {
                foreach ($definition['legacy_names'] as $legacyName) {
                    $subject = DB::table('subjects')->where('subject_name', $legacyName)->first();

                    if ($subject) {
                        break;
                    }
                }
            }

            $id = $subject?->id ?? (string) Str::uuid();

            if ($subject) {
                DB::table('subjects')->where('id', $id)->update([
                    'subject_name' => $name,
                    'abbrv' => $definition['abbrv'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('subjects')->insert([
                    'id' => $id,
                    'subject_name' => $name,
                    'abbrv' => $definition['abbrv'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $ids[$name] = $id;
        }

        return $ids;
    }

    /**
     * @param  array<string, string>  $gradeLevelIds
     * @param  array<string, string>  $subjectIds
     * @return array<int, string>
     */
    private function alignSubjectGradeMappings(array $gradeLevelIds, array $subjectIds): array
    {
        $keptIds = [];
        $now = now();
        $hasLegacyKeyStage = Schema::hasColumn('subject_grade_levels', 'key_stage');
        $hasCurriculum = Schema::hasColumn('subject_grade_levels', 'curriculum_id');

        foreach ($this->subjectGradeMatrix() as $grade => $subjects) {
            $gradeDefinition = $this->gradeDefinitions()[$grade];
            $stageName = $this->keyStageDefinitions()[$gradeDefinition['key_stage']]['name'];

            foreach ($subjects as $targetSubject => $mapping) {
                $targetSubjectId = $subjectIds[$targetSubject];
                $sourceSubjectId = $subjectIds[$mapping['source']];

                $targetRow = DB::table('subject_grade_levels')
                    ->where('grade_level_id', $gradeLevelIds[$grade])
                    ->where('subject_id', $targetSubjectId)
                    ->orderByRaw('CASE WHEN curriculum_id IS NULL THEN 0 ELSE 1 END')
                    ->first();

                $sourceRow = $targetRow ?: DB::table('subject_grade_levels')
                    ->where('grade_level_id', $gradeLevelIds[$grade])
                    ->where('subject_id', $sourceSubjectId)
                    ->orderByRaw('CASE WHEN curriculum_id IS NULL THEN 0 ELSE 1 END')
                    ->first();

                if ($sourceRow) {
                    $updates = [
                        'subject_id' => $targetSubjectId,
                        'resource_ratio' => $mapping['ratio'],
                        'updated_at' => $now,
                    ];

                    if ($hasLegacyKeyStage) {
                        $updates['key_stage'] = $stageName;
                    }

                    DB::table('subject_grade_levels')->where('id', $sourceRow->id)->update($updates);
                    $keptIds[] = $sourceRow->id;

                    continue;
                }

                $id = (string) Str::uuid();
                $values = [
                    'id' => $id,
                    'subject_id' => $targetSubjectId,
                    'grade_level_id' => $gradeLevelIds[$grade],
                    'resource_ratio' => $mapping['ratio'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($hasLegacyKeyStage) {
                    $values['key_stage'] = $stageName;
                }

                if ($hasCurriculum) {
                    $values['curriculum_id'] = null;
                }

                DB::table('subject_grade_levels')->insert($values);
                $keptIds[] = $id;
            }
        }

        return array_values(array_unique($keptIds));
    }

    /**
     * @param  array<int, string>  $keptMappingIds
     */
    private function removeObsoleteMappings(array $keptMappingIds): void
    {
        $obsoleteIds = DB::table('subject_grade_levels')
            ->whereNotIn('id', $keptMappingIds)
            ->pluck('id')
            ->all();

        if ($obsoleteIds === []) {
            return;
        }

        $this->removeMappingIdsFromResourceList('print_resources', $obsoleteIds);
        $this->removeMappingIdsFromResourceList('nonprint_resources', $obsoleteIds);

        if (Schema::hasTable('print_resource_sgl')) {
            DB::table('print_resource_sgl')->whereIn('sgl_id', $obsoleteIds)->delete();
        }

        foreach (array_chunk($obsoleteIds, 500) as $chunk) {
            DB::table('subject_grade_levels')->whereIn('id', $chunk)->delete();
        }
    }

    /**
     * @param  array<int, string>  $obsoleteIds
     */
    private function removeMappingIdsFromResourceList(string $table, array $obsoleteIds): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'subject_grade_level_ids')) {
            return;
        }

        $obsolete = array_fill_keys($obsoleteIds, true);

        DB::table($table)
            ->select(['id', 'subject_grade_level_ids'])
            ->whereNotNull('subject_grade_level_ids')
            ->orderBy('id')
            ->chunkById(250, function ($resources) use ($table, $obsolete) {
                foreach ($resources as $resource) {
                    $originalIds = array_values(array_filter(array_map(
                        'trim',
                        explode(',', (string) $resource->subject_grade_level_ids)
                    )));

                    $validIds = array_values(array_unique(array_filter(
                        $originalIds,
                        fn (string $id) => ! isset($obsolete[$id])
                    )));

                    if ($validIds !== $originalIds) {
                        DB::table($table)->where('id', $resource->id)->update([
                            'subject_grade_level_ids' => implode(',', $validIds),
                        ]);
                    }
                }
            }, 'id');
    }

    /**
     * @param  array<string, string>  $subjectIds
     */
    private function removeObsoleteLegacySubjects(array $subjectIds): void
    {
        $keptIds = array_values($subjectIds);
        $legacyNames = ['Edukasyon sa Pagpapakatao', 'EPP/TLE/TVE', 'MTB-MLE'];

        $obsoleteSubjects = DB::table('subjects')
            ->whereIn('subject_name', $legacyNames)
            ->whereNotIn('id', $keptIds)
            ->pluck('id');

        foreach ($obsoleteSubjects as $subjectId) {
            if (! DB::table('subject_grade_levels')->where('subject_id', $subjectId)->exists()) {
                DB::table('subjects')->where('id', $subjectId)->delete();
            }
        }
    }

    private function finishSubjectGradeLevelsTable(): void
    {
        $this->repointKeyStageInReportingViews(true);

        if (Schema::hasColumn('subject_grade_levels', 'key_stage')) {
            Schema::table('subject_grade_levels', function (Blueprint $table) {
                $table->dropColumn('key_stage');
            });
        }

        Schema::table('subject_grade_levels', function (Blueprint $table) {
            $table->string('resource_ratio', 5)->nullable(false)->change();
        });

        if (! $this->hasIndex('subject_grade_levels', 'subject_grade_level_unique')) {
            Schema::table('subject_grade_levels', function (Blueprint $table) {
                $table->unique(['subject_id', 'grade_level_id'], 'subject_grade_level_unique');
            });
        }
    }

    /**
     * The PostgreSQL reporting views expose key_stage inside their JSON data.
     * Repoint them before dropping either the legacy column or key_stages table.
     */
    private function repointKeyStageInReportingViews(bool $useNormalizedKeyStage): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $views = DB::select(<<<'SQL'
            SELECT schemaname, viewname, definition
            FROM pg_views
            WHERE schemaname = current_schema()
              AND viewname IN ('vw_print_resources_by_library', 'vw_nonprint_resources_by_library')
            SQL);

        foreach ($views as $view) {
            $definition = rtrim(trim($view->definition), ';');

            if ($useNormalizedKeyStage) {
                $updatedDefinition = str_replace(
                    "'key_stage', sgl.key_stage",
                    "'key_stage', ks.name",
                    $definition,
                    $keyStageReferenceCount
                );

                if ($keyStageReferenceCount !== 1) {
                    throw new RuntimeException("Unable to update key-stage reference in {$view->viewname}.");
                }

                $updatedDefinition = str_replace(
                    'JOIN grade_levels gl ON ((gl.id = sgl.grade_level_id)))',
                    "JOIN grade_levels gl ON ((gl.id = sgl.grade_level_id)))\n             JOIN key_stages ks ON ((ks.id = gl.key_stage_id))",
                    $updatedDefinition,
                    $gradeJoinCount
                );

                if ($gradeJoinCount !== 1) {
                    throw new RuntimeException("Unable to join key_stages in {$view->viewname}.");
                }
            } else {
                $updatedDefinition = str_replace(
                    "'key_stage', ks.name",
                    "'key_stage', sgl.key_stage",
                    $definition,
                    $keyStageReferenceCount
                );

                if ($keyStageReferenceCount !== 1) {
                    throw new RuntimeException("Unable to restore key-stage reference in {$view->viewname}.");
                }

                $updatedDefinition = str_replace(
                    "\n             JOIN key_stages ks ON ((ks.id = gl.key_stage_id))",
                    '',
                    $updatedDefinition,
                    $gradeJoinCount
                );

                if ($gradeJoinCount !== 1) {
                    throw new RuntimeException("Unable to remove key_stages join from {$view->viewname}.");
                }
            }

            $schema = str_replace('"', '""', $view->schemaname);
            $name = str_replace('"', '""', $view->viewname);

            DB::statement("CREATE OR REPLACE VIEW \"{$schema}\".\"{$name}\" AS {$updatedDefinition}");
        }
    }

    /**
     * @return array<string, array{name: string, sort_order: int}>
     */
    private function keyStageDefinitions(): array
    {
        return [
            'KS1' => [
                'name' => 'Key Stage 1 — Kindergarten to Grade 3 (Foundational Skills)',
                'sort_order' => 1,
            ],
            'KS2' => [
                'name' => 'Key Stage 2 — Grades 4 to 6 (Consolidation)',
                'sort_order' => 2,
            ],
            'KS3' => [
                'name' => 'Key Stage 3 — Grades 7 to 10 (Exploratory & Application)',
                'sort_order' => 3,
            ],
            'KS4' => [
                'name' => 'Key Stage 4 — Grades 11 to 12 (Senior High School) Core Subjects',
                'sort_order' => 4,
            ],
        ];
    }

    /**
     * @return array<string, array{classification: string, key_stage: string, legacy_stage: string, sort_order: int}>
     */
    private function gradeDefinitions(): array
    {
        return [
            'Kindergarten' => ['classification' => 'S1', 'key_stage' => 'KS1', 'legacy_stage' => 'S1', 'sort_order' => 0],
            'Grade 1' => ['classification' => 'S1', 'key_stage' => 'KS1', 'legacy_stage' => 'S1', 'sort_order' => 1],
            'Grade 2' => ['classification' => 'S1', 'key_stage' => 'KS1', 'legacy_stage' => 'S1', 'sort_order' => 2],
            'Grade 3' => ['classification' => 'S1', 'key_stage' => 'KS1', 'legacy_stage' => 'S1', 'sort_order' => 3],
            'Grade 4' => ['classification' => 'ES', 'key_stage' => 'KS2', 'legacy_stage' => 'ES', 'sort_order' => 4],
            'Grade 5' => ['classification' => 'ES', 'key_stage' => 'KS2', 'legacy_stage' => 'ES', 'sort_order' => 5],
            'Grade 6' => ['classification' => 'ES', 'key_stage' => 'KS2', 'legacy_stage' => 'ES', 'sort_order' => 6],
            'Grade 7' => ['classification' => 'JHS', 'key_stage' => 'KS3', 'legacy_stage' => 'JHS', 'sort_order' => 7],
            'Grade 8' => ['classification' => 'JHS', 'key_stage' => 'KS3', 'legacy_stage' => 'JHS', 'sort_order' => 8],
            'Grade 9' => ['classification' => 'JHS', 'key_stage' => 'KS3', 'legacy_stage' => 'JHS', 'sort_order' => 9],
            'Grade 10' => ['classification' => 'JHS', 'key_stage' => 'KS3', 'legacy_stage' => 'JHS', 'sort_order' => 10],
            'Grade 11' => ['classification' => 'SHS', 'key_stage' => 'KS4', 'legacy_stage' => 'SHS', 'sort_order' => 11],
            'Grade 12' => ['classification' => 'SHS', 'key_stage' => 'KS4', 'legacy_stage' => 'SHS', 'sort_order' => 12],
        ];
    }

    /**
     * @return array<string, array{abbrv: string, legacy_names: array<int, string>}>
     */
    private function subjectDefinitions(): array
    {
        return [
            'Kindergarten Domains' => ['abbrv' => 'Kinder', 'legacy_names' => []],
            'Language' => ['abbrv' => 'Language', 'legacy_names' => ['MTB-MLE']],
            'Reading & Literacy' => ['abbrv' => 'R&L', 'legacy_names' => []],
            'Filipino' => ['abbrv' => 'Fil', 'legacy_names' => []],
            'English' => ['abbrv' => 'Eng', 'legacy_names' => []],
            'Mathematics' => ['abbrv' => 'Math', 'legacy_names' => []],
            'Science' => ['abbrv' => 'Sci', 'legacy_names' => []],
            'Makabansa' => ['abbrv' => 'Makabansa', 'legacy_names' => []],
            'GMRC' => ['abbrv' => 'GMRC', 'legacy_names' => ['Edukasyon sa Pagpapakatao']],
            'Araling Panlipunan' => ['abbrv' => 'AP', 'legacy_names' => []],
            'MAPEH' => ['abbrv' => 'MAPEH', 'legacy_names' => []],
            'TLE / EPP' => ['abbrv' => 'TLE/EPP', 'legacy_names' => ['EPP/TLE/TVE']],
            'TLE' => ['abbrv' => 'TLE', 'legacy_names' => []],
            'Values Education' => ['abbrv' => 'Values Ed', 'legacy_names' => []],
            'Effective Communication' => ['abbrv' => 'Effective Comm', 'legacy_names' => []],
            'General Mathematics' => ['abbrv' => 'Gen Math', 'legacy_names' => []],
            'General Science' => ['abbrv' => 'Gen Sci', 'legacy_names' => []],
            'Life & Career Skills' => ['abbrv' => 'LCS', 'legacy_names' => []],
            'Kasaysayan at Lipunang Pilipino' => ['abbrv' => 'KLP', 'legacy_names' => []],
            'Specialized / Track Subjects' => ['abbrv' => 'Specialized/Track', 'legacy_names' => []],
        ];
    }

    /**
     * Each entry is target subject => [legacy source subject, image ratio].
     *
     * @return array<string, array<string, array{source: string, ratio: string}>>
     */
    private function subjectGradeMatrix(): array
    {
        $matrix = [
            'Kindergarten' => [
                'Kindergarten Domains' => ['source' => 'Language', 'ratio' => '1:1'],
            ],
            'Grade 1' => [
                'Language' => ['source' => 'Language', 'ratio' => '1:1'],
                'Reading & Literacy' => ['source' => 'English', 'ratio' => '1:2'],
                'Mathematics' => ['source' => 'Mathematics', 'ratio' => '1:1'],
                'Makabansa' => ['source' => 'Araling Panlipunan', 'ratio' => '1:2'],
                'GMRC' => ['source' => 'GMRC', 'ratio' => '1:2'],
            ],
            'Grade 2' => [
                'Filipino' => ['source' => 'Filipino', 'ratio' => '1:1'],
                'English' => ['source' => 'English', 'ratio' => '1:1'],
                'Mathematics' => ['source' => 'Mathematics', 'ratio' => '1:1'],
                'Makabansa' => ['source' => 'Araling Panlipunan', 'ratio' => '1:2'],
                'GMRC' => ['source' => 'GMRC', 'ratio' => '1:2'],
            ],
            'Grade 3' => [
                'Filipino' => ['source' => 'Filipino', 'ratio' => '1:1'],
                'English' => ['source' => 'English', 'ratio' => '1:1'],
                'Mathematics' => ['source' => 'Mathematics', 'ratio' => '1:1'],
                'Science' => ['source' => 'Science', 'ratio' => '1:2'],
                'Makabansa' => ['source' => 'Araling Panlipunan', 'ratio' => '1:2'],
                'GMRC' => ['source' => 'GMRC', 'ratio' => '1:2'],
            ],
        ];

        foreach (['Grade 4', 'Grade 5', 'Grade 6'] as $grade) {
            $matrix[$grade] = [
                'Filipino' => ['source' => 'Filipino', 'ratio' => '1:1'],
                'English' => ['source' => 'English', 'ratio' => '1:1'],
                'Mathematics' => ['source' => 'Mathematics', 'ratio' => '1:1'],
                'Science' => ['source' => 'Science', 'ratio' => '1:1'],
                'Araling Panlipunan' => ['source' => 'Araling Panlipunan', 'ratio' => '1:1'],
                'MAPEH' => ['source' => 'MAPEH', 'ratio' => '1:2'],
                'TLE / EPP' => ['source' => 'TLE / EPP', 'ratio' => '1:3'],
                'GMRC' => ['source' => 'GMRC', 'ratio' => '1:2'],
            ];
        }

        foreach (['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'] as $grade) {
            $matrix[$grade] = [
                'Filipino' => ['source' => 'Filipino', 'ratio' => '1:1'],
                'English' => ['source' => 'English', 'ratio' => '1:1'],
                'Mathematics' => ['source' => 'Mathematics', 'ratio' => '1:1'],
                'Science' => ['source' => 'Science', 'ratio' => '1:1'],
                'Araling Panlipunan' => ['source' => 'Araling Panlipunan', 'ratio' => '1:2'],
                'MAPEH' => ['source' => 'MAPEH', 'ratio' => '1:2'],
                'TLE' => ['source' => 'TLE / EPP', 'ratio' => '1:3'],
                'Values Education' => ['source' => 'GMRC', 'ratio' => '1:2'],
            ];
        }

        foreach (['Grade 11', 'Grade 12'] as $grade) {
            $matrix[$grade] = [
                'Effective Communication' => ['source' => 'English', 'ratio' => '1:2'],
                'General Mathematics' => ['source' => 'Mathematics', 'ratio' => '1:2'],
                'General Science' => ['source' => 'Science', 'ratio' => '1:2'],
                'Life & Career Skills' => ['source' => 'GMRC', 'ratio' => '1:3'],
                'Kasaysayan at Lipunang Pilipino' => ['source' => 'Filipino', 'ratio' => '1:2'],
                'Specialized / Track Subjects' => ['source' => 'TLE / EPP', 'ratio' => '1:3'],
            ];
        }

        return $matrix;
    }

    private function hasIndex(string $table, string $name): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $name);
    }

    private function hasForeign(string $table, string $name): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return collect(Schema::getForeignKeys($table))
            ->contains(fn (array $foreign) => ($foreign['name'] ?? null) === $name);
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        return collect(Schema::getColumns($table))->contains(
            fn (array $definition) => ($definition['name'] ?? null) === $column
                && (bool) ($definition['nullable'] ?? false)
        );
    }
};
