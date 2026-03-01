<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE OR REPLACE FUNCTION build_print_resource_search_vector(resource_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text     text := '';
                authors_text   text := '';
                type_name_text text := '';
                shortname_text text := '';
                subjects_text  text := '';
                grades_text    text := '';
                resource_rec   RECORD;
            BEGIN
                -- Get the resource record
                SELECT isbn, publisher, copyright::text, print_title_id, print_type_id, subject_grade_level_ids
                INTO resource_rec
                FROM print_resources
                WHERE id = resource_id;

                -- Get title
                SELECT title INTO title_text
                FROM print_titles
                WHERE id = resource_rec.print_title_id;

                -- Get authors (concatenated with spaces)
                SELECT string_agg(a.author_name, ' ')
                INTO authors_text
                FROM print_titles pt
                JOIN author_print_title apt ON pt.id = apt.print_title_id
                JOIN authors a              ON apt.author_id = a.id
                WHERE pt.id = resource_rec.print_title_id;

                -- Get print type info
                SELECT type_name, shortname
                INTO type_name_text, shortname_text
                FROM print_types
                WHERE id = resource_rec.print_type_id;

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

                -- Build weighted search vector
                --   A → title          (highest relevance)
                --   B → authors, subjects
                --   C → isbn, publisher, type_name, shortname, grades
                --      NOTE: grades_text uses 'simple' dictionary so numbers like
                --            1, 2, 10 are preserved — 'english' would strip them
                --   D → copyright
                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text,             '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(authors_text,           '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(subjects_text,          '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.isbn,      '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.publisher, '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(type_name_text,         '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(shortname_text,         '')), 'C') ||
                    setweight(to_tsvector('simple',  COALESCE(grades_text,            '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.copyright, '')), 'D');
            END;
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        echo "Rebuilding print_resource search vectors (subjects + grades added)...\n";
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
        ');
        echo "Done!\n";
    }

    public function down()
    {
        DB::statement("
            CREATE OR REPLACE FUNCTION build_print_resource_search_vector(resource_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text     text := '';
                authors_text   text := '';
                type_name_text text := '';
                shortname_text text := '';
                resource_rec   RECORD;
            BEGIN
                SELECT isbn, publisher, copyright::text, print_title_id, print_type_id
                INTO resource_rec
                FROM print_resources
                WHERE id = resource_id;

                SELECT title INTO title_text
                FROM print_titles
                WHERE id = resource_rec.print_title_id;

                SELECT string_agg(a.author_name, ' ')
                INTO authors_text
                FROM print_titles pt
                JOIN author_print_title apt ON pt.id = apt.print_title_id
                JOIN authors a              ON apt.author_id = a.id
                WHERE pt.id = resource_rec.print_title_id;

                SELECT type_name, shortname
                INTO type_name_text, shortname_text
                FROM print_types
                WHERE id = resource_rec.print_type_id;

                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text,             '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(authors_text,           '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.isbn,      '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.publisher, '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(type_name_text,         '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(shortname_text,         '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.copyright, '')), 'D');
            END;
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        echo "Reverting print_resource search vectors (subjects + grades removed)...\n";
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
        ');
        echo "Done!\n";
    }
};
