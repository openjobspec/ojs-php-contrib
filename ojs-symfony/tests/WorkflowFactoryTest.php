<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Symfony\Workflow\WorkflowFactory;
use OpenJobSpec\Client;
use OpenJobSpec\Step;
use PHPUnit\Framework\TestCase;

class WorkflowFactoryTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(WorkflowFactory::class));
    }

    public function testConstructorRequiresClient(): void
    {
        $ref = new \ReflectionClass(WorkflowFactory::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame(Client::class, $params[0]->getType()->getName());
    }

    public function testHasChainMethod(): void
    {
        $ref = new \ReflectionClass(WorkflowFactory::class);
        $this->assertTrue($ref->hasMethod('chain'));

        $method = $ref->getMethod('chain');
        $params = $method->getParameters();
        $this->assertSame('name', $params[0]->getName());
        $this->assertSame('steps', $params[1]->getName());
    }

    public function testHasGroupMethod(): void
    {
        $ref = new \ReflectionClass(WorkflowFactory::class);
        $this->assertTrue($ref->hasMethod('group'));

        $method = $ref->getMethod('group');
        $params = $method->getParameters();
        $this->assertSame('name', $params[0]->getName());
        $this->assertSame('jobs', $params[1]->getName());
    }

    public function testHasBatchMethod(): void
    {
        $ref = new \ReflectionClass(WorkflowFactory::class);
        $this->assertTrue($ref->hasMethod('batch'));

        $method = $ref->getMethod('batch');
        $params = $method->getParameters();
        $this->assertSame('name', $params[0]->getName());
        $this->assertSame('jobs', $params[1]->getName());
        $this->assertSame('onComplete', $params[2]->getName());
        $this->assertTrue($params[2]->allowsNull());
        $this->assertSame('onSuccess', $params[3]->getName());
        $this->assertSame('onFailure', $params[4]->getName());
    }

    public function testHasSubmitMethod(): void
    {
        $ref = new \ReflectionClass(WorkflowFactory::class);
        $this->assertTrue($ref->hasMethod('submit'));
    }

    public function testChainWithFakeTransport(): void
    {
        $transport = new \OpenJobSpec\Testing\FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $factory = new WorkflowFactory($client);

        $result = $factory->chain('test-chain', [
            new Step(type: 'step_a', args: [1]),
            new Step(type: 'step_b', args: [2]),
        ]);

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('chain', $result['type']);
        $this->assertSame('test-chain', $result['name']);
    }

    public function testGroupWithFakeTransport(): void
    {
        $transport = new \OpenJobSpec\Testing\FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $factory = new WorkflowFactory($client);

        $result = $factory->group('test-group', [
            new Step(type: 'worker_a', args: ['x']),
            new Step(type: 'worker_b', args: ['y']),
        ]);

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('group', $result['type']);
        $this->assertSame('test-group', $result['name']);
    }

    public function testBatchWithCallbacks(): void
    {
        $transport = new \OpenJobSpec\Testing\FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $factory = new WorkflowFactory($client);

        $result = $factory->batch(
            'test-batch',
            [new Step(type: 'task_a', args: [])],
            onComplete: new Step(type: 'notify', args: ['done']),
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('batch', $result['type']);
        $this->assertSame('test-batch', $result['name']);
    }

    public function testChainWithArraySteps(): void
    {
        $transport = new \OpenJobSpec\Testing\FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $factory = new WorkflowFactory($client);

        $result = $factory->chain('array-chain', [
            ['type' => 'step_x', 'args' => [10]],
            ['type' => 'step_y', 'args' => [20]],
        ]);

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('chain', $result['type']);
    }

    public function testSubmitRawDefinition(): void
    {
        $transport = new \OpenJobSpec\Testing\FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $factory = new WorkflowFactory($client);

        $result = $factory->submit([
            'type' => 'chain',
            'name' => 'raw-wf',
            'steps' => [['type' => 'do_thing', 'args' => []]],
        ]);

        $this->assertArrayHasKey('id', $result);
    }
}
