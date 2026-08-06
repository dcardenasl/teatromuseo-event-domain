<?php

declare(strict_types=1);

namespace App\Traits\Models;

/**
 * Atomically adjusts an `available_spots` inventory column.
 *
 * Shared by TicketTypeModel and OccurrenceModel. BookingService used to run
 * this exact raw-builder update twice — once inline in beforeStore() (decrement
 * on hold) and once inline in beforeUpdate() (increment on cancel/expire) — with
 * the whole block byte-for-byte duplicated within the class (LAYER-03). It now
 * calls this single model method from both places instead.
 */
trait HasAvailableSpots
{
    /**
     * Adds ($delta > 0) or removes ($delta < 0) spots from `available_spots`
     * in one atomic UPDATE.
     *
     * A decrement only succeeds if enough spots remain (`available_spots >=
     * abs($delta)` is part of the WHERE clause), so two concurrent booking
     * holds racing for the last spot can't both succeed — the second one's
     * UPDATE matches zero rows. An increment (releasing a hold back to
     * inventory) has no such guard and always succeeds as long as the row
     * exists. The caller runs inside BaseCrudService::wrapInTransaction(),
     * so a failed decrement paired with an already-succeeded sibling update
     * (e.g. ticket_types ok, occurrences sold out) is rolled back atomically
     * by the enclosing transaction — this method does not need to.
     *
     * @return bool true if a row was updated; false if a decrement could not
     *               be satisfied (not enough spots) or the row doesn't exist.
     */
    public function adjustAvailableSpots(int $id, int $delta): bool
    {
        if ($delta === 0) {
            return true;
        }

        $builder = $this->builder()->where($this->primaryKey, $id);

        if ($delta < 0) {
            $builder->where('available_spots >=', abs($delta));
        }

        $operator = $delta < 0 ? '-' : '+';
        $amount = abs($delta);

        return (bool) $builder->set('available_spots', "available_spots {$operator} {$amount}", false)->update();
    }
}
