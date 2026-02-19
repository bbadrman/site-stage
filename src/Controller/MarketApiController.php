<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class MarketApiController extends AbstractController
{
    public function __construct(private HttpClientInterface $http) {}

    private const TZ = 'Africa/Casablanca';

    private function jsonOk(mixed $data, int $status = 200): JsonResponse
    {
        $res = $this->json($data, $status);

        // CORS (good for dev)
        $res->headers->set('Access-Control-Allow-Origin', '*');
        $res->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $res->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        // no cache
        $res->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $res;
    }

    #[Route('/api/widgets/{any}', name: 'api_widgets_preflight', requirements: ['any' => '.+'], methods: ['OPTIONS'])]
    public function preflight(): JsonResponse
    {
        return $this->jsonOk(['ok' => true]);
    }

    /**
     * Fetch chart meta + spark closes from Yahoo v8 chart.
     * range/interval configurable.
     */
    private function chartPack(string $symbol, string $range = '1d', string $interval = '5m', int $sparkMax = 40): ?array
    {
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/" . urlencode($symbol)
            . "?range=" . urlencode($range)
            . "&interval=" . urlencode($interval);

        try {
            $json = $this->http->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36',
                    'Accept' => 'application/json',
                ],
            ])->toArray(false);
        } catch (TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface $e) {
            return null;
        }

        $res = $json['chart']['result'][0] ?? null;
        if (!$res) return null;

        $meta = $res['meta'] ?? null;
        $closes = $res['indicators']['quote'][0]['close'] ?? [];

        $spark = array_values(array_filter($closes, fn($v) => $v !== null));
        if ($sparkMax > 0 && count($spark) > $sparkMax) {
            $spark = array_slice($spark, -$sparkMax);
        }

        return ['meta' => $meta, 'spark' => $spark];
    }

    // ==============================
    // 1) Quote + Chart (Big Widget)
    // GET /api/widgets/quote/NVDA?range=1mo
    // ==============================
    #[Route('/api/widgets/quote/{symbol}', name: 'api_widgets_quote', methods: ['GET'])]
    public function quote(string $symbol, Request $request): JsonResponse
    {
        $range = (string) $request->query->get('range', '1mo'); // 1d,5d,1mo,6mo,ytd,1y,5y,max
        $interval = $this->intervalFromRange($range);

        $pack = $this->chartPack($symbol, $range, $interval, 0); // 0 = no spark limit here, we use series below
        if (!$pack || empty($pack['meta'])) {
            return $this->jsonOk(['error' => 'No data'], 404);
        }

        // For big chart: request again but keep closes as full series (same pack already has closes, but sparkMax=0 might empty slice logic)
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/" . urlencode($symbol)
            . "?range=" . urlencode($range)
            . "&interval=" . urlencode($interval);

        try {
            $json = $this->http->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36',
                    'Accept' => 'application/json',
                ],
            ])->toArray(false);
        } catch (TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface $e) {
            return $this->jsonOk(['error' => 'Yahoo request failed', 'details' => $e->getMessage()], 502);
        }

        $result = $json['chart']['result'][0] ?? null;
        if (!$result) {
            $err = $json['chart']['error']['description'] ?? 'No data';
            return $this->jsonOk(['error' => $err], 404);
        }

        $meta = $result['meta'] ?? [];
        $closes = $result['indicators']['quote'][0]['close'] ?? [];

        $series = array_values(array_filter($closes, fn($v) => $v !== null));
        if (count($series) > 600) $series = array_slice($series, -600);

        $last = $meta['regularMarketPrice'] ?? null;
        $prev = $meta['previousClose'] ?? null;

        $change = ($last !== null && $prev !== null) ? ($last - $prev) : null;
        $changePct = ($change !== null && $prev != 0) ? ($change / $prev * 100) : null;

        $t = $meta['regularMarketTime'] ?? null;
        $at = $t ? (new \DateTimeImmutable('@' . $t))
            ->setTimezone(new \DateTimeZone(self::TZ))
            ->format('H:i:s') : null;

        return $this->jsonOk([
            'symbol' => $meta['symbol'] ?? $symbol,
            'last' => $last,
            'change' => $change,
            'changePct' => $changePct,
            'dayHigh' => $meta['dayHigh'] ?? null,
            'dayLow' => $meta['dayLow'] ?? null,
            'volume' => $meta['regularMarketVolume'] ?? null,
            'at' => $at,
            'series' => $series,
        ]);
    }

    // ==============================
    // 2) Today's Market List + spark
    // GET /api/widgets/market?symbols=AAPL,MSFT,NVDA&mode=intraday
    // ==============================
    #[Route('/api/widgets/market', name: 'api_widgets_market', methods: ['GET'])]
    public function market(Request $request): JsonResponse
    {
        $symbols = (string) $request->query->get('symbols', '');
        $arr = array_values(array_filter(array_map('trim', explode(',', $symbols))));
        if (!$arr) return $this->jsonOk([]);

        $arr = array_slice($arr, 0, 20);

        $out = [];
        foreach ($arr as $sym) {
            $pack = $this->chartPack($sym, '1d', '5m', 40);
            if (!$pack || empty($pack['meta'])) continue;

            $meta = $pack['meta'];
            $spark = $pack['spark'] ?? [];

            $last = $meta['regularMarketPrice'] ?? null;
            $prev = $meta['previousClose'] ?? null;

            $change = ($last !== null && $prev !== null) ? ($last - $prev) : null;
            $changePct = ($change !== null && $prev) ? ($change / $prev * 100) : null;

            $t = $meta['regularMarketTime'] ?? null;
            $time = $t ? (new \DateTimeImmutable('@' . $t))
                ->setTimezone(new \DateTimeZone(self::TZ))
                ->format('H:i:s') : '';

            $out[] = [
                'symbol' => $meta['symbol'] ?? $sym,
                'price' => $last,
                'currency' => $meta['currency'] ?? 'USD',
                'change' => $change,
                'changePct' => $changePct,
                'time' => $time,
                'spark' => $spark, // ✅ sparkline values
            ];
        }

        return $this->jsonOk($out);
    }

    // ==============================
    // 3) Crypto Ticker
    // GET /api/widgets/crypto?symbols=BTC-USD,ETH-USD,SOL-USD
    // ==============================
    #[Route('/api/widgets/crypto', name: 'api_widgets_crypto', methods: ['GET'])]
    public function crypto(Request $request): JsonResponse
    {
        $symbols = (string) $request->query->get('symbols', '');
        $arr = array_values(array_filter(array_map('trim', explode(',', $symbols))));
        if (!$arr) return $this->jsonOk([]);

        $arr = array_slice($arr, 0, 25);

        $out = [];
        foreach ($arr as $sym) {
            $pack = $this->chartPack($sym, '1d', '15m', 20);
            if (!$pack || empty($pack['meta'])) continue;

            $meta = $pack['meta'];
            $last = $meta['regularMarketPrice'] ?? null;
            $prev = $meta['previousClose'] ?? null;

            $change = ($last !== null && $prev !== null) ? ($last - $prev) : null;
            $changePct = ($change !== null && $prev) ? ($change / $prev * 100) : null;

            $out[] = [
                'symbol' => $meta['symbol'] ?? $sym,
                'price' => $last,
                'changePct' => $changePct,
            ];
        }

        return $this->jsonOk($out);
    }

    // interval mapping compatible with your JS RANGE_MAP
    private function intervalFromRange(string $range): string
    {
        return match ($range) {
            '1d'  => '5m',
            '5d'  => '15m',
            '1mo' => '1h',
            '6mo' => '1d',
            'ytd' => '1d',
            '1y'  => '1d',
            '5y'  => '1wk',
            'max' => '1mo',
            default => '1h',
        };
    }
}
