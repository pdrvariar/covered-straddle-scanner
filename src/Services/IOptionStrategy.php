<?php

namespace App\Services;

interface IOptionStrategy {
    public function getName(): string;
    public function execute(string $symbol, string $expirationDate, float $selicAnnual, array $filters = [], bool $includePayoffData = false): ?array;
}
