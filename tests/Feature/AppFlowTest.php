<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\ExpiryItem;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Carbon\CarbonImmutable;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-02 09:00:00'));
        $this->seed(ReferenceDataSeeder::class);
    }

    private function kvgPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Grundversicherung',
            'provider' => 'Musterkasse',
            'cost' => "420.00",
            'cost_interval' => 'monthly',
            'start_date' => '2024-01-01',
            'notice_anchor' => 'fixed_dates',
            'fixed_end_dates_text' => '31.12.',
            'notice_value' => 1,
            'notice_unit' => 'months',
        ], $overrides);
    }

    #[Test]
    public function erster_besuch_fuehrt_zur_registrierung_danach_ist_sie_zu(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertRedirect('/register');

        $this->post('/register', [
            'name' => 'Stefan Muster',
            'email' => 'stefan@example.ch',
            'password' => 'ein-langes-passwort',
            'password_confirmation' => 'ein-langes-passwort',
        ])->assertRedirect(route('settings.edit'));

        $this->assertAuthenticated();
        auth()->logout();

        $this->get('/register')->assertRedirect('/login');
        $this->post('/register', [
            'name' => 'X', 'email' => 'x@example.ch', 'password' => 'ein-langes-passwort', 'password_confirmation' => 'ein-langes-passwort',
        ])->assertForbidden();
    }

    #[Test]
    public function login_mit_falschem_passwort_schlaegt_fehl(): void
    {
        User::factory()->create(['email' => 'a@example.ch']);

        $this->post('/login', ['email' => 'a@example.ch', 'password' => 'falsch'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'a@example.ch', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    #[Test]
    public function vertrag_erfassen_berechnet_fristen_und_plant_erinnerungen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('contracts.create'))->assertOk()->assertSee('Krankenkasse Grundversicherung');
        $this->actingAs($user)->get(route('contracts.create', ['template' => 'kvg-grundversicherung']))->assertOk()->assertSee('Feste Kündigungstermine')
            ->assertSee('name="notice_value" type="number" min="0" max="730" value="1"', false);

        $response = $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload());

        $contract = Contract::firstOrFail();
        $response->assertRedirect(route('contracts.show', $contract));

        $this->assertSame(42000, $contract->cost_amount);
        $this->assertSame(['12-31'], $contract->fixed_end_dates);
        $this->assertSame('2026-12-31', $contract->next_term_end->toDateString());
        $this->assertSame('2026-11-30', $contract->receipt_deadline->toDateString());
        $this->assertSame('2026-11-23', $contract->send_by->toDateString());

        // Standard 30/7/0 Tage vor Versandtag; 30 Tage vorher liegt in der Vergangenheit.
        $due = $contract->reminders()->orderBy('due_on')->pluck('due_on')->map->toDateString()->all();
        $this->assertSame(['2026-11-16', '2026-11-23'], $due);

        $this->actingAs($user)->get(route('contracts.show', $contract))->assertOk()->assertSee('Kündigung bis');
        $this->actingAs($user)->get(route('contracts.edit', $contract))->assertOk();
        $this->actingAs($user)->get(route('contracts.index'))->assertOk()->assertSee('Musterkasse');
        $this->actingAs($user)->get('/')->assertOk()->assertSee('Grundversicherung bis');
    }

    #[Test]
    public function vertrag_bearbeiten_plant_erinnerungen_neu(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload());
        $contract = Contract::firstOrFail();

        $this->actingAs($user)->put(route('contracts.update', $contract), $this->kvgPayload(['reminder_offsets_text' => '14, 2']))
            ->assertRedirect(route('contracts.show', $contract));

        $due = $contract->reminders()->orderBy('due_on')->pluck('due_on')->map->toDateString()->all();
        $this->assertSame(['2026-11-09', '2026-11-21'], $due);
    }

    #[Test]
    public function validierung_meldet_fehlende_termine(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload(['fixed_end_dates_text' => '']))
            ->assertSessionHasErrors('fixed_end_dates');
        $this->assertDatabaseCount('contracts', 0);
    }

    #[Test]
    public function fremde_vertraege_sind_unsichtbar(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $contract = Contract::factory()->for($owner)->create();

        $this->actingAs($other)->get(route('contracts.show', $contract))->assertNotFound();
        $this->actingAs($other)->delete(route('contracts.destroy', $contract))->assertNotFound();
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
    }

    #[Test]
    public function vorschau_liefert_fristen_als_json(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('contracts.preview'), $this->kvgPayload())
            ->assertOk()
            ->assertJson(['term_end' => '2026-12-31', 'receipt_deadline' => '2026-11-30', 'send_by' => '2026-11-23']);
    }

    #[Test]
    public function kuendigungsschreiben_als_pdf_und_statusablauf(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload(['customer_number' => '123.456']));
        $contract = Contract::firstOrFail();

        $this->actingAs($user)->get(route('contracts.letter.edit', $contract))->assertOk()->assertSee('123.456');

        $response = $this->actingAs($user)->post(route('contracts.letter.download', $contract), [
            'sender' => "Stefan Muster\nMusterstrasse 1\n9000 St. Gallen",
            'recipient' => "Musterkasse\nPostfach\n8000 Zürich",
            'place' => 'St. Gallen',
            'date' => '2026-11-02',
            'subject' => 'Kündigung Grundversicherung',
            'body' => 'Sehr geehrte Damen und Herren',
            'signature' => 'Stefan Muster',
            'registered' => 1,
        ]);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame(ContractStatus::CancellationPlanned, $contract->fresh()->status);
        $this->assertSame(1, $contract->documents()->count());

        $this->actingAs($user)->post(route('contracts.status', $contract), ['action' => 'sent', 'sent_on' => '2026-11-10']);
        $contract->refresh();
        $this->assertSame(ContractStatus::CancelledUnconfirmed, $contract->status);
        $this->assertSame('2026-12-31', $contract->cancellation_effective_on->toDateString());
        $this->assertTrue($contract->reminders()->where('kind', 'confirmation')->whereDate('due_on', '2026-11-24')->exists());
        $this->assertFalse($contract->reminders()->where('kind', 'deadline')->whereNull('sent_at')->exists());

        $this->actingAs($user)->post(route('contracts.status', $contract), ['action' => 'confirmed']);
        $this->assertSame(ContractStatus::CancelledConfirmed, $contract->fresh()->status);

        $contract->documents->each->delete();
    }

    #[Test]
    public function behalten_schaltet_erinnerungen_dieser_periode_stumm(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload());
        $contract = Contract::firstOrFail();

        $this->actingAs($user)->post(route('contracts.keep', $contract))->assertRedirect();
        $this->assertSame(0, $contract->reminders()->whereNull('sent_at')->count());

        // Speichern plant für dieselbe Periode nicht erneut.
        $contract->refresh()->save();
        $this->assertSame(0, $contract->reminders()->whereNull('sent_at')->count());
    }

    #[Test]
    public function faellige_erinnerungen_werden_versendet(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload());

        $this->travelTo(CarbonImmutable::parse('2026-11-16 08:00:00'));
        $this->artisan('fristwatcher:send-reminders')->assertSuccessful();

        Notification::assertSentTo($user, ReminderNotification::class, function ($n, $channels) {
            $content = $n->content();

            return in_array('mail', $channels, true)
                && str_contains($content['title'], 'Grundversicherung')
                && str_contains($content['title'], 'in 7 Tagen');
        });
        $this->assertSame(1, Reminder::whereNotNull('sent_at')->count());

        // Zweiter Lauf am selben Tag sendet nichts doppelt.
        $this->artisan('fristwatcher:send-reminders')->assertSuccessful();
        Notification::assertSentToTimes($user, ReminderNotification::class, 1);
    }

    #[Test]
    public function erinnerung_wird_vor_der_versandstunde_nicht_gesendet(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload());

        $this->travelTo(CarbonImmutable::parse('2026-11-16 05:00:00'));
        $this->artisan('fristwatcher:send-reminders')->assertSuccessful();
        Notification::assertNothingSent();
    }

    #[Test]
    public function mail_inhalt_wird_gerendert(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload());
        $reminder = Reminder::firstOrFail();

        $mail = (new ReminderNotification($reminder))->toMail($user);
        $html = (string) $mail->render();

        $this->assertStringContainsString('Musterkasse', $html);
        $this->assertStringContainsString('30. November 2026', $html);
    }

    #[Test]
    public function refresh_rollt_auf_naechste_periode_und_uebernimmt_preis(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload());
        $contract = Contract::firstOrFail();

        $this->actingAs($user)->post(route('contracts.price-changes.store', $contract), [
            'new_price' => '455.50',
            'effective_date' => '2027-01-01',
            'announced_at' => '2026-10-15',
            'special_termination_deadline' => '2026-11-30',
        ])->assertRedirect();
        $this->assertSame(42000, $contract->fresh()->cost_amount);
        $this->assertTrue(Reminder::where('kind', 'price_special')->exists());

        $this->travelTo(CarbonImmutable::parse('2027-01-02 08:00:00'));
        $this->artisan('fristwatcher:refresh')->assertSuccessful();

        $contract->refresh();
        $this->assertSame(45550, $contract->cost_amount);
        $this->assertSame('2027-12-31', $contract->next_term_end->toDateString());
    }

    #[Test]
    public function gekuendigte_vertraege_werden_nach_ablauf_beendet(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload());
        $contract = Contract::firstOrFail();
        $this->actingAs($user)->post(route('contracts.status', $contract), ['action' => 'sent']);

        $this->travelTo(CarbonImmutable::parse('2027-01-05 08:00:00'));
        $this->artisan('fristwatcher:refresh')->assertSuccessful();

        $this->assertSame(ContractStatus::Ended, $contract->fresh()->status);
    }

    #[Test]
    public function ablaufdaten_erfassen_und_erinnern(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('expiry.store'), [
            'type' => 'passport', 'name' => 'Pass', 'expires_at' => '2027-03-01', 'reference_last4' => '1234',
        ])->assertRedirect(route('expiry.index'));

        $item = ExpiryItem::firstOrFail();
        // 180 Tage vorher liegt in der Vergangenheit, 60 Tage vorher nicht.
        $this->assertSame(['2026-12-31'], $item->reminders()->pluck('due_on')->map->toDateString()->all());

        $this->actingAs($user)->get(route('expiry.index'))->assertOk()->assertSee('Pass');
        $this->actingAs($user)->get(route('expiry.edit', $item))->assertOk();
        $this->actingAs($user)->post(route('expiry.store'), ['type' => 'passport', 'name' => 'X', 'expires_at' => '2027-03-01', 'reference_last4' => '123456'])
            ->assertSessionHasErrors('reference_last4');
    }

    #[Test]
    public function sparpotenzial_und_einstellungen(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload());
        $this->actingAs($user)->get(route('savings'))->assertOk()->assertSee("5\u{2019}040.00", false);

        $this->actingAs($user)->get(route('settings.edit'))->assertOk();
        $this->actingAs($user)->put(route('settings.update'), [
            'name' => 'Stefan', 'email' => $user->email, 'street' => 'Weg 2', 'zip' => '9435', 'city' => 'Heerbrugg',
            'canton' => 'SG', 'reminder_offsets_text' => '10', 'send_buffer_days' => 3,
            'notify_mail' => 1, 'notify_push' => 0,
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame([10], $user->reminderOffsets());
        $this->assertFalse($user->wantsPush());
        // Neuer Puffer 3 Arbeitstage: 30.11. minus 3 = 25.11.
        $this->assertSame('2026-11-25', Contract::first()->send_by->toDateString());
    }

    #[Test]
    public function deploy_endpunkte_sind_ohne_gueltiges_token_gesperrt(): void
    {
        config(['fristwatcher.deploy_hook_token' => null]);
        $this->post('/_deploy')->assertNotFound();
        $this->get('/_cron?token=x')->assertNotFound();

        config(['fristwatcher.deploy_hook_token' => str_repeat('a', 40)]);
        $this->post('/_deploy', [], ['X-Deploy-Token' => 'falsch'])->assertForbidden();
        $this->get('/_cron?token=falsch')->assertForbidden();

        $this->get('/_cron?token='.str_repeat('a', 40))->assertOk();
    }

    #[Test]
    public function test_erinnerung_aus_den_einstellungen(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('contracts.store'), $this->kvgPayload());

        $this->actingAs($user)->post(route('settings.test-notification'))->assertRedirect();
        Notification::assertSentTo($user, ReminderNotification::class);
    }
}
