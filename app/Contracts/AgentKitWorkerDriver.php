<?php

namespace App\Contracts;

interface AgentKitWorkerDriver
{
    public function start(): array;

    public function stop(): array;

    public function restart(): array;

    public function status(): array;

    public function logs(int $lines = 50): array;
}
