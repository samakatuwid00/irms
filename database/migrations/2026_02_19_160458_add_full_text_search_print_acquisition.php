<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE print_acquisitions ADD COLUMN IF NOT EXISTS search_vector tsvector');

        DB::statement('CREATE INDEX IF NOT EXISTS print_acquisitions_search_idx ON print_acquisitions USING GIN (search_vector)');

        DB::statement("
            CREATE OR REPLACE FUNCTION build_print_acquisition_search_vector(p_print_id UUID, p_library_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text        text := '';
                authors_text      text := '';
                library_name_text text := '';
                type_name_text    text := '';
                shortname_text    text := '';
                subjects_text     text := '';
                grades_text       text := '';
                res_rec           RECORD;
            BEGIN
                -- Get the linked resource
                SELECT isbn, publisher, copyright::text, print_title_id, print_type_id, subject_grade_level_ids
                INTO res_rec
                FROM print_resources
                WHERE id = p_print_id;

                -- Get title from print_titles
                SELECT title INTO title_text
                FROM print_titles
                WHERE id = res_rec.print_title_id;

                -- Get authors (concatenated with spaces)
                SELECT string_agg(a.author_name, ' ')
                INTO authors_text
                FROM print_titles pt
                JOIN author_print_title apt ON pt.id = apt.print_title_id
                JOIN authors a              ON apt.author_id = a.id
                WHERE pt.id = res_rec.print_title_id;

                -- Resolve library name from whichever library table owns the ID
                SELECT COALESCE(
                    (SELECT library_name FROM school_libraries   WHERE id = p_library_id),
                    (SELECT library_name FROM division_libraries WHERE id = p_library_id),
                    (SELECT library_name FROM region_libraries   WHERE id = p_library_id),
                    ''
                ) INTO library_name_text;

                -- Get print type info
                SELECT type_name, shortname
                INTO type_name_text, shortname_text
                FROM print_types
                WHERE id = res_rec.print_type_id;

                -- Get subjects and grade levels from comma-separated IDs
                IF res_rec.subject_grade_level_ids IS NOT NULL AND res_rec.subject_grade_level_ids <> '' THEN
                    SELECT
                        string_agg(DISTINCT s.subject_name, ' '),
                        string_agg(DISTINCT g.grade, ' ')
                    INTO subjects_text, grades_text
                    FROM subject_grade_levels sgl
                    JOIN subjects s     ON sgl.subject_id = s.id
                    JOIN grade_levels g ON sgl.grade_level_id = g.id
                    WHERE sgl.id::text = ANY(string_to_array(res_rec.subject_grade_level_ids, ','));
                END IF;

                -- Build weighted search vector
                --   A → title          (highest relevance)
                --   B → authors, subjects
                --   C → isbn, publisher, type_name, shortname, grades
                --      NOTE: grades_text uses 'simple' dictionary so numbers like
                --            1, 2, 10 are preserved — 'english' would strip them
                --   D → library name, copyright
                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text,           '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(authors_text,         '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(subjects_text,        '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.isbn,         '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.publisher,    '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(type_name_text,       '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(shortname_text,       '')), 'C') ||
                    setweight(to_tsvector('simple',  COALESCE(grades_text,          '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(library_name_text,    '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.copyright,    '')), 'D');
            END;
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        DB::statement("
            CREATE OR REPLACE FUNCTION print_acquisitions_search_trigger()
            RETURNS trigger AS \$\$
            BEGIN
                NEW.search_vector := build_print_acquisition_search_vector(NEW.print_id, NEW.library_id);
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::statement('DROP TRIGGER IF EXISTS print_acquisitions_search_update ON print_acquisitions');

        DB::statement('
            CREATE TRIGGER print_acquisitions_search_update
            BEFORE INSERT OR UPDATE ON print_acquisitions
            FOR EACH ROW
            EXECUTE FUNCTION print_acquisitions_search_trigger();
        ');

        echo "Populating search vectors for existing print acquisitions (subjects + grades added)...\n";
        DB::statement('
            UPDATE print_acquisitions
            SET search_vector = build_print_acquisition_search_vector(print_id, library_id)
        ');
        echo "Done!\n";
    }

    public function down()
    {
        DB::statement('DROP TRIGGER IF EXISTS print_acquisitions_search_update ON print_acquisitions');
        DB::statement('DROP FUNCTION IF EXISTS print_acquisitions_search_trigger()');
        DB::statement('DROP FUNCTION IF EXISTS build_print_acquisition_search_vector(UUID, UUID)');
        DB::statement('DROP INDEX IF EXISTS print_acquisitions_search_idx');
        DB::statement('ALTER TABLE print_acquisitions DROP COLUMN IF EXISTS search_vector');
    }
};
