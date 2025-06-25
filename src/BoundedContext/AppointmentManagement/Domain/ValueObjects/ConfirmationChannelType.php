<?php

declare(strict_types=1);

namespace Core\BoundedContext\AppointmentManagement\Domain\ValueObjects;

enum ConfirmationChannelType: string
{
    case Whatsapp = 'whatsapp';
    case Voice = 'voz';
}
