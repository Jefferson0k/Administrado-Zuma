<?php

namespace App\Enums;

enum InvoiceStatus: string
{
  case ACTIVE = "active";
  case PAID = "paid";
  case INACTIVE = "inactive";
  case REPROGRAMED = "reprogramed";
  case EXPIRED = "expired";
  case JUDICIALIZED = "judicialized";
}
