<?php
/**
 * Configuration for job polling per job type.
 *
 * Defines timing parameters for polling different job types with appropriate
 * delays and timeouts based on expected processing time.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\api;

/**
 * Configuration class for job polling parameters.
 */
class polling_config {

    // Edit module timing constants (fast operations, ~2-5 seconds).
    /** @var int Initial delay for edit operations (ms). */
    public const EDIT_INITIAL_DELAY_MS = 2000;
    /** @var int Poll interval for edit operations (ms). */
    public const EDIT_POLL_INTERVAL_MS = 2000;
    /** @var int Timeout for edit operations (ms) - 2 minutes. */
    public const EDIT_TIMEOUT_MS = 120000;

    // Generate module timing constants (medium operations, ~5-15 seconds).
    /** @var int Initial delay for generation operations (ms). */
    public const GENERATE_INITIAL_DELAY_MS = 3000;
    /** @var int Poll interval for generation operations (ms). */
    public const GENERATE_POLL_INTERVAL_MS = 2000;
    /** @var int Timeout for generation operations (ms) - 1 minute. */
    public const GENERATE_TIMEOUT_MS = 60000;

    // Course generation timing constants (long operations, ~30-120 seconds).
    /** @var int Initial delay for course generation (ms). */
    public const COURSE_GEN_INITIAL_DELAY_MS = 5000;
    /** @var int Poll interval for course generation (ms). */
    public const COURSE_GEN_POLL_INTERVAL_MS = 3000;
    /** @var int Timeout for course generation (ms) - 5 minutes. */
    public const COURSE_GEN_TIMEOUT_MS = 300000;

    // Milliseconds to seconds conversion factor.
    /** @var float Conversion factor from milliseconds to seconds. */
    private const MS_TO_SECONDS = 1000.0;

    /** @var int Initial delay before first poll in milliseconds. */
    public readonly int $initialdelayms;

    /** @var int Interval between polls in milliseconds. */
    public readonly int $pollintervalms;

    /** @var int Total timeout in milliseconds. */
    public readonly int $timeoutms;

    /**
     * Constructor.
     *
     * @param int $initialdelayms Initial delay before first poll.
     * @param int $pollintervalms Interval between subsequent polls.
     * @param int $timeoutms Total timeout for polling.
     */
    public function __construct(int $initialdelayms, int $pollintervalms, int $timeoutms) {
        $this->initialdelayms = $initialdelayms;
        $this->pollintervalms = $pollintervalms;
        $this->timeoutms = $timeoutms;
    }

    /**
     * Get polling configuration for a specific job type.
     *
     * Job types have different expected processing times:
     * - edit_module: Fast edits to existing content (~2-5 seconds)
     * - generate_module: Creating new modules (~5-15 seconds)
     * - course_gen: Full course generation (~30-120 seconds)
     *
     * @param string $jobtype The job type identifier.
     * @return self The polling configuration for the job type.
     */
    public static function for_job_type(string $jobtype): self {
        return match ($jobtype) {
            'edit_module' => new self(
                self::EDIT_INITIAL_DELAY_MS,
                self::EDIT_POLL_INTERVAL_MS,
                self::EDIT_TIMEOUT_MS
            ),
            'generate_module' => new self(
                self::GENERATE_INITIAL_DELAY_MS,
                self::GENERATE_POLL_INTERVAL_MS,
                self::GENERATE_TIMEOUT_MS
            ),
            'course_gen' => new self(
                self::COURSE_GEN_INITIAL_DELAY_MS,
                self::COURSE_GEN_POLL_INTERVAL_MS,
                self::COURSE_GEN_TIMEOUT_MS
            ),
            'tutor' => new self(2000, 2000, 90000),
            // Default to generate_module timing for unknown types.
            default => new self(
                self::GENERATE_INITIAL_DELAY_MS,
                self::GENERATE_POLL_INTERVAL_MS,
                self::GENERATE_TIMEOUT_MS
            ),
        };
    }

    /**
     * Calculate the maximum number of poll attempts based on config.
     *
     * @return int The maximum number of poll attempts.
     */
    public function get_max_attempts(): int {
        $remainingtime = $this->timeoutms - $this->initialdelayms;

        if ($remainingtime <= 0) {
            return 1;
        }

        return (int) ceil($remainingtime / $this->pollintervalms) + 1;
    }

    /**
     * Convert initial delay to seconds for PHP sleep.
     *
     * @return float The initial delay in seconds.
     */
    public function get_initial_delay_seconds(): float {
        return $this->initialdelayms / self::MS_TO_SECONDS;
    }

    /**
     * Convert poll interval to seconds for PHP sleep.
     *
     * @return float The poll interval in seconds.
     */
    public function get_poll_interval_seconds(): float {
        return $this->pollintervalms / self::MS_TO_SECONDS;
    }

    /**
     * Convert timeout to seconds.
     *
     * @return float The timeout in seconds.
     */
    public function get_timeout_seconds(): float {
        return $this->timeoutms / self::MS_TO_SECONDS;
    }
}
