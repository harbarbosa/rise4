<?php

namespace OrdemServico\Tests\Unit;

use OrdemServico\Libraries\EuGestorClient;
use OrdemServico\Libraries\EuGestorSettings;
use PHPUnit\Framework\TestCase;

final class EuGestorClientTest extends TestCase
{
    public function testPaginationCollectsAllPages(): void
    {
        $settings = new class extends EuGestorSettings {
            public function getAccessToken(): string { return 'test-token'; }
            public function getTokenExpiresAt(): int { return time() + 600; }
        };
        $calls = [];
        $transport = function (string $method, string $url, array $headers, array $payload) use (&$calls): array {
            $calls[] = [$method, $url, $headers, $payload];
            $page = (int)($payload['pagina'] ?? 0);
            if ($page === 1) { return ['status' => 200, 'data' => ['items' => array_fill(0, 100, ['ordemServicoId' => 1]), 'total' => 101]]; }
            return ['status' => 200, 'data' => ['items' => [['ordemServicoId' => 101]], 'total' => 101]];
        };

        $orders = (new EuGestorClient($settings, $transport))->listOpenOrders();
        self::assertCount(101, $orders);
        self::assertCount(2, $calls);
        self::assertSame('Bearer test-token', $calls[0][2][1]);
    }
}
