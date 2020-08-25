<?php

namespace Tasks;

abstract class Manager implements TaskInterface
{
    protected $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    abstract public function execute();
}
