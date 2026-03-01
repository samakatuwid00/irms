<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE OR REPLACE FUNCTION build_nonprint_resource_search_vector(resource_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text     text := '';
                subjects_text  text := '';
                grades_text    text := '';
                type_name_text text := '';
                shortname_text text := '';
                resource_rec   RECORD;
            BEGIN
                -- Get the resource record
                SELECT
                    brand, code, version, url, size, model,
                    nonprint_title_id, nonprint_type_id, subject_grade_level_ids
                INTO resource_rec
                FROM nonprint_resources
                WHERE id = resource_id;

                -- Get title
                SELECT title INTO title_text
                FROM nonprint_titles
                WHERE id = resource_rec.nonprint_title_id;

                -- Get subjects and grade levels from comma-separated IDs
                IF resource_rec.subject_grade_level_ids IS NOT NULL AND resource_rec.subject_grade_level_ids <> '' THEN
                    SELECT
                        string_agg(DISTINCT s.subject_name, ' '),
                        string_agg(DISTINCT g.grade, ' ')
                    INTO subjects_text, grades_text
                    FROM subject_grade_levels sgl
                    JOIN subjects s     ON sgl.subject_id = s.id
                    JOIN grade_levels g ON sgl.grade_level_id = g.id
                    WHERE sgl.id::text = ANY(string_to_array(resource_rec.subject_grade_level_ids, ','));
                END IF;

                -- Get nonprint type info
                SELECT type_name, shortname
                INTO type_name_text, shortname_text
                FROM nonprint_types
                WHERE id = resource_rec.nonprint_type_id;

                -- Build weighted search vector
                --   NOTE: grades_text uses 'simple' dictionary so numbers like
                --         1, 2, 10 are preserved — 'english' would strip them
                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text,             '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.brand,     '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(subjects_text,          '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.code,      '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.model,     '')), 'C') ||
                    setweight(to_tsvector('simple',  COALESCE(grades_text,            '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(type_name_text,         '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(shortname_text,         '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.version,   '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.url,       '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.size,      '')), 'D');
            END;
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        echo "Rebuilding nonprint_resource search vectors (simple dictionary for grades)...\n";
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
        ');
        echo "Done!\n";
    }

    public function down()
    {
        DB::statement("
            CREATE OR REPLACE FUNCTION build_nonprint_resource_search_vector(resource_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text     text := '';
                subjects_text  text := '';
                grades_text    text := '';
                type_name_text text := '';
                shortname_text text := '';
                resource_rec   RECORD;
            BEGIN
                SELECT
                    brand, code, version, url, size, model,
                    nonprint_title_id, nonprint_type_id, subject_grade_level_ids
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
                    JOIN subjects s     ON sgl.subject_id = s.id
                    JOIN grade_levels g ON sgl.grade_level_id = g.id
                    WHERE sgl.id::text = ANY(string_to_array(resource_rec.subject_grade_level_ids, ','));
                END IF;

                SELECT type_name, shortname
                INTO type_name_text, shortname_text
                FROM nonprint_types
                WHERE id = resource_rec.nonprint_type_id;

                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text,             '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.brand,     '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(subjects_text,          '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.code,      '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.model,     '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(grades_text,            '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(type_name_text,         '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(shortname_text,         '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.version,   '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.url,       '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.size,      '')), 'D');
            END;
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        echo "Reverting nonprint_resource search vectors...\n";
        DB::statement('
            UPDATE nonprint_resources
            SET search_vector = build_nonprint_resource_search_vector(id)
        ');
        echo "Done!\n";
    }
};
