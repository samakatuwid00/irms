<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------------
        // 1. Replace the search vector builder function for print_resources
        //    Library info has been removed — print_resources is now a pure
        //    book-metadata table (title, authors, isbn, publisher, copyright).
        // ---------------------------------------------------------------
        DB::statement("
            CREATE OR REPLACE FUNCTION build_print_resource_search_vector(resource_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text   text := '';
                authors_text text := '';
                resource_rec RECORD;
            BEGIN
                -- Get the resource record (library_id / library_name no longer here)
                SELECT isbn, publisher, copyright::text, print_title_id
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

                -- Build weighted search vector (no library info)
                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text,              '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(authors_text,            '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.isbn,       '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.publisher,  '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.copyright,  '')), 'D');
            END;
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        // ---------------------------------------------------------------
        // 2. Rebuild all existing print_resource search vectors
        //    (strips out any old library-name tokens)
        // ---------------------------------------------------------------
        echo "Rebuilding print_resource search vectors (library info removed)...\n";
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
        ');
        echo "Done!\n";
    }

    public function down()
    {
        // Restore the original function that included library info
        DB::statement("
            CREATE OR REPLACE FUNCTION build_print_resource_search_vector(resource_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text        text := '';
                authors_text      text := '';
                library_name_text text := '';
                resource_rec      RECORD;
            BEGIN
                SELECT isbn, publisher, copyright::text, print_title_id, library_id
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

                SELECT COALESCE(
                    (SELECT library_name FROM school_libraries   WHERE id = resource_rec.library_id),
                    (SELECT library_name FROM division_libraries WHERE id = resource_rec.library_id),
                    (SELECT library_name FROM region_libraries   WHERE id = resource_rec.library_id),
                    ''
                ) INTO library_name_text;

                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text,             '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(authors_text,           '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.isbn,      '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.publisher, '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(library_name_text,      '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.copyright, '')), 'D');
            END;
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
        ');
    }
};
