<?php

namespace App\Traits;

/**
 * Este trait se encarga de convertir un porcentaje a un decimal
 * y viceversa.
 * 
 * @author: Apros  global
 * @version: 1.0.0
 * @since: 2025-05-26
 * @package: App\Traits
 */
trait ConvertsPercent
{
  /**
   * Convierte un porcentaje a un decimal
   *
   * @param float| int $percentage
   * @return float
   * @throws \InvalidArgumentException
   * @example:
   *  $this->convertToDecimalPercent(100); // 1
   *  $this->convertToDecimalPercent(50); // 0.5
   *  $this->convertToDecimalPercent(0); // 0
   *  $this->convertToDecimalPercent(1000); // 10
   *  $this->convertToDecimalPercent(-10); // throw \InvalidArgumentException
   *  $this->convertToDecimalPercent(110); // throw \InvalidArgumentException
   */
  public function convertToDecimalPercent(float| int $percentage): float
  {
    if ($percentage < 0 || $percentage > 100) {
      throw new \InvalidArgumentException("El porcentaje debe estar entre 0 y 100");
    }

    return $percentage / 100;
  }

  /**
   * Convierte un decimal a un porcentaje
   *
   * @param float| int $decimal
   * @return float| int
   * @throws \InvalidArgumentException
   * @example:
   *  $this->convertToPercentage(1); // 100
   *  $this->convertToPercentage(0.5); // 50
   *  $this->convertToPercentage(0); // 0
   *  $this->convertToPercentage(10); // 1000
   *  $this->convertToPercentage(-1); // throw \InvalidArgumentException
   *  $this->convertToPercentage(1.1); // throw \InvalidArgumentException
   */
  public function convertToPercentage(float| int $decimal): float | int
  {
    if ($decimal < 0 || $decimal > 1) {
      throw new \InvalidArgumentException("El decimal debe estar entre 0 y 1");
    }

    return $decimal * 100;
  }
}
