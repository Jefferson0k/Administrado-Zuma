<?php

namespace App\Enums;

enum MovementStatus: string
{
  case VALID = "valid";
  case INVALID = "invalid";
  case PENDING = "pending";
  case REJECTED = "rejected";
}
