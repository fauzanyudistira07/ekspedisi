<?php

namespace Tests\Unit;

use App\Models\Shipment;
use Tests\TestCase;

class ShipmentExceptionFlowTest extends TestCase
{
    public function test_out_for_delivery_can_go_to_exception_statuses(): void
    {
        $allowedStatuses = Shipment::nextTrackingStatuses(Shipment::STATUS_OUT_FOR_DELIVERY);

        $this->assertContains(Shipment::STATUS_FAILED_DELIVERY, $allowedStatuses);
        $this->assertContains(Shipment::STATUS_RETURNED_TO_SENDER, $allowedStatuses);
        $this->assertContains(Shipment::STATUS_EXCEPTION_HOLD, $allowedStatuses);
    }
}
