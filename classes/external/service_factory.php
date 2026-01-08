<?php
/**
 * Factory for creating service instances in external API classes.
 *
 * Centralizes service instantiation to allow for easier testing and
 * consistent service configuration across all API endpoints.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\external;

use local_dixeo\api\client;
use local_dixeo\service\job_service;
use local_dixeo\service\module_generation_service;

/**
 * Factory class for creating service instances.
 *
 * Provides focused service instances for external API endpoints.
 * Supports test mock injection for each service type independently.
 */
class service_factory {

    /** @var job_service|null Mock job service instance for unit testing. */
    private static ?job_service $testjobservice = null;

    /** @var module_generation_service|null Mock module generation service for unit testing. */
    private static ?module_generation_service $testmodulegenerationservice = null;

    /** @var client|null Mock client instance for unit testing. */
    private static ?client $testclient = null;

    /**
     * Get a job_service instance.
     *
     * Returns a fresh instance unless a test instance has been set.
     *
     * @return job_service The service instance.
     */
    public static function get_job_service(): job_service {
        if (self::$testjobservice !== null) {
            return self::$testjobservice;
        }

        return new job_service();
    }

    /**
     * Get a module_generation_service instance.
     *
     * Returns a fresh instance unless a test instance has been set.
     *
     * @return module_generation_service The service instance.
     */
    public static function get_module_generation_service(): module_generation_service {
        if (self::$testmodulegenerationservice !== null) {
            return self::$testmodulegenerationservice;
        }

        return new module_generation_service();
    }

    /**
     * Get a client instance.
     *
     * Returns a fresh instance unless a test instance has been set.
     * Use for direct API calls like get_module_types().
     *
     * @return client The client instance.
     */
    public static function get_client(): client {
        if (self::$testclient !== null) {
            return self::$testclient;
        }

        return new client();
    }

    /**
     * Set a test job service instance.
     *
     * Use this in unit tests to inject mock services.
     *
     * @param job_service|null $service The test service, or null to clear.
     */
    public static function set_test_job_service(?job_service $service): void {
        self::$testjobservice = $service;
    }

    /**
     * Set a test module generation service instance.
     *
     * Use this in unit tests to inject mock services.
     *
     * @param module_generation_service|null $service The test service, or null to clear.
     */
    public static function set_test_module_generation_service(?module_generation_service $service): void {
        self::$testmodulegenerationservice = $service;
    }

    /**
     * Set a test client instance.
     *
     * Use this in unit tests to inject mock clients.
     *
     * @param client|null $client The test client, or null to clear.
     */
    public static function set_test_client(?client $client): void {
        self::$testclient = $client;
    }

    /**
     * Reset the factory to default state.
     *
     * Should be called in test tearDown methods.
     */
    public static function reset(): void {
        self::$testjobservice = null;
        self::$testmodulegenerationservice = null;
        self::$testclient = null;
    }
}
