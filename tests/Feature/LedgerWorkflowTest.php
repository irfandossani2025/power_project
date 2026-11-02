<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_balanced_journal_entry_is_recorded(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create();
        $this->actingAs($user)->get(route('ledger.index'))->assertOk();
        $cash=Account::where('code','1000')->firstOrFail(); $equity=Account::where('code','3000')->firstOrFail();
        $this->actingAs($user)->post(route('ledger.entries.store'), ['entry_date'=>'2026-11-03','reference'=>'OPEN-001','lines'=>[['account_id'=>$cash->id,'debit'=>1000,'credit'=>0],['account_id'=>$equity->id,'debit'=>0,'credit'=>1000]]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('journal_entries',['reference'=>'OPEN-001']);
        $this->assertDatabaseCount('journal_lines',2);
    }

    public function test_unbalanced_journal_entry_is_rejected(): void
    {
        Company::create(['name'=>'My Business']); $user=User::factory()->create(); $this->actingAs($user)->get(route('ledger.index'));
        $cash=Account::where('code','1000')->firstOrFail(); $equity=Account::where('code','3000')->firstOrFail();
        $this->actingAs($user)->from(route('ledger.index'))->post(route('ledger.entries.store'), ['entry_date'=>'2026-11-03','lines'=>[['account_id'=>$cash->id,'debit'=>1000,'credit'=>0],['account_id'=>$equity->id,'debit'=>0,'credit'=>900]]])->assertSessionHasErrors('lines');
        $this->assertDatabaseCount('journal_entries',0);
    }
}
