<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Workflows;

use OpenJobSpec\Client;
use OpenJobSpec\Step;
use OpenJobSpec\Workflow;

/**
 * Laravel-idiomatic fluent builder for OJS workflows.
 *
 * Wraps the SDK Workflow and Step classes with a chainable API:
 *
 *   app(WorkflowBuilder::class)
 *       ->create('order.process')
 *       ->chain()
 *       ->add('payment.charge', ['amount' => 4999])
 *       ->add('inventory.reserve', ['sku' => 'WIDGET-1'])
 *       ->add('email.receipt', ['to' => 'user@example.com'])
 *       ->dispatch();
 */
class WorkflowBuilder
{
    /** @var Step[] */
    private array $steps = [];
    private string $type = 'chain';

    private ?Step $onComplete = null;
    private ?Step $onSuccess = null;
    private ?Step $onFailure = null;

    public function __construct(
        private readonly Client $client,
        private readonly string $name = '',
    ) {}

    /**
     * Create a new builder for a named workflow.
     */
    public function create(string $name): self
    {
        return new self($this->client, $name);
    }

    /**
     * Set workflow type to chain (sequential execution).
     */
    public function chain(): self
    {
        $this->type = 'chain';
        return $this;
    }

    /**
     * Set workflow type to group (parallel execution).
     */
    public function group(): self
    {
        $this->type = 'group';
        return $this;
    }

    /**
     * Set workflow type to batch (parallel with callbacks).
     */
    public function batch(): self
    {
        $this->type = 'batch';
        return $this;
    }

    /**
     * Add a step to the workflow.
     *
     * @param string $type    Job type (e.g., 'email.send')
     * @param array  $args    Job arguments
     * @param array  $options Step options: queue, priority, timeout, meta, retry
     */
    public function add(string $type, array $args = [], array $options = []): self
    {
        $this->steps[] = new Step(
            type: $type,
            args: $args,
            queue: $options['queue'] ?? 'default',
            priority: $options['priority'] ?? null,
            timeout: $options['timeout'] ?? null,
            meta: $options['meta'] ?? [],
        );
        return $this;
    }

    /**
     * Set the on_complete callback (fires when all jobs finish, regardless of outcome).
     * Only used with batch workflows.
     */
    public function onComplete(string $type, array $args = [], array $options = []): self
    {
        $this->onComplete = new Step(
            type: $type,
            args: $args,
            queue: $options['queue'] ?? 'default',
            priority: $options['priority'] ?? null,
            timeout: $options['timeout'] ?? null,
            meta: $options['meta'] ?? [],
        );
        return $this;
    }

    /**
     * Set the on_success callback (fires when all jobs succeed).
     * Only used with batch workflows.
     */
    public function onSuccess(string $type, array $args = [], array $options = []): self
    {
        $this->onSuccess = new Step(
            type: $type,
            args: $args,
            queue: $options['queue'] ?? 'default',
            priority: $options['priority'] ?? null,
            timeout: $options['timeout'] ?? null,
            meta: $options['meta'] ?? [],
        );
        return $this;
    }

    /**
     * Set the on_failure callback (fires when any job fails).
     * Only used with batch workflows.
     */
    public function onFailure(string $type, array $args = [], array $options = []): self
    {
        $this->onFailure = new Step(
            type: $type,
            args: $args,
            queue: $options['queue'] ?? 'default',
            priority: $options['priority'] ?? null,
            timeout: $options['timeout'] ?? null,
            meta: $options['meta'] ?? [],
        );
        return $this;
    }

    /**
     * Build and submit the workflow to the OJS backend.
     *
     * @return array The workflow response from the backend
     * @throws \LogicException If no steps have been added
     */
    public function dispatch(): array
    {
        if (empty($this->steps)) {
            throw new \LogicException('Cannot dispatch a workflow with no steps.');
        }

        if ($this->name === '') {
            throw new \LogicException('Workflow name is required. Use create() to set it.');
        }

        $definition = match ($this->type) {
            'chain' => Workflow::chain($this->name, $this->steps),
            'group' => Workflow::group($this->name, $this->steps),
            'batch' => Workflow::batch(
                $this->name,
                $this->steps,
                onComplete: $this->onComplete,
                onSuccess: $this->onSuccess,
                onFailure: $this->onFailure,
            ),
            default => throw new \LogicException("Unknown workflow type: {$this->type}"),
        };

        return $this->client->workflow($definition);
    }

    /**
     * Get the configured workflow type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the configured steps.
     *
     * @return Step[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Get the workflow name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
