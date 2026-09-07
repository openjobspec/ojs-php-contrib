<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Workflows\WorkflowBuilder;
use OpenJobSpec\Client;
use OpenJobSpec\Step;
use PHPUnit\Framework\TestCase;

class WorkflowBuilderTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(WorkflowBuilder::class));
    }

    public function testDefaultTypeIsChain(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $this->assertSame('chain', $builder->getType());
    }

    public function testChainSetsType(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $builder->group()->chain();
        $this->assertSame('chain', $builder->getType());
    }

    public function testGroupSetsType(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $builder->group();
        $this->assertSame('group', $builder->getType());
    }

    public function testBatchSetsType(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $builder->batch();
        $this->assertSame('batch', $builder->getType());
    }

    public function testAddStep(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $builder->add('email.send', ['to' => 'user@example.com']);

        $steps = $builder->getSteps();
        $this->assertCount(1, $steps);
        $this->assertInstanceOf(Step::class, $steps[0]);
        $this->assertSame('email.send', $steps[0]->type);
        $this->assertSame(['to' => 'user@example.com'], $steps[0]->args);
    }

    public function testAddMultipleSteps(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $builder
            ->add('step.one', ['a' => 1])
            ->add('step.two', ['b' => 2])
            ->add('step.three', ['c' => 3]);

        $this->assertCount(3, $builder->getSteps());
    }

    public function testAddStepWithOptions(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $builder->add('email.send', ['to' => 'user@example.com'], [
            'queue' => 'emails',
            'priority' => 5,
            'timeout' => 60,
        ]);

        $step = $builder->getSteps()[0];
        $this->assertSame('emails', $step->queue);
        $this->assertSame(5, $step->priority);
        $this->assertSame(60, $step->timeout);
    }

    public function testCreateReturnsNewBuilder(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client);
        $newBuilder = $builder->create('my.workflow');

        $this->assertNotSame($builder, $newBuilder);
        $this->assertSame('my.workflow', $newBuilder->getName());
    }

    public function testFluentChaining(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $result = $builder->chain()->add('step.one')->add('step.two');

        $this->assertSame($builder, $result);
    }

    public function testDispatchThrowsOnEmptySteps(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot dispatch a workflow with no steps');
        $builder->dispatch();
    }

    public function testDispatchThrowsOnEmptyName(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, '');
        $builder->add('step.one');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Workflow name is required');
        $builder->dispatch();
    }

    public function testDispatchChainWorkflow(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('workflow')
            ->with($this->callback(function (array $definition) {
                return $definition['type'] === 'chain'
                    && $definition['name'] === 'test.chain'
                    && count($definition['steps']) === 2;
            }))
            ->willReturn(['id' => 'wf-123', 'status' => 'running']);

        $builder = new WorkflowBuilder($client, 'test.chain');
        $result = $builder->chain()
            ->add('step.one', ['a' => 1])
            ->add('step.two', ['b' => 2])
            ->dispatch();

        $this->assertSame('wf-123', $result['id']);
    }

    public function testDispatchGroupWorkflow(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('workflow')
            ->with($this->callback(function (array $definition) {
                return $definition['type'] === 'group'
                    && $definition['name'] === 'test.group'
                    && count($definition['jobs']) === 2;
            }))
            ->willReturn(['id' => 'wf-456']);

        $builder = new WorkflowBuilder($client, 'test.group');
        $result = $builder->group()
            ->add('step.one')
            ->add('step.two')
            ->dispatch();

        $this->assertSame('wf-456', $result['id']);
    }

    public function testDispatchBatchWorkflowWithCallbacks(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('workflow')
            ->with($this->callback(function (array $definition) {
                return $definition['type'] === 'batch'
                    && $definition['name'] === 'test.batch'
                    && count($definition['jobs']) === 2
                    && isset($definition['callbacks']['on_complete'])
                    && isset($definition['callbacks']['on_success'])
                    && isset($definition['callbacks']['on_failure']);
            }))
            ->willReturn(['id' => 'wf-789']);

        $builder = new WorkflowBuilder($client, 'test.batch');
        $result = $builder->batch()
            ->add('step.one')
            ->add('step.two')
            ->onComplete('notify.complete', ['channel' => 'slack'])
            ->onSuccess('notify.success')
            ->onFailure('notify.failure')
            ->dispatch();

        $this->assertSame('wf-789', $result['id']);
    }

    public function testOnCompleteReturnsBuilder(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $result = $builder->onComplete('notify.done');
        $this->assertSame($builder, $result);
    }

    public function testOnSuccessReturnsBuilder(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $result = $builder->onSuccess('notify.success');
        $this->assertSame($builder, $result);
    }

    public function testOnFailureReturnsBuilder(): void
    {
        $client = $this->createMock(Client::class);
        $builder = new WorkflowBuilder($client, 'test');
        $result = $builder->onFailure('notify.failure');
        $this->assertSame($builder, $result);
    }
}
