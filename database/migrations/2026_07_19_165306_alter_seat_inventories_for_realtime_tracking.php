<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('seat_inventories')->truncate();

        // Alter seat_inventories table to map directly to voyages
        Schema::table('seat_inventories', function (Blueprint $table) {
            $table->dropForeign(['route_id']);
            $table->dropForeign(['vessel_id']);
            
            $table->dropUnique('seat_inv_unique');
            
            $table->dropColumn(['departure_date', 'route_id', 'vessel_id']);
            
            $table->foreignId('voyage_id')->after('id')->constrained('voyages')->cascadeOnDelete();
            
            $table->unique(['voyage_id', 'travel_class_id'], 'seat_inv_voyage_class_unique');
        });

                // Add Triggers for real-time synchronization
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_tr_booking_locks_after_insert() RETURNS TRIGGER AS $$
                BEGIN
                    UPDATE seat_inventories SET held_seats = held_seats + NEW.quantity WHERE voyage_id = NEW.voyage_id AND travel_class_id = NEW.ferry_class_id;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
                CREATE TRIGGER tr_booking_locks_after_insert AFTER INSERT ON booking_locks FOR EACH ROW EXECUTE FUNCTION fn_tr_booking_locks_after_insert();
            ");
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_tr_booking_locks_after_update() RETURNS TRIGGER AS $$
                BEGIN
                    IF NEW.quantity != OLD.quantity THEN
                        UPDATE seat_inventories SET held_seats = held_seats - OLD.quantity + NEW.quantity WHERE voyage_id = NEW.voyage_id AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
                CREATE TRIGGER tr_booking_locks_after_update AFTER UPDATE ON booking_locks FOR EACH ROW EXECUTE FUNCTION fn_tr_booking_locks_after_update();
            ");
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_tr_booking_locks_after_delete() RETURNS TRIGGER AS $$
                BEGIN
                    UPDATE seat_inventories SET held_seats = held_seats - OLD.quantity WHERE voyage_id = OLD.voyage_id AND travel_class_id = OLD.ferry_class_id;
                    RETURN OLD;
                END;
                $$ LANGUAGE plpgsql;
                CREATE TRIGGER tr_booking_locks_after_delete AFTER DELETE ON booking_locks FOR EACH ROW EXECUTE FUNCTION fn_tr_booking_locks_after_delete();
            ");
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_tr_tickets_after_insert() RETURNS TRIGGER AS $$
                BEGIN
                    IF NEW.status IN ('confirmed', 'held', 'checked_in', 'boarded') THEN
                        UPDATE seat_inventories SET sold_seats = sold_seats + 1 WHERE voyage_id = NEW.voyage_id AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
                CREATE TRIGGER tr_tickets_after_insert AFTER INSERT ON tickets FOR EACH ROW EXECUTE FUNCTION fn_tr_tickets_after_insert();
            ");
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_tr_tickets_after_update() RETURNS TRIGGER AS $$
                DECLARE
                    was_sold BOOLEAN;
                    is_sold BOOLEAN;
                BEGIN
                    was_sold := (OLD.status IN ('confirmed', 'held', 'checked_in', 'boarded'));
                    is_sold := (NEW.status IN ('confirmed', 'held', 'checked_in', 'boarded'));
                    IF is_sold = TRUE AND was_sold = FALSE THEN
                        UPDATE seat_inventories SET sold_seats = sold_seats + 1 WHERE voyage_id = NEW.voyage_id AND travel_class_id = NEW.ferry_class_id;
                    ELSIF is_sold = FALSE AND was_sold = TRUE THEN
                        UPDATE seat_inventories SET sold_seats = sold_seats - 1 WHERE voyage_id = NEW.voyage_id AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
                CREATE TRIGGER tr_tickets_after_update AFTER UPDATE ON tickets FOR EACH ROW EXECUTE FUNCTION fn_tr_tickets_after_update();
            ");
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_tr_tickets_after_delete() RETURNS TRIGGER AS $$
                BEGIN
                    IF OLD.status IN ('confirmed', 'held', 'checked_in', 'boarded') THEN
                        UPDATE seat_inventories SET sold_seats = sold_seats - 1 WHERE voyage_id = OLD.voyage_id AND travel_class_id = OLD.ferry_class_id;
                    END IF;
                    RETURN OLD;
                END;
                $$ LANGUAGE plpgsql;
                CREATE TRIGGER tr_tickets_after_delete AFTER DELETE ON tickets FOR EACH ROW EXECUTE FUNCTION fn_tr_tickets_after_delete();
            ");
        } else {
            DB::unprepared("
                CREATE TRIGGER tr_booking_locks_after_insert 
                AFTER INSERT ON booking_locks
                FOR EACH ROW
                BEGIN
                    UPDATE seat_inventories 
                    SET held_seats = held_seats + NEW.quantity
                    WHERE voyage_id = NEW.voyage_id 
                      AND travel_class_id = NEW.ferry_class_id;
                END;
            ");
            DB::unprepared("
                CREATE TRIGGER tr_booking_locks_after_update 
                AFTER UPDATE ON booking_locks
                FOR EACH ROW
                BEGIN
                    IF NEW.quantity != OLD.quantity THEN
                        UPDATE seat_inventories 
                        SET held_seats = held_seats - OLD.quantity + NEW.quantity
                        WHERE voyage_id = NEW.voyage_id 
                          AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                END;
            ");
            DB::unprepared("
                CREATE TRIGGER tr_booking_locks_after_delete 
                AFTER DELETE ON booking_locks
                FOR EACH ROW
                BEGIN
                    UPDATE seat_inventories 
                    SET held_seats = held_seats - OLD.quantity
                    WHERE voyage_id = OLD.voyage_id 
                      AND travel_class_id = OLD.ferry_class_id;
                END;
            ");
            DB::unprepared("
                CREATE TRIGGER tr_tickets_after_insert 
                AFTER INSERT ON tickets
                FOR EACH ROW
                BEGIN
                    IF NEW.status IN ('confirmed', 'held', 'checked_in', 'boarded') THEN
                        UPDATE seat_inventories 
                        SET sold_seats = sold_seats + 1
                        WHERE voyage_id = NEW.voyage_id 
                          AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                END;
            ");
            DB::unprepared("
                CREATE TRIGGER tr_tickets_after_update 
                AFTER UPDATE ON tickets
                FOR EACH ROW
                BEGIN
                    DECLARE was_sold BOOLEAN;
                    DECLARE is_sold BOOLEAN;
                    
                    SET was_sold = (OLD.status IN ('confirmed', 'held', 'checked_in', 'boarded'));
                    SET is_sold = (NEW.status IN ('confirmed', 'held', 'checked_in', 'boarded'));
                    
                    IF is_sold = TRUE AND was_sold = FALSE THEN
                        UPDATE seat_inventories 
                        SET sold_seats = sold_seats + 1
                        WHERE voyage_id = NEW.voyage_id 
                          AND travel_class_id = NEW.ferry_class_id;
                    ELSEIF is_sold = FALSE AND was_sold = TRUE THEN
                        UPDATE seat_inventories 
                        SET sold_seats = sold_seats - 1
                        WHERE voyage_id = NEW.voyage_id 
                          AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                END;
            ");
            DB::unprepared("
                CREATE TRIGGER tr_tickets_after_delete 
                AFTER DELETE ON tickets
                FOR EACH ROW
                BEGIN
                    IF OLD.status IN ('confirmed', 'held', 'checked_in', 'boarded') THEN
                        UPDATE seat_inventories 
                        SET sold_seats = sold_seats - 1
                        WHERE voyage_id = OLD.voyage_id 
                          AND travel_class_id = OLD.ferry_class_id;
                    END IF;
                END;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
        public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS tr_booking_locks_after_insert ON booking_locks');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_booking_locks_after_update ON booking_locks');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_booking_locks_after_delete ON booking_locks');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_tickets_after_insert ON tickets');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_tickets_after_update ON tickets');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_tickets_after_delete ON tickets');
        
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS fn_tr_booking_locks_after_insert');
            DB::unprepared('DROP FUNCTION IF EXISTS fn_tr_booking_locks_after_update');
            DB::unprepared('DROP FUNCTION IF EXISTS fn_tr_booking_locks_after_delete');
            DB::unprepared('DROP FUNCTION IF EXISTS fn_tr_tickets_after_insert');
            DB::unprepared('DROP FUNCTION IF EXISTS fn_tr_tickets_after_update');
            DB::unprepared('DROP FUNCTION IF EXISTS fn_tr_tickets_after_delete');
        }

        Schema::table('seat_inventories', function (Blueprint $table) {
            $table->dropForeign(['voyage_id']);
            $table->dropUnique('seat_inv_voyage_class_unique');
            $table->dropColumn('voyage_id');
            
            $table->date('departure_date')->after('id');
            $table->foreignId('route_id')->after('departure_date')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('vessel_id')->after('route_id')->constrained('ferries')->cascadeOnDelete();
            
            $table->unique(['departure_date', 'route_id', 'vessel_id', 'travel_class_id'], 'seat_inv_unique');
        });
    }
};
