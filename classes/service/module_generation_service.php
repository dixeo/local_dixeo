<?php
/**
 * Service for AI-powered module generation and editing.
 *
 * Handles module generation and regeneration (editing) operations,
 * building appropriate context and submitting jobs to the Dixeo API.
 *
 * @package    local_dixeo
 * @copyright  2025 Edunao SAS (contact@edunao.com)
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo\service;

use local_dixeo\api\exception\api_exception;
use local_dixeo\context\context_builder_factory;
use local_dixeo\context\course_context_builder;
use local_dixeo\dto\operation_result;

/**
 * Service for module generation and editing operations.
 */
class module_generation_service {

    /** @var string API endpoint for module generation. */
    private const GENERATE_ENDPOINT = '/v1/modules/generate';

    /** @var string API endpoint for module regeneration (editing). */
    private const REGENERATE_ENDPOINT = '/v1/modules/regenerate';

    /** @var string Job type for generation operations. */
    private const JOB_TYPE_GENERATE = 'generate_module';

    /** @var string Job type for edit operations. */
    private const JOB_TYPE_EDIT = 'edit_module';

    /** @var array Module types that require assessment context (full content). */
    private const ASSESSMENT_MODULES = ['quiz', 'glossary'];

    /** @var job_service Job management service. */
    private job_service $jobService;

    /** @var string|null The namespace for API requests. */
    private ?string $namespace;

    /**
     * Constructor.
     *
     * @param job_service|null $jobService Optional job service.
     * @param string|null $namespace Optional namespace override.
     */
    public function __construct(
        ?job_service $jobService = null,
        ?string $namespace = null
    ) {
        $this->jobService = $jobService ?? new job_service();
        $this->namespace = $namespace ?? $this->get_configured_namespace();
    }

    /**
     * Submit a module generation job for a course (recommended).
     *
     * Builds appropriate context internally based on module type:
     * - Teaching modules (page, label): tiered context by section proximity
     * - Assessment modules (quiz, glossary): full content everywhere
     *
     * @param string $moduletype The module type (page, label, quiz, glossary).
     * @param string $instructions Instructions for the AI.
     * @param int $courseid The course ID.
     * @param int|null $sectionnumber Target section number.
     * @return operation_result Pending operation result with job_id.
     * @throws api_exception If the API request fails.
     */
    public function submit_generate_job_for_course(
        string $moduletype,
        string $instructions,
        int $courseid,
        ?int $sectionnumber = null
    ): operation_result {
        $mode = $this->get_context_mode($moduletype);
        $context = context_builder_factory::buildCourseContext($courseid, $sectionnumber, $mode);

        return $this->submit_generate_job($moduletype, $instructions, $context);
    }

    /**
     * Submit a module generation job without polling.
     *
     * Returns immediately with job_id. Use job_service::get_job_status() to poll.
     * Prefer submit_generate_job_for_course() which builds context automatically.
     *
     * @param string $moduletype The module type (page, label, quiz, glossary).
     * @param string $instructions Instructions for the AI.
     * @param string $context Course/section context in markdown format.
     * @return operation_result Pending operation result with job_id.
     * @throws api_exception If the API request fails.
     */
    public function submit_generate_job(string $moduletype, string $instructions, string $context): operation_result {
        $payload = $this->build_payload($moduletype, $instructions, $context);

        return $this->jobService->submit_job(self::GENERATE_ENDPOINT, $payload);
    }

    /**
     * Generate a new module using AI (blocking).
     *
     * Submits a generation request and polls for completion.
     *
     * @param string $moduletype The module type (page, label, quiz, glossary).
     * @param string $instructions Instructions for the AI.
     * @param string $context Course/section context in markdown format.
     * @return operation_result The operation result (completed or pending).
     * @throws api_exception If an API error occurs.
     */
    public function generate_module(string $moduletype, string $instructions, string $context): operation_result {
        $payload = $this->build_payload($moduletype, $instructions, $context);

        return $this->jobService->submit_and_wait(
            self::GENERATE_ENDPOINT,
            $payload,
            self::JOB_TYPE_GENERATE
        );
    }

