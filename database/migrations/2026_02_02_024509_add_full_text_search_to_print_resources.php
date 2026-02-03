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
        DB::statement('ALTER TABLE print_resources ADD COLUMN search_vector tsvector');

        // Create GIN index (crucial for 100k+ records!)
        DB::statement('CREATE INDEX print_resources_search_idx ON print_resources USING GIN (search_vector)');

        // Create function to build search vector
        DB::statement("
            CREATE OR REPLACE FUNCTION build_print_resource_search_vector(resource_id UUID)
            RETURNS tsvector AS $$
            DECLARE
                title_text text := '';
                authors_text text := '';
                library_name_text text := '';
                resource_rec RECORD;
            BEGIN
                -- Get the resource record
                SELECT isbn, publisher, copyright::text, print_title_id, library_id
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
                JOIN authors a ON apt.author_id = a.id
                WHERE pt.id = resource_rec.print_title_id;

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
                    setweight(to_tsvector('english', COALESCE(authors_text, '')), 'B') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.isbn, '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.publisher, '')), 'C') ||
                    setweight(to_tsvector('english', COALESCE(library_name_text, '')), 'D') ||
                    setweight(to_tsvector('english', COALESCE(resource_rec.copyright, '')), 'D');
            END;
            $$ LANGUAGE plpgsql STABLE;
        ");

        // FIXED: BEFORE trigger that modifies NEW directly - NO recursion!
        DB::statement("
            CREATE OR REPLACE FUNCTION print_resources_search_trigger()
            RETURNS trigger AS $$
            BEGIN
                -- Set the search_vector on NEW before the row is written
                NEW.search_vector := build_print_resource_search_vector(NEW.id);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // FIXED: BEFORE trigger (not AFTER)
        DB::statement('
            CREATE TRIGGER print_resources_search_update
            BEFORE INSERT OR UPDATE ON print_resources
            FOR EACH ROW
            EXECUTE FUNCTION print_resources_search_trigger();
        ');

        // Populate existing records (this will take a few minutes for 100k records)
        echo "Populating search vectors for existing records...\n";
        DB::statement('
            UPDATE print_resources
            SET search_vector = build_print_resource_search_vector(id)
        ');
        echo "Done!\n";
    }

    public function down()
    {
        DB::statement('DROP TRIGGER IF EXISTS print_resources_search_update ON print_resources');
        DB::statement('DROP FUNCTION IF EXISTS print_resources_search_trigger()');
        DB::statement('DROP FUNCTION IF EXISTS build_print_resource_search_vector(UUID)');
        DB::statement('DROP INDEX IF EXISTS print_resources_search_idx');
        DB::statement('ALTER TABLE print_resources DROP COLUMN IF EXISTS search_vector');
    }
};
