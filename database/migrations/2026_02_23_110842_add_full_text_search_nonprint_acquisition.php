<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------------
        // 1. Add tsvector column to nonprint_acquisitions (safe if rerun)
        // ---------------------------------------------------------------
        DB::statement('ALTER TABLE nonprint_acquisitions ADD COLUMN IF NOT EXISTS search_vector tsvector');

        // ---------------------------------------------------------------
        // 2. GIN index for fast full-text search on acquisitions
        // ---------------------------------------------------------------
        DB::statement('CREATE INDEX IF NOT EXISTS nonprint_acquisitions_search_idx ON nonprint_acquisitions USING GIN (search_vector)');

        // ---------------------------------------------------------------
        // 3. Builder function — accepts nonprint_id + library_id directly
        //    so it can be called safely from both the trigger (using NEW.*)
        //    and from application observers (passing the saved values).
        //
        //    ROOT CAUSE FIX: the old version accepted an acquisition_id and
        //    queried back the same row inside a BEFORE trigger — but on INSERT
        //    the row does not exist yet, so the SELECT returns nothing and the
        //    vector is always empty. Passing the FK values directly avoids
        //    that chicken-and-egg problem entirely.
        // ---------------------------------------------------------------
        DB::statement("
            CREATE OR REPLACE FUNCTION build_nonprint_acquisition_search_vector(p_nonprint_id UUID, p_library_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text        text := '';
                subjects_text     text := '';
                grades_text       text := '';
                library_name_text text := '';
                res_rec           RECORD;
            BEGIN
                -- Get the linked resource (brand, code, version, url, size, model, nonprint_title_id, subject_grade_level_ids)
                SELECT brand, code, version, url, size, model, nonprint_title_id, subject_grade_level_ids
                INTO res_rec
                FROM nonprint_resources
                WHERE id = p_nonprint_id;

                -- Get title from nonprint_titles
                SELECT title INTO title_text
                FROM nonprint_titles
                WHERE id = res_rec.nonprint_title_id;

                -- Get subjects and grade levels (concatenated)
                IF res_rec.subject_grade_level_ids IS NOT NULL AND res_rec.subject_grade_level_ids <> '' THEN
                    SELECT
                        string_agg(DISTINCT s.subject_name, ' '),
                        string_agg(DISTINCT g.grade, ' ')
                    INTO subjects_text, grades_text
                    FROM subject_grade_levels sgl
                    JOIN subjects s ON sgl.subject_id = s.id
                    JOIN grade_levels g ON sgl.grade_level_id = g.id
                    WHERE sgl.id::text = ANY(string_to_array(res_rec.subject_grade_level_ids, ','));
                END IF;

                -- Resolve library name from whichever library table owns the ID
                SELECT COALESCE(
                    (SELECT library_name FROM school_libraries   WHERE id = p_library_id),
                    (SELECT library_name FROM division_libraries WHERE id = p_library_id),
                    (SELECT library_name FROM region_libraries   WHERE id = p_library_id),
                    ''
                ) INTO library_name_text;

                -- Build weighted search vector
                --   A → title          (highest relevance)
                --   B → brand, subjects
                --   C → code, model, grades
                --   D → library name, version, url, size
                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text,           '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.brand,        '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(subjects_text,        '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.code,         '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.model,        '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(grades_text,          '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(library_name_text,    '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.version,      '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.url,          '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.size,         '')), 'D');
            END;
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        // ---------------------------------------------------------------
        // 4. Trigger function — passes NEW.nonprint_id and NEW.library_id
        //    directly so the builder never needs to re-query the same row.
        // ---------------------------------------------------------------
        DB::statement("
            CREATE OR REPLACE FUNCTION nonprint_acquisitions_search_trigger()
            RETURNS trigger AS \$\$
            BEGIN
                NEW.search_vector := build_nonprint_acquisition_search_vector(NEW.nonprint_id, NEW.library_id);
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // ---------------------------------------------------------------
        // 5. Attach the trigger (drop first so reruns are safe)
        // ---------------------------------------------------------------
        DB::statement('DROP TRIGGER IF EXISTS nonprint_acquisitions_search_update ON nonprint_acquisitions');

        DB::statement('
            CREATE TRIGGER nonprint_acquisitions_search_update
            BEFORE INSERT OR UPDATE ON nonprint_acquisitions
            FOR EACH ROW
            EXECUTE FUNCTION nonprint_acquisitions_search_trigger();
        ');

        // ---------------------------------------------------------------
        // 6. Populate existing rows
        // ---------------------------------------------------------------
        echo "Populating search vectors for existing nonprint acquisitions...\n";
        DB::statement('
            UPDATE nonprint_acquisitions
            SET search_vector = build_nonprint_acquisition_search_vector(nonprint_id, library_id)
        ');
        echo "Done!\n";
    }

    public function down()
    {
        DB::statement('DROP TRIGGER IF EXISTS nonprint_acquisitions_search_update ON nonprint_acquisitions');
        DB::statement('DROP FUNCTION IF EXISTS nonprint_acquisitions_search_trigger()');
        DB::statement('DROP FUNCTION IF EXISTS build_nonprint_acquisition_search_vector(UUID, UUID)');
        DB::statement('DROP INDEX IF EXISTS nonprint_acquisitions_search_idx');
        DB::statement('ALTER TABLE nonprint_acquisitions DROP COLUMN IF EXISTS search_vector');
    }
};
