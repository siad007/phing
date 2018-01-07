<?php

/**
 * Interface used to bridge to the actual Main class.
 */
interface PhingMain
{
    /**
     * Start Phing.
     *
     * @param array $args command line args
     * @param Properties $additionalUserProperties properties to set beyond those that
     *        may be specified on the args list
     */
    public function startPhing(array $args, Properties $additionalUserProperties = null): void;
}
