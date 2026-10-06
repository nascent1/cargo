<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared("
                -- 1. Helper function for sold seats
                CREATE OR REPLACE FUNCTION fn_recalculate_sold_seats(p_voyage_id BIGINT, p_class_id BIGINT) RETURNS VOID AS $$
                DECLARE
                    v_sold_individual INT;
                    v_sold_whole_room INT;
                BEGIN
                    -- Individual tickets (not whole room)
                    SELECT COALESCE(COUNT(*), 0) INTO v_sold_individual
                    FROM tickets bi
                    WHERE bi.voyage_id = p_voyage_id 
                      AND bi.ferry_class_id = p_class_id
                      AND (bi.is_whole_room = FALSE OR bi.is_whole_room IS NULL)
                      AND bi.status IN ('confirmed', 'held', 'checked_in', 'boarded')
                      AND (
                          bi.final_fare > 0 OR 
                          EXISTS (
                              SELECT 1 FROM passenger_types pt 
                              WHERE pt.id = bi.passenger_type_id 
                                AND pt.code != 'INFANT' 
                                AND pt.name != 'Infant'
                          )
                      );

                    -- Whole room tickets (count room capacity once per room)
                    SELECT COALESCE(SUM(r.capacity), 0) INTO v_sold_whole_room
                    FROM (
                        SELECT DISTINCT room_id
                        FROM tickets
                        WHERE voyage_id = p_voyage_id 
                          AND ferry_class_id = p_class_id
                          AND is_whole_room = TRUE
                          AND status IN ('confirmed', 'held', 'checked_in', 'boarded')
                    ) bi
                    JOIN ferry_class_rooms r ON bi.room_id = r.id;

                    UPDATE seat_inventories
                    SET sold_seats = v_sold_individual + v_sold_whole_room
                    WHERE voyage_id = p_voyage_id AND travel_class_id = p_class_id;
                END;
                $$ LANGUAGE plpgsql;

                -- 2. Helper function for held seats
                CREATE OR REPLACE FUNCTION fn_recalculate_held_seats(p_voyage_id BIGINT, p_class_id BIGINT) RETURNS VOID AS $$
                DECLARE
                    v_held_individual INT;
                    v_held_whole_room INT;
                BEGIN
                    -- Individual locks
                    SELECT COALESCE(SUM(quantity), 0) INTO v_held_individual
                    FROM booking_locks
                    WHERE voyage_id = p_voyage_id 
                      AND ferry_class_id = p_class_id
                      AND (is_whole_room = FALSE OR is_whole_room IS NULL)
                      AND expires_at > NOW();

                    -- Whole room locks
                    SELECT COALESCE(SUM(r.capacity), 0) INTO v_held_whole_room
                    FROM (
                        SELECT DISTINCT room_id
                        FROM booking_locks
                        WHERE voyage_id = p_voyage_id 
                          AND ferry_class_id = p_class_id
                          AND is_whole_room = TRUE
                          AND expires_at > NOW()
                    ) bl
                    JOIN ferry_class_rooms r ON bl.room_id = r.id;

                    UPDATE seat_inventories
                    SET held_seats = v_held_individual + v_held_whole_room
                    WHERE voyage_id = p_voyage_id AND travel_class_id = p_class_id;
                END;
                $$ LANGUAGE plpgsql;

                -- 3. Consolidated Triggers
                CREATE OR REPLACE FUNCTION fn_tr_tickets_after_change() RETURNS TRIGGER AS $$
                BEGIN
                    IF TG_OP = 'UPDATE' THEN
                        IF OLD.voyage_id != NEW.voyage_id OR OLD.ferry_class_id != NEW.ferry_class_id THEN
                            PERFORM fn_recalculate_sold_seats(OLD.voyage_id, OLD.ferry_class_id);
                        END IF;
                        PERFORM fn_recalculate_sold_seats(NEW.voyage_id, NEW.ferry_class_id);
                        RETURN NEW;
                    ELSIF TG_OP = 'DELETE' THEN
                        PERFORM fn_recalculate_sold_seats(OLD.voyage_id, OLD.ferry_class_id);
                        RETURN OLD;
                    ELSE
                        PERFORM fn_recalculate_sold_seats(NEW.voyage_id, NEW.ferry_class_id);
                        RETURN NEW;
                    END IF;
                END;
                $$ LANGUAGE plpgsql;

                CREATE OR REPLACE FUNCTION fn_tr_booking_locks_after_change() RETURNS TRIGGER AS $$
                BEGIN
                    IF TG_OP = 'UPDATE' THEN
                        IF OLD.voyage_id != NEW.voyage_id OR OLD.ferry_class_id != NEW.ferry_class_id THEN
                            PERFORM fn_recalculate_held_seats(OLD.voyage_id, OLD.ferry_class_id);
                        END IF;
                        PERFORM fn_recalculate_held_seats(NEW.voyage_id, NEW.ferry_class_id);
                        RETURN NEW;
                    ELSIF TG_OP = 'DELETE' THEN
                        PERFORM fn_recalculate_held_seats(OLD.voyage_id, OLD.ferry_class_id);
                        RETURN OLD;
                    ELSE
                        PERFORM fn_recalculate_held_seats(NEW.voyage_id, NEW.ferry_class_id);
                        RETURN NEW;
                    END IF;
                END;
                $$ LANGUAGE plpgsql;

                -- Drop old triggers
                DROP TRIGGER IF EXISTS tr_tickets_after_insert ON tickets;
                DROP TRIGGER IF EXISTS tr_tickets_after_update ON tickets;
                DROP TRIGGER IF EXISTS tr_tickets_after_delete ON tickets;
                
                DROP TRIGGER IF EXISTS tr_booking_locks_after_insert ON booking_locks;
                DROP TRIGGER IF EXISTS tr_booking_locks_after_update ON booking_locks;
                DROP TRIGGER IF EXISTS tr_booking_locks_after_delete ON booking_locks;

                -- Create new triggers
                CREATE TRIGGER tr_tickets_after_change
                AFTER INSERT OR UPDATE OR DELETE ON tickets
                FOR EACH ROW EXECUTE FUNCTION fn_tr_tickets_after_change();

                CREATE TRIGGER tr_booking_locks_after_change
                AFTER INSERT OR UPDATE OR DELETE ON booking_locks
                FOR EACH ROW EXECUTE FUNCTION fn_tr_booking_locks_after_change();
            ");
        }
    }

    public function down(): void
    {
        // Reverting this complex logic is typically done by dropping the new triggers and reverting to the original migration.
    }
};
