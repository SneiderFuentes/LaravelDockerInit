<?php

namespace Tests\Unit\AppointmentManagement\Domain;

use Core\BoundedContext\AppointmentManagement\Domain\Entities\Appointment;
use Core\BoundedContext\AppointmentManagement\Domain\ValueObjects\AppointmentStatus;
use DateTime;
use PHPUnit\Framework\TestCase;

class AppointmentTest extends TestCase
{
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appointment = Appointment::create(
            '123456',
            'center_a',
            'patient123',
            'John Doe',
            '1234567890',
            new DateTime('2023-12-15 10:00:00'),
            AppointmentStatus::Pending,
            'Test notes'
        );
    }

    public function testCanCreateAppointment(): void
    {
        $this->assertEquals('123456', $this->appointment->id());
        $this->assertEquals('center_a', $this->appointment->centerKey());
        $this->assertEquals('patient123', $this->appointment->patientId());
        $this->assertEquals('John Doe', $this->appointment->patientName());
        $this->assertEquals('1234567890', $this->appointment->patientPhone());
        $this->assertEquals('2023-12-15 10:00:00', $this->appointment->scheduledAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(AppointmentStatus::Pending, $this->appointment->status());
        $this->assertEquals('Test notes', $this->appointment->notes());
    }

    public function testCanConfirmAppointment(): void
    {
        $confirmedAppointment = $this->appointment->confirm();

        $this->assertEquals(AppointmentStatus::Confirmed, $confirmedAppointment->status());
        $this->assertEquals(AppointmentStatus::Pending, $this->appointment->status(), 'Original appointment should be unchanged');
    }

    public function testCanCancelAppointment(): void
    {
        $cancelledAppointment = $this->appointment->cancel();

        $this->assertEquals(AppointmentStatus::Cancelled, $cancelledAppointment->status());
        $this->assertEquals(AppointmentStatus::Pending, $this->appointment->status(), 'Original appointment should be unchanged');
    }

    public function testCannotConfirmAlreadyConfirmedAppointment(): void
    {
        $confirmedAppointment = $this->appointment->confirm();

        $this->expectException(\InvalidArgumentException::class);
        $confirmedAppointment->confirm();
    }

    public function testCannotCancelAlreadyCancelledAppointment(): void
    {
        $cancelledAppointment = $this->appointment->cancel();

        $this->expectException(\InvalidArgumentException::class);
        $cancelledAppointment->cancel();
    }

    public function testCannotHaveEmptyId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Appointment::create(
            '',
            'center_a',
            'patient123',
            'John Doe',
            '1234567890',
            new DateTime(),
            AppointmentStatus::Pending
        );
    }

    public function testCannotHaveEmptyCenterKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Appointment::create(
            '123456',
            '',
            'patient123',
            'John Doe',
            '1234567890',
            new DateTime(),
            AppointmentStatus::Pending
        );
    }

    public function testCannotHaveEmptyPatientId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Appointment::create(
            '123456',
            'center_a',
            '',
            'John Doe',
            '1234567890',
            new DateTime(),
            AppointmentStatus::Pending
        );
    }
}
