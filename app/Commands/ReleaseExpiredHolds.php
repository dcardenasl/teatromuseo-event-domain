<?php

declare(strict_types=1);

namespace App\Commands;

use App\Models\BookingModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class ReleaseExpiredHolds extends BaseCommand
{
    protected $group = 'Events';
    protected $name = 'bookings:release-expired';
    protected $description = 'Release expired booking holds and return spots back to inventory.';

    protected $usage = 'bookings:release-expired';

    /**
     * @param array<int, string> $params
     */
    public function run(array $params): int
    {
        $bookingModel = model(BookingModel::class);

        $now = date('Y-m-d H:i:s');
        $expiredBookings = $bookingModel
            ->where('status', 'pending')
            ->where('reserved_until <', $now)
            ->findAll();

        if (empty($expiredBookings)) {
            CLI::write('No expired holds found.', 'green');
            return 0;
        }

        /** @var \App\Services\Events\BookingService $bookingService */
        $bookingService = Services::bookingService();
        $dtoFactory = Services::requestDtoFactory();
        $count = 0;

        foreach ($expiredBookings as $booking) {
            try {
                $dto = $dtoFactory->make(\App\DTO\Request\Events\BookingUpdateRequestDTO::class, [
                    'status' => 'expired',
                ]);

                $bookingService->update((int) $booking->id, $dto);
                CLI::write("Successfully released expired hold for Booking ID: {$booking->id} (UUID: {$booking->uuid}).", 'yellow');
                $count++;
            } catch (\Exception $e) {
                CLI::error("Failed to release Booking ID {$booking->id}: " . $e->getMessage());
            }
        }

        CLI::write("Release cron complete. Released {$count} holds.", 'green');
        return 0;
    }
}
