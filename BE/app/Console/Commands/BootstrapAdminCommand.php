<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Services\Admin\BootstrapAdminService;
use App\Services\Auth\PasswordResetService;
use Illuminate\Console\Command;
use Throwable;

class BootstrapAdminCommand extends Command
{
    protected $signature = 'smart-fitness:bootstrap-admin
        {--name= : Họ tên quản trị viên đầu tiên}
        {--email= : Email quản trị viên đầu tiên}
        {--phone= : Số điện thoại tùy chọn}
        {--branch= : ID hoặc mã chi nhánh đang hoạt động}
        {--confirm : Xác nhận chủ động việc bootstrap Admin đầu tiên}';

    protected $description = 'Khởi tạo đúng một tài khoản ADMIN đầu tiên và yêu cầu thiết lập mật khẩu';

    public function handle(BootstrapAdminService $bootstrap, PasswordResetService $passwordReset): int
    {
        $duLieu = [
            'name' => $this->giaTriBatBuoc('name', 'Họ tên'),
            'email' => $this->giaTriBatBuoc('email', 'Email'),
            'phone' => $this->giaTriTuyChon('phone', 'Số điện thoại (có thể bỏ trống)'),
            'branch' => $this->giaTriBatBuoc('branch', 'ID hoặc mã chi nhánh'),
        ];

        if (! $this->xacNhan()) {
            $this->error('BOOTSTRAP_ADMIN_CANCELLED');

            return self::FAILURE;
        }

        try {
            $ketQua = $bootstrap->khoiTao($duLieu);
            if ($ketQua['transition'] === 'CREATED') {
                // Chỉ gọi sau khi transaction tạo account/role/audit đã commit.
                $passwordReset->yeuCau((string) $ketQua['email']);
            }

            $this->line('BOOTSTRAP_ADMIN_'.$ketQua['transition']);
            $this->line('account_id: '.$ketQua['account_id']);
            $this->line('email: '.$ketQua['email']);
            $this->line('role: ADMIN');
            $this->line('password_setup_notification: '.($ketQua['transition'] === 'CREATED' ? 'QUEUED' : 'UNCHANGED'));

            return self::SUCCESS;
        } catch (AuthWorkflowException $exception) {
            $this->error($exception->safeCode);

            return self::FAILURE;
        } catch (Throwable) {
            $this->error('BOOTSTRAP_ADMIN_FAILED');

            return self::FAILURE;
        }
    }

    private function giaTriBatBuoc(string $tuyChon, string $cauHoi): string
    {
        $giaTri = trim((string) $this->option($tuyChon));
        if ($giaTri !== '') {
            return $giaTri;
        }

        if (! $this->input->isInteractive()) {
            throw new \InvalidArgumentException("Option --{$tuyChon} is required in non-interactive mode.");
        }

        return trim((string) $this->ask($cauHoi));
    }

    private function giaTriTuyChon(string $tuyChon, string $cauHoi): ?string
    {
        $giaTri = $this->option($tuyChon);
        if ($giaTri !== null) {
            $giaTri = trim((string) $giaTri);

            return $giaTri === '' ? null : $giaTri;
        }

        if (! $this->input->isInteractive()) {
            return null;
        }

        $giaTri = trim((string) $this->ask($cauHoi));

        return $giaTri === '' ? null : $giaTri;
    }

    private function xacNhan(): bool
    {
        if ((bool) $this->option('confirm')) {
            return true;
        }

        return $this->input->isInteractive()
            && $this->confirm('Xác nhận bootstrap quản trị viên đầu tiên?', false);
    }
}
