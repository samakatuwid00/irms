<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Add tsvector column
        DB::statement('ALTER TABLE nonprint_resources ADD COLUMN search_vector tsvector');

        // Create GIN index (crucial for performance!)
        DB::statement('CREATE INDEX nonprint_resources_search_idx ON nonprint_resources USING GIN (search_vector)');

        // Create function to build search vector
        DB::statement("
            CREATE OR REPLACE FUNCTION build_nonprint_resource_search_vector(resource_id UUID)
            RETURNS tsvector AS $$
            DECLARE
                title_text text := '';
                subjects_text text := '';
                grades_text text := '';
                library_name_text text := '';
                resource_rec RECORD;
            BEGIN
                -- Get the resource record
                SELECT
                    brand, code, version, url, size, model,
                    nonprint_title_id, library_id, subject_grade_level_ids
                INTO resource_rec
                FROM nonprint_resources
                WHERE id = resource_id;

                -- Get title
                SELECT title INTO title_text
                FROM nonprint_titles
                WHERE id = resource_rec.nonprint_title_id;

                -- Get subjects and grade levels (concatenated)
                IF resource_rec.subject_grade_level_ids IS NOT NULL AND resource_rec.subject_grade_level_ids <> '' THEN
                    SELECT
                        string_agg(DISTINCT s.subject_name, ' '),
                        string_agg(DISTINCT g.grade, ' ')
                    INTO subjects_text, grades_text
                    FROM subject_grade_levels sgl
                    JOIN subjects s ON sgl.subject_id = s.id
                    JOIN grade_levels g ON sgl.grade_level_id = g.id
                    WHERE sgl.id::text = ANY(string_to_array(resource_rec.subject_grade_level_ids, ','));
                END IF;

                -- Get library name from any of the three library tables
                SELECT COALESCE(
                    (SELECT library_name FROM school_libraries WHERE id = resource_rec.library_id),
                    (SELECT library_name FROM division_libraries WHERE id = resource_rec.library_id),
                    (SELECT library_name FROM region_libraries WHERE id = resource_rec.library_id),
                    ''
                ) INTO library_name_text;

                -- Build weighted search vector
                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text, '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.brand, '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(subjects_text, '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.code, '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.model, '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(grades_text, '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(library_name_text, '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.version, '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.url, '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.size, '')), 'D');
            END;
            $$ LANGUAGE plpgsql STABLE;
        ");

        // FIXED: BEFORE trigger that modifies NEW directly - NO recursion!
        DB::statement("
            CREATE OR REPLACE FUNCTION nonprint_resources_search_trigger()
            RETURNS trigger AS $$
            BEGIN
                -- Set the search_vector on NEW before the row is written
                NEW.search_vector := build_nonprint_resource_search_vector(NEW.id);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // FIXED: BEFORE trigger (not AFTER)
        DB::statement('
            CREATE TRIGGER nonprint_resources_search_update
            BEFORE INSERT OR UPDATE ON nonprint_resources
            FOR EACH ROW
            EXECUTE FUNCTION nonprint_resources_search_trigger();
        ');

        // Populate existing records
        echo "Populating search vectors for existing non-print resources...\n";
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
        ');
        echo "Done!\n";
    }

    public function down()
    {
        DB::statement('DROP TRIGGER IF EXISTS nonprint_resources_search_update ON nonprint_resources');
        DB::statement('DROP FUNCTION IF EXISTS nonprint_resources_search_trigger()');
        DB::statement('DROP FUNCTION IF EXISTS build_nonprint_resource_search_vector(UUID)');
        DB::statement('DROP INDEX IF EXISTS nonprint_resources_search_idx');
        DB::statement('ALTER TABLE nonprint_resources DROP COLUMN IF EXISTS search_vector');
    }
};
