<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------------
        // 1. Add tsvector column to print_acquisitions (safe if rerun)
        // ---------------------------------------------------------------
        DB::statement('ALTER TABLE print_acquisitions ADD COLUMN IF NOT EXISTS search_vector tsvector');

        // ---------------------------------------------------------------
        // 2. GIN index for fast full-text search on acquisitions
        // ---------------------------------------------------------------
        DB::statement('CREATE INDEX IF NOT EXISTS print_acquisitions_search_idx ON print_acquisitions USING GIN (search_vector)');

        // ---------------------------------------------------------------
        // 3. Builder function — accepts print_id + library_id directly
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
            CREATE OR REPLACE FUNCTION build_print_acquisition_search_vector(p_print_id UUID, p_library_id UUID)
            RETURNS tsvector AS \$\$
            DECLARE
                title_text        text := '';
                authors_text      text := '';
                library_name_text text := '';
                res_rec           RECORD;
            BEGIN
                -- Get the linked resource (isbn, publisher, copyright, print_title_id)
                SELECT isbn, publisher, copyright::text, print_title_id
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

                -- Build weighted search vector
                --   A → title          (highest relevance)
                --   B → authors
                --   C → isbn, publisher
                --   D → library name, copyright
                RETURN
                    setweight(to_tsvector('english', COALESCE(title_text,           '')), 'A') ||
                    setweight(to_tsvector('english', COALESCE(authors_text,         '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.isbn,         '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.publisher,    '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(library_name_text,    '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(res_rec.copyright,    '')), 'D');
            END;
            \$\$ LANGUAGE plpgsql STABLE;
        ");

        // ---------------------------------------------------------------
        // 4. Trigger function — passes NEW.print_id and NEW.library_id
        //    directly so the builder never needs to re-query the same row.
        // ---------------------------------------------------------------
        DB::statement("
            CREATE OR REPLACE FUNCTION print_acquisitions_search_trigger()
            RETURNS trigger AS \$\$
            BEGIN
                NEW.search_vector := build_print_acquisition_search_vector(NEW.print_id, NEW.library_id);
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // ---------------------------------------------------------------
        // 5. Attach the trigger (drop first so reruns are safe)
        // ---------------------------------------------------------------
        DB::statement('DROP TRIGGER IF EXISTS print_acquisitions_search_update ON print_acquisitions');

        DB::statement('
            CREATE TRIGGER print_acquisitions_search_update
            BEFORE INSERT OR UPDATE ON print_acquisitions
            FOR EACH ROW
            EXECUTE FUNCTION print_acquisitions_search_trigger();
        ');

        // ---------------------------------------------------------------
        // 6. Populate existing rows
        // ---------------------------------------------------------------
        echo "Populating search vectors for existing print acquisitions...\n";
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
