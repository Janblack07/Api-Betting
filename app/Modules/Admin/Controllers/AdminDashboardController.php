<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Betting\Models\Bet;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenApi\Attributes as OA;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/admin/dashboard',
        summary: 'Dashboard administrativo',
        description: 'HU-44: Devuelve métricas generales de usuarios, apuestas, saldo y consumo de The Odds API.',
        security: [['sanctum' => []]],
        tags: ['Admin Dashboard'],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard administrativo obtenido correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function index(): JsonResponse
    {
        $totalUsers = User::query()->count();

        $totalCustomers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'customer'))
            ->count();

        $pendingBets = Bet::query()
            ->whereIn('status', ['pending', 'accepted'])
            ->count();

        $totalBets = Bet::query()->count();

        $totalAmountBet = (float) Bet::query()->sum('total_amount');

        $totalWonPaid = (float) Bet::query()
            ->where('status', 'won')
            ->sum('potential_win');

        $totalLostAmount = (float) Bet::query()
            ->where('status', 'lost')
            ->sum('total_amount');

        $totalRefunded = (float) Bet::query()
            ->where('status', 'refunded')
            ->sum('total_amount');

        $platformProfit = $totalLostAmount - max($totalWonPaid - (float) Bet::query()
            ->where('status', 'won')
            ->sum('total_amount'), 0);

        $walletTotals = [
            'total_balance' => '0.00',
            'total_locked_balance' => '0.00',
            'total_available_balance' => '0.00',
        ];

        if (Schema::hasTable('wallets')) {
            $totalBalance = (float) DB::table('wallets')->sum('balance');
            $totalLockedBalance = (float) DB::table('wallets')->sum('locked_balance');

            $walletTotals = [
                'total_balance' => number_format($totalBalance, 2, '.', ''),
                'total_locked_balance' => number_format($totalLockedBalance, 2, '.', ''),
                'total_available_balance' => number_format($totalBalance - $totalLockedBalance, 2, '.', ''),
            ];
        }

        $apiUsage = [
            'total_requests' => 0,
            'total_credits_used' => 0,
            'last_requests_remaining' => null,
            'last_request_at' => null,
        ];

        if (Schema::hasTable('api_usage_logs')) {
            $lastLog = DB::table('api_usage_logs')
                ->latest('id')
                ->first();

            $apiUsage = [
                'total_requests' => DB::table('api_usage_logs')->count(),
                'total_credits_used' => (int) DB::table('api_usage_logs')->sum('credits_used'),
                'last_requests_remaining' => $lastLog->requests_remaining ?? null,
                'last_request_at' => $lastLog->created_at ?? null,
            ];
        }

        return $this->successResponse([
            'users' => [
                'total_users' => $totalUsers,
                'total_customers' => $totalCustomers,
            ],
            'bets' => [
                'total_bets' => $totalBets,
                'pending_bets' => $pendingBets,
                'total_amount_bet' => number_format($totalAmountBet, 2, '.', ''),
                'total_won_paid' => number_format($totalWonPaid, 2, '.', ''),
                'total_lost_amount' => number_format($totalLostAmount, 2, '.', ''),
                'total_refunded' => number_format($totalRefunded, 2, '.', ''),
                'platform_profit' => number_format($platformProfit, 2, '.', ''),
            ],
            'wallets' => $walletTotals,
            'api_usage' => $apiUsage,
        ], 'Dashboard administrativo obtenido correctamente.');
    }
}
