<?php

declare(strict_types=1);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__.'/check-target.php';

$kernel = $app->make(Kernel::class);

/** @return array{int,array<string,mixed>} */
function requestDemo(Kernel $kernel, string $method, string $path, ?string $token = null, ?array $payload = null): array
{
    Auth::forgetGuards();
    $headers = ['HTTP_ACCEPT' => 'application/json'];
    if ($token !== null) {
        $headers['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
    }
    if ($payload !== null) {
        $headers['CONTENT_TYPE'] = 'application/json';
    }
    $request = Request::create($path, $method, [], [], [], $headers,
        $payload === null ? null : json_encode($payload, JSON_THROW_ON_ERROR));
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);

    return [$response->getStatusCode(), json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR)];
}

function demoLogin(Kernel $kernel, string $email): string
{
    [$status, $body] = requestDemo($kernel, 'POST', '/api/auth/login', null, [
        'email' => $email,
        'password' => 'DevOnly!ChangeMe123',
        'device_name' => 'FE5 demo verifier',
    ]);
    if ($status !== 200 || ! isset($body['data']['access_token'])) {
        throw new RuntimeException('Demo login failed for '.$email.' (HTTP '.$status.').');
    }

    return $body['data']['access_token'];
}

$memberId = (int) DB::table('ho_so_hoi_vien')
    ->join('nguoi_dung', 'nguoi_dung.id', '=', 'ho_so_hoi_vien.nguoi_dung_id')
    ->where('nguoi_dung.thu_dien_tu', 'dev.member01@smartfitness.local')
    ->value('ho_so_hoi_vien.id');
$sessionId = (int) DB::table('phien_tap')->where('hoi_vien_id', $memberId)
    ->where('trang_thai', 'HOAN_THANH')->value('id');
$exerciseId = (int) DB::table('bai_tap_trong_phien')->where('phien_tap_id', $sessionId)->value('bai_tap_id');
if ($memberId < 1 || $sessionId < 1 || $exerciseId < 1) {
    throw new RuntimeException('FE5 demo fixture is incomplete.');
}

$usageBefore = [
    'registrations' => DB::table('dang_ky_goi_tap')->count(),
    'terms' => DB::table('ky_han_hoi_vien')->count(),
    'usage' => DB::table('su_dung_quyen_loi')->count(),
];
$fixtureCounts = [
    'assignments' => DB::table('phan_cong_huan_luyen_vien')->where('hoi_vien_id', $memberId)->count(),
    'plans' => DB::table('ke_hoach_tap')->where('hoi_vien_id', $memberId)->count(),
    'body_measurements' => DB::table('chi_so_co_the')->where('hoi_vien_id', $memberId)->count(),
    'completed_sessions' => DB::table('phien_tap')->where('hoi_vien_id', $memberId)->where('trang_thai', 'HOAN_THANH')->count(),
    'notes' => DB::table('ghi_chu_huan_luyen')->where('hoi_vien_id', $memberId)->count(),
];
echo 'Fixture counts: '.json_encode($fixtureCounts, JSON_THROW_ON_ERROR)."\n";
$ptToken = demoLogin($kernel, 'dev.pt01@smartfitness.local');
$paths = [
    '/api/profile/trainer',
    '/api/pt/members',
    '/api/pt/members/'.$memberId,
    '/api/pt/members/'.$memberId.'/progress/overview',
    '/api/pt/members/'.$memberId.'/progress/body',
    '/api/pt/members/'.$memberId.'/progress/exercises/'.$exerciseId,
    '/api/pt/members/'.$memberId.'/workout/plans/current',
    '/api/pt/members/'.$memberId.'/workout/sessions',
    '/api/pt/members/'.$memberId.'/workout/sessions/'.$sessionId,
    '/api/pt/members/'.$memberId.'/notes',
];
$responses = [];
foreach ($paths as $path) {
    [$status, $body] = requestDemo($kernel, 'GET', $path, $ptToken);
    if ($status !== 200) {
        throw new RuntimeException('Expected HTTP 200 for '.$path.', got '.$status.'.');
    }
    $responses[$path] = $body['data'] ?? null;
    echo "PASS GET {$path}\n";
}

$base = '/api/pt/members/'.$memberId;
if (count($responses['/api/pt/members']) < 1
    || ($responses[$base]['member']['id'] ?? null) !== $memberId
    || ! isset($responses[$base.'/workout/plans/current']['plan']['id'])
    || count($responses[$base.'/workout/plans/current']['future_schedule'] ?? []) < 1
    || count($responses[$base.'/workout/sessions'] ?? []) < 1
    || ($responses[$base.'/workout/sessions/'.$sessionId]['id'] ?? null) !== $sessionId
    || count($responses[$base.'/progress/body']['items'] ?? []) < 2
    || ($responses[$base.'/progress/overview']['completed_sessions_count'] ?? 0) < 1
    || count($responses[$base.'/progress/exercises/'.$exerciseId]['items'] ?? []) < 1
    || count($responses[$base.'/notes']) < 1) {
    throw new RuntimeException('An FE5 API returned an empty or inconsistent demo payload.');
}
echo "PASS populated FE5 payloads across seven screens\n";

$otherPtToken = demoLogin($kernel, 'dev.pt02@smartfitness.local');
[$status] = requestDemo($kernel, 'GET', '/api/pt/members/'.$memberId, $otherPtToken);
if ($status !== 404) {
    throw new RuntimeException('PT02 scope check expected HTTP 404, got '.$status.'.');
}
echo "PASS PT02 denied access to Member01\n";

$usageAfter = [
    'registrations' => DB::table('dang_ky_goi_tap')->count(),
    'terms' => DB::table('ky_han_hoi_vien')->count(),
    'usage' => DB::table('su_dung_quyen_loi')->count(),
];
if ($usageBefore !== $usageAfter) {
    throw new RuntimeException('Read-only FE5 checks changed membership/usage data.');
}
echo "PASS no Membership/usage side effect\n";
