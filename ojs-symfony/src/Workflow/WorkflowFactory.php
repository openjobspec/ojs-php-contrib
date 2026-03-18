<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Workflow;

use OpenJobSpec\Client;
use OpenJobSpec\Step;
use OpenJobSpec\Workflow;

/**
 * Symfony service for building and dispatching OJS workflows.
 *
 * Wraps the SDK's static Workflow builder with a dependency-injected service
 * that normalizes step definitions and submits workflows via the OJS Client.
 */
class WorkflowFactory
{
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * Create and submit a chain (sequential) workflow.
     *
     * @param string $name  Workflow name
     * @param array  $steps Ordered list of Step objects or associative arrays
     * @return array Server response with workflow ID and state
     */
    public function chain(string $name, array $steps): array
    {
        $normalizedSteps = array_map(
            fn(Step|array $s) => $s instanceof Step ? $s : Step::fromArray($s),
            $steps,
        );

        return $this->client->workflow(Workflow::chain($name, $normalizedSteps));
    }

    /**
     * Create and submit a group (parallel) workflow.
     *
     * @param string $name Workflow name
     * @param array  $jobs Jobs to run in parallel as Step objects or arrays
     * @return array Server response with workflow ID and state
     */
    public function group(string $name, array $jobs): array
    {
        $normalizedJobs = array_map(
            fn(Step|array $s) => $s instanceof Step ? $s : Step::fromArray($s),
            $jobs,
        );

        return $this->client->workflow(Workflow::group($name, $normalizedJobs));
    }

    /**
     * Create and submit a batch workflow with optional callbacks.
     *
     * @param string    $name       Workflow name
     * @param array     $jobs       Jobs to run in parallel
     * @param Step|null $onComplete Callback when all jobs finish
     * @param Step|null $onSuccess  Callback when all jobs succeed
     * @param Step|null $onFailure  Callback when any job fails
     * @return array Server response with workflow ID and state
     */
    public function batch(
        string $name,
        array $jobs,
        ?Step $onComplete = null,
        ?Step $onSuccess = null,
        ?Step $onFailure = null,
    ): array {
        $normalizedJobs = array_map(
            fn(Step|array $s) => $s instanceof Step ? $s : Step::fromArray($s),
            $jobs,
        );

        return $this->client->workflow(
            Workflow::batch($name, $normalizedJobs, $onComplete, $onSuccess, $onFailure),
        );
    }

    /**
     * Submit a raw workflow definition array.
     *
     * @param array $definition Pre-built workflow definition
     * @return array Server response
     */
    public function submit(array $definition): array
    {
        return $this->client->workflow($definition);
    }
}
