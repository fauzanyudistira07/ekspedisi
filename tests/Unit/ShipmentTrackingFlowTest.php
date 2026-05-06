<?php

namespace Tests\Unit;

use App\Models\Shipment;
use Tests\TestCase;

class ShipmentTrackingFlowTest extends TestCase
{
    public function test_in_transit_can_repeat_before_arrived_at_branch(): void
    {
        $allowedStatuses = Shipment::nextTrackingStatuses(Shipment::STATUS_IN_TRANSIT);

        $this->assertContains(Shipment::STATUS_IN_TRANSIT, $allowedStatuses);
        $this->assertContains(Shipment::STATUS_ARRIVED_AT_BRANCH, $allowedStatuses);
    }
}