    /**
     * Regenerate (edit) an existing module using AI.
     *
     * Submits an edit request and polls for completion.
     *
     * @param string $moduletype The module type (page, label).
     * @param string $instructions Instructions for the AI.
     * @param string $context Course/section context including current content.
     * @return operation_result The operation result (completed or pending).
     * @throws api_exception If an API error occurs.
     */
    public function regenerate_module(string $moduletype, string $instructions, string $context): operation_result {
        $payload = $this->build_payload($moduletype, $instructions, $context);

        return $this->jobService->submit_and_wait(
            self::REGENERATE_ENDPOINT,
            $payload,
            self::JOB_TYPE_EDIT
        );
    }

    /**
     * Edit a module by course module ID.
     *
     * Simplified interface that resolves module type and context internally.
     * Catches exceptions and returns failed operation_result instead of throwing.
     *
     * @param int $cmid The course module ID.
     * @param string $instructions Instructions for the AI.
     * @return operation_result The operation result (completed, pending, or failed).
     */
    public function edit_module(int $cmid, string $instructions): operation_result {
        try {
            $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
            $moduletype = $cm->modname;
            $context = context_builder_factory::buildModuleEditContext($cmid);

            return $this->regenerate_module($moduletype, $instructions, $context);

        } catch (api_exception $e) {
            return operation_result::failed($e->getMessage(), 'api_error');
        } catch (\dml_exception $e) {
            return operation_result::failed(
                'Module not found or database error: ' . $e->getMessage(),
                'module_not_found'
            );
        } catch (\Throwable $e) {
            return operation_result::failed(
                'Unexpected error: ' . $e->getMessage(),
                'unexpected_error'
            );
        }
    }

    /**
     * Set a custom namespace for subsequent requests.
     *
     * @param string|null $namespace The namespace to use.
     * @return self For method chaining.
     */
    public function set_namespace(?string $namespace): self {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Get the current namespace.
     *
     * @return string|null The namespace.
     */
    public function get_namespace(): ?string {
        return $this->namespace;
    }

    /**
     * Get the underlying job service.
     *
     * @return job_service The job service.
     */
    public function get_job_service(): job_service {
        return $this->jobService;
    }

    /**
     * Build the API request payload.
     *
     * @param string $moduletype The module type.
     * @param string $instructions The AI instructions.
     * @param string $context The context markdown.
     * @return array The request payload.
     * @throws \invalid_parameter_exception If required parameters are empty.
     */
    private function build_payload(string $moduletype, string $instructions, string $context): array {
        if (empty(trim($moduletype))) {
            throw new \invalid_parameter_exception('Module type is required');
        }
        if (empty(trim($instructions))) {
            throw new \invalid_parameter_exception('Instructions are required');
        }

        $payload = [
            'module_type' => $moduletype,
            'instructions' => $instructions,
            'context' => $context,
        ];

        if ($this->namespace !== null) {
            $payload['namespace'] = $this->namespace;
        }

        return $payload;
    }

    /**
     * Get the configured namespace.
     *
     * @return string|null The namespace, or null if not configured.
     */
    private function get_configured_namespace(): ?string {
        $namespace = get_config('local_dixeo', 'namespace');

        if (!empty($namespace)) {
            return $namespace;
        }

        return local_dixeo_get_default_namespace();
    }

    /**
     * Determine the appropriate context mode for a module type.
     *
     * @param string $moduletype The module type.
     * @return string The context mode (MODE_TEACHING or MODE_ASSESSMENT).
     */
    private function get_context_mode(string $moduletype): string {
        if (in_array($moduletype, self::ASSESSMENT_MODULES, true)) {
            return course_context_builder::MODE_ASSESSMENT;
        }

        return course_context_builder::MODE_TEACHING;
    }
}
