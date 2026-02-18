<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MarketApiController extends AbstractController
{
    public function __construct(private HttpClientInterface $http) {}

    // ==============================
    // 1) Quote + Chart (Big Widget)
    // GET /api/widgets/quote/AAPL?range=1mo
    // ==============================
    #[Route('/api/widgets/quote/{symbol}', name: 'api_widgets_quote', methods: ['GET'])]
    public function quote(string $symbol, Request $request): JsonResponse
    {
        $range = $request->query->get('range', '1mo'); // 1d,5d,1mo,6mo,ytd,1y,5y,max
        $interval = $this->intervalFromRange($range);

        $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?range={$range}&interval={$interval}";
        $json = $this->http->request('GET', $url)->toArray(false);

        $result = $json['chart']['result'][0] ?? null;
        if (!$result) {
            return $this->json(['error' => 'No data'], 404);
        }

        $meta = $result['meta'] ?? [];
        $closes = $result['indicators']['quote'][0]['close'] ?? [];

        // series (remove nulls)
        $series = array_values(array_filter($closes, fn ($v) => $v !== null));

        $last = $meta['regularMarketPrice'] ?? null;
        $prev = $meta['previousClose'] ?? null;

        $change = ($last !== null && $prev !== null) ? ($last - $prev) : null;
        $changePct = ($change !== null && $prev != 0) ? ($change / $prev * 100) : null;

        $t = $meta['regularMarketTime'] ?? null;
        $at = $t ? (new \DateTimeImmutable('@' . $t))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->format('d M Y') : null;

        return $this->json([
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
    // 2) Today's Market List
    // GET /api/widgets/market?symbols=AAPL,MSFT,NVDA&mode=intraday
    // ==============================
    #[Route('/api/widgets/market', name: 'api_widgets_market', methods: ['GET'])]
    public function market(Request $request): JsonResponse
    {
        $symbols = $request->query->get('symbols', '');
        $arr = array_filter(array_map('trim', explode(',', $symbols)));
        if (!$arr) return $this->json([]);

        $url = "https://query1.finance.yahoo.com/v7/finance/quote?symbols=" . urlencode(implode(',', $arr));
        $json = $this->http->request('GET', $url)->toArray(false);

        $quotes = $json['quoteResponse']['result'] ?? [];
        $out = [];

        foreach ($quotes as $q) {
            $t = $q['regularMarketTime'] ?? null;

            $out[] = [
                'symbol' => $q['symbol'] ?? '',
                'price' => $q['regularMarketPrice'] ?? null,
                'currency' => $q['currency'] ?? 'USD',
                'change' => $q['regularMarketChange'] ?? null,
                'changePct' => $q['regularMarketChangePercent'] ?? null,
                'time' => $t ? (new \DateTimeImmutable('@' . $t))
                    ->setTimezone(new \DateTimeZone('Europe/Paris'))
                    ->format('H:i:s') : '',
            ];
        }

        return $this->json($out);
    }

    // ==============================
    // 3) Crypto Ticker
    // GET /api/widgets/crypto?symbols=BTC-USD,ETH-USD,SOL-USD
    // ==============================
    #[Route('/api/widgets/crypto', name: 'api_widgets_crypto', methods: ['GET'])]
    public function crypto(Request $request): JsonResponse
    {
        $symbols = $request->query->get('symbols', '');
        $arr = array_filter(array_map('trim', explode(',', $symbols)));
        if (!$arr) return $this->json([]);

        $url = "https://query1.finance.yahoo.com/v7/finance/quote?symbols=" . urlencode(implode(',', $arr));
        $json = $this->http->request('GET', $url)->toArray(false);

        $quotes = $json['quoteResponse']['result'] ?? [];
        $out = [];

        foreach ($quotes as $q) {
            $out[] = [
                'symbol' => $q['symbol'] ?? '',
                'price' => $q['regularMarketPrice'] ?? null,
                'changePct' => $q['regularMarketChangePercent'] ?? null,
            ];
        }

        return $this->json($out);
    }

    // ✅ interval mapping (مهم بزاف)
    private function intervalFromRange(string $range): string
    {
        return match ($range) {
            '1d'  => '5m',
            '5d'  => '15m',
            '1mo' => '1h',   // OK
            '6mo' => '1d',
            'ytd' => '1d',
            '1y'  => '1wk',
            '5y'  => '1wk',
            'max' => '1mo',
            default => '1h',
        };
    }
}
