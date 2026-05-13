<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Levée quand un patient tente de réserver un créneau dont le statut
 * n'est plus OPEN au moment où la transaction prend le verrou.
 */
final class SlotAlreadyBookedException extends \RuntimeException
{
    public function __construct(int $slotId)
    {
        parent::__construct(sprintf('Le créneau %d n\'est plus disponible.', $slotId));
    }
}
