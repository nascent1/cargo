<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_tr_booking_locks_after_update() RETURNS TRIGGER AS $$
                BEGIN
                    IF NEW.quantity != OLD.quantity THEN
                        UPDATE seat_inventories
                        SET held_seats = GREATEST(0, held_seats - OLD.quantity + NEW.quantity)
                        WHERE voyage_id = NEW.voyage_id
                          AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
            ");
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_tr_booking_locks_after_delete() RETURNS TRIGGER AS $$
                BEGIN
                    UPDATE seat_inventories
                    SET held_seats = GREATEST(0, held_seats - OLD.quantity)
                    WHERE voyage_id = OLD.voyage_id
                      AND travel_class_id = OLD.ferry_class_id;
                    RETURN OLD;
                END;
                $$ LANGUAGE plpgsql;
            ");
            DB::unprepared("
                CREATE OR REPLACE FUNCTION fn_tr_tickets_after_delete() RETURNS TRIGGER AS $$
                BEGIN
                    IF OLD.status IN ('confirmed', 'held', 'checked_in', 'boarded') THEN
                        UPDATE seat_inventories
                        SET sold_seats = GREATEST(0, sold_seats - 1)
                        WHERE voyage_id = OLD.voyage_id
                          AND travel_class_id = OLD.ferry_class_id;
                    END IF;
                    RETURN OLD;
                END;
                $$ LANGUAGE plpgsql;
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
                        UPDATE seat_inventories
                        SET sold_seats = sold_seats + 1
                        WHERE voyage_id = NEW.voyage_id
                          AND travel_class_id = NEW.ferry_class_id;
                    ELSIF is_sold = FALSE AND was_sold = TRUE THEN
                        UPDATE seat_inventories
                        SET sold_seats = GREATEST(0, sold_seats - 1)
                        WHERE voyage_id = NEW.voyage_id
                          AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
            ");
        } else {
            DB::unprepared("
                DROP TRIGGER IF EXISTS tr_booking_locks_after_update;
                CREATE TRIGGER tr_booking_locks_after_update
                AFTER UPDATE ON booking_locks
                FOR EACH ROW
                BEGIN
                    IF NEW.quantity != OLD.quantity THEN
                        UPDATE seat_inventories
                        SET held_seats = IF(
                            (CAST(held_seats AS SIGNED) - CAST(OLD.quantity AS SIGNED) + CAST(NEW.quantity AS SIGNED)) >= 0,
                            held_seats - OLD.quantity + NEW.quantity,
                            0
                        )
                        WHERE voyage_id = NEW.voyage_id
                          AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                END;

                DROP TRIGGER IF EXISTS tr_booking_locks_after_delete;
                CREATE TRIGGER tr_booking_locks_after_delete
                AFTER DELETE ON booking_locks
                FOR EACH ROW
                BEGIN
                    UPDATE seat_inventories
                    SET held_seats = IF(held_seats >= OLD.quantity, held_seats - OLD.quantity, 0)
                    WHERE voyage_id = OLD.voyage_id
                      AND travel_class_id = OLD.ferry_class_id;
                END;
                
                DROP TRIGGER IF EXISTS tr_tickets_after_delete;
                CREATE TRIGGER tr_tickets_after_delete
                AFTER DELETE ON tickets
                FOR EACH ROW
                BEGIN
                    IF OLD.status IN ('confirmed', 'held', 'checked_in', 'boarded') THEN
                        UPDATE seat_inventories
                        SET sold_seats = IF(sold_seats >= 1, sold_seats - 1, 0)
                        WHERE voyage_id = OLD.voyage_id
                          AND travel_class_id = OLD.ferry_class_id;
                    END IF;
                END;
                
                DROP TRIGGER IF EXISTS tr_tickets_after_update;
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
                        SET sold_seats = IF(sold_seats >= 1, sold_seats - 1, 0)
                        WHERE voyage_id = NEW.voyage_id
                          AND travel_class_id = NEW.ferry_class_id;
                    END IF;
                END;
            ");
        }
    }

    public function down(): void
    {
        //
    }
};