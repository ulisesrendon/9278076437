<?php

namespace Drupal\gv_fanatics_plus_checkout;

/**
 * Interfaz para el gestor de estados de checkout.
 */
interface CheckoutOrderManagerInterface {


  /**
   * Gets the current checkout step ID.
   *
   * Ensures that the user is allowed to access the requested step ID,
   * when given. In case the requested step ID is empty, invalid, or
   * not allowed, a different step ID will be returned.
   *
   * @param string $requested_step_id
   *   (Optional) The requested step ID.
   *
   * @return string
   *   The checkout step ID.
   */
  public function getCheckoutStepId(string $requested_step_id = NULL);
  
  /**
   * Gets all the visible checkout steps.
   * 
   * @return array
   *   The visible checkout steps
   */
  public function getVisibleSteps();

}
