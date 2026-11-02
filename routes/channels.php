<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;
use Modules\Address\Broadcasting\AddressChannel;

Broadcast::channel('address.{addressId}', AddressChannel::class);
