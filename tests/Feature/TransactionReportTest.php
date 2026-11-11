<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_transactions_and_open_a_transaction_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $paid = Transaction::query()->create([
            'user_id' => $admin->id, 'order_number' => 'TRX-261107-HIST0001',
            'checkout_key' => (string) Str::uuid(), 'payment_method' => 'cash',
            'payment_status' => 'paid', 'total_amount' => 18000, 'cash_received' => 20000,
            'change_amount' => 2000, 'paid_at' => now(),
        ]);
        $paid->items()->create(['product_name' => 'Bebek goreng', 'product_price' => 18000, 'quantity' => 1, 'line_total' => 18000]);
        Transaction::query()->create([
            'user_id' => $admin->id, 'order_number' => 'TRX-261107-HIST0002',
            'checkout_key' => (string) Str::uuid(), 'payment_method' => 'qris',
            'payment_status' => 'pending', 'total_amount' => 44000,
        ]);

        $this->actingAs($admin)->get('/admin/transactions?status=paid')
            ->assertOk()->assertSee('TRX-261107-HIST0001')->assertDontSee('TRX-261107-HIST0002');
        $this->get(route('admin.transactions.show', $paid))
            ->assertOk()->assertSee('Bebek goreng')->assertSee('Rp 18.000');
    }

    public function test_sales_report_exports_paid_transactions_as_an_excel_workbook(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $paid = Transaction::query()->create([
            'user_id' => $admin->id, 'order_number' => 'TRX-261107-RPT00001',
            'checkout_key' => (string) Str::uuid(), 'payment_method' => 'cash',
            'payment_status' => 'paid', 'total_amount' => 21000, 'cash_received' => 25000,
            'change_amount' => 4000, 'paid_at' => now(),
        ]);
        $paid->items()->create(['product_name' => 'Nasi bebek', 'product_price' => 21000, 'product_cost' => 10000, 'quantity' => 1, 'line_total' => 21000]);
        Transaction::query()->create([
            'user_id' => $admin->id, 'order_number' => 'TRX-261107-RPT00002',
            'checkout_key' => (string) Str::uuid(), 'payment_method' => 'qris',
            'payment_status' => 'pending', 'total_amount' => 99000, 'paid_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/reports?period=daily')
            ->assertOk()->assertSee('Rp 21.000')->assertSee('Laba kotor')->assertSee('Rp 11.000')
            ->assertDontSee('Rata-rata transaksi')->assertDontSee('Rp 99.000');
        $export = $this->get('/admin/reports/export?period=daily');
        $export->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $xlsx = $export->streamedContent();
        $this->assertSame('PK', substr($xlsx, 0, 2));

        $temporaryFile = tempnam(sys_get_temp_dir(), 'kasir-report-');
        try {
            file_put_contents($temporaryFile, $xlsx);
            $archive = new \ZipArchive;
            $this->assertSame(true, $archive->open($temporaryFile));
            $strings = $archive->getFromName('xl/sharedStrings.xml');
            $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
            $archive->close();
            $this->assertStringContainsString('TRX-261107-RPT00001', $strings);
            $this->assertStringNotContainsString('TRX-261107-RPT00002', $strings);
            $this->assertStringContainsString('<v>21000</v>', $worksheet);
            $this->assertStringContainsString('<v>10000</v>', $worksheet);
            $this->assertStringContainsString('<v>11000</v>', $worksheet);
        } finally {
            @unlink($temporaryFile);
        }
    }

    public function test_sales_report_can_download_a_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/reports/export/pdf?period=daily')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF-', false);
    }

    public function test_cashier_cannot_access_admin_transaction_pages(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)->get('/admin/transactions')->assertForbidden();
        $this->get('/admin/reports')->assertForbidden();
    }
}
