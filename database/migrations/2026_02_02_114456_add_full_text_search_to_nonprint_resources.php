<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------------
        // 1. Replace the search vector builder function for nonprint_resources
        //    Library info has been removed — nonprint_resources is now a pure
        //    resource-metadata table (brand, code, version, url, size, model).
        //    Library info is now stored in nonprint_acquisitions table.
        // ---------------------------------------------------------------
        DB::statement("
            CREATE OR REPLACE FUNCTION build_nonprint_resource_search_vector(resource_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text text := '';
                subjects_text text := '';
                grades_text text := '';
                resource_rec RECORD;
            BEGIN
                -- Get the resource record (library_id / library_name no longer here)
                SELECT
                    brand, code, version, url, size, model,
                    nonprint_title_id, subject_grade_level_ids
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

                -- Build weighted search vector (no library info)
                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text,             '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.brand,     '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(subjects_text,          '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.code,      '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.model,     '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(grades_text,            '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.version,   '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.url,       '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.size,      '')), 'D');
            END;
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        // ---------------------------------------------------------------
        // 2. Rebuild all existing nonprint_resource search vectors
        //    (strips out any old library-name tokens)
        // ---------------------------------------------------------------
        echo "Rebuilding nonprint_resource search vectors (library info removed)...\n";
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
        ');
        echo "Done!\n";
    }

    public function down()
    {
        // Restore the original function that included library info
        DB::statement("
            CREATE OR REPLACE FUNCTION build_nonprint_resource_search_vector(resource_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text text := '';
                subjects_text text := '';
                grades_text text := '';
                library_name_text text := '';
                resource_rec RECORD;
            BEGIN
                SELECT
                    brand, code, version, url, size, model,
                    nonprint_title_id, library_id, subject_grade_level_ids
                INTO resource_rec
                FROM nonprint_resources
                WHERE id = resource_id;

                SELECT title INTO title_text
                FROM nonprint_titles
                WHERE id = resource_rec.nonprint_title_id;

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

                SELECT COALESCE(
                    (SELECT library_name FROM school_libraries WHERE id = resource_rec.library_id),
                    (SELECT library_name FROM division_libraries WHERE id = resource_rec.library_id),
                    (SELECT library_name FROM region_libraries WHERE id = resource_rec.library_id),
                    ''
                ) INTO library_name_text;

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
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
        ');
    }
};
