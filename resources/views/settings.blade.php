<x-layouts.app title="Einstellungen">
    <h1 class="text-[28px] font-semibold tracking-tight">Einstellungen</h1>

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-8">
            @csrf @method('PUT')

            <fieldset class="panel space-y-5 p-5 sm:p-6 [&>legend~*]:clear-both">
                <legend class="section-title float-left mb-1 w-full">Profil und Adresse</legend>
                <p class="clear-both -mt-2 text-[14px] text-ink-soft">Die Adresse erscheint als Absender auf Kündigungsschreiben.</p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="label">Name</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="field" autocomplete="name">
                        <x-field-error name="name" />
                    </div>
                    <div>
                        <label for="email" class="label">E-Mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="field" autocomplete="email">
                        <x-field-error name="email" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="street" class="label">Strasse und Nr.</label>
                        <input id="street" name="street" value="{{ old('street', $user->street) }}" class="field" autocomplete="street-address">
                    </div>
                    <div class="grid grid-cols-[110px_1fr] gap-3 sm:col-span-2">
                        <div>
                            <label for="zip" class="label">PLZ</label>
                            <input id="zip" name="zip" value="{{ old('zip', $user->zip) }}" class="field" inputmode="numeric" autocomplete="postal-code">
                        </div>
                        <div>
                            <label for="city" class="label">Ort</label>
                            <input id="city" name="city" value="{{ old('city', $user->city) }}" class="field" autocomplete="address-level2">
                        </div>
                    </div>
                    <div>
                        <label for="canton" class="label">Kanton</label>
                        <select id="canton" name="canton" class="field">
                            @foreach ($cantons as $k)
                                <option value="{{ $k }}" @selected(old('canton', $user->canton) === $k)>{{ $k }}</option>
                            @endforeach
                        </select>
                        <p class="hint">Für kantonale Feiertage bei der Fristberechnung.</p>
                    </div>
                </div>
            </fieldset>

            <fieldset class="panel space-y-5 p-5 sm:p-6 [&>legend~*]:clear-both">
                <legend class="section-title float-left mb-1 w-full">Erinnerungen</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="reminder_offsets_text" class="label">Erinnern, Tage vor dem Versandtag</label>
                        <input id="reminder_offsets_text" name="reminder_offsets_text" value="{{ old('reminder_offsets_text', $offsetsText) }}" class="field" placeholder="30, 7, 0">
                        <p class="hint">Mit Komma getrennt. 0 = am Versandtag selbst.</p>
                    </div>
                    <div>
                        <label for="send_buffer_days" class="label">Postweg in Arbeitstagen</label>
                        <input id="send_buffer_days" name="send_buffer_days" type="number" min="0" max="30" value="{{ old('send_buffer_days', $user->sendBufferDays()) }}" class="field w-28">
                        <p class="hint">Versandtag = Eingangsdatum minus diese Arbeitstage.</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <label class="flex items-center gap-2.5 text-[15px]">
                        <input type="hidden" name="notify_mail" value="0">
                        <input type="checkbox" name="notify_mail" value="1" @checked(old('notify_mail', $user->wantsMail())) class="rounded text-accent focus:ring-accent">
                        Per E-Mail an {{ $user->email }}
                    </label>
                    <label class="flex items-center gap-2.5 text-[15px]">
                        <input type="hidden" name="notify_push" value="0">
                        <input type="checkbox" name="notify_push" value="1" @checked(old('notify_push', $user->wantsPush())) class="rounded text-accent focus:ring-accent">
                        Per Push auf verbundene Geräte
                    </label>
                </div>
            </fieldset>

            <button class="btn-primary">Einstellungen speichern</button>
        </form>

        <aside class="space-y-6">
            <section class="panel p-5" x-data="pushToggle({ vapidKey: @js($vapidKey), storeUrl: @js(route('push.store')), destroyUrl: @js(route('push.destroy')) })">
                <h2 class="section-title">Push auf diesem Gerät</h2>
                <p class="mt-1 text-[14px] text-ink-soft">{{ $pushDevices }} {{ $pushDevices === 1 ? 'Gerät' : 'Geräte' }} verbunden.</p>

                <template x-if="!supported">
                    <p class="mt-3 text-[14px] text-ink-soft">Dieser Browser unterstützt keine Push-Mitteilungen.</p>
                </template>
                <template x-if="supported && ios && !standalone">
                    <p class="mt-3 text-[14px] text-ink-soft">Auf dem iPhone zuerst im Teilen-Menü «Zum Home-Bildschirm» wählen und die App von dort öffnen. Dann hier Push aktivieren.</p>
                </template>
                <button type="button" x-show="supported && (!ios || standalone)" x-on:click="toggle()" x-bind:disabled="busy" class="mt-4 w-full"
                        x-bind:class="subscribed ? 'btn-secondary' : 'btn-primary'">
                    <x-icon name="bell" class="size-4" />
                    <span x-text="subscribed ? 'Push auf diesem Gerät ausschalten' : 'Push auf diesem Gerät aktivieren'"></span>
                </button>
                <p class="mt-3 text-[14px]" x-show="message" x-text="message"></p>
            </section>

            <section class="panel p-5">
                <h2 class="section-title">Test</h2>
                <p class="mt-1 text-[14px] text-ink-soft">Schickt eine Erinnerung für deine nächste Frist über alle aktiven Kanäle.</p>
                <form method="POST" action="{{ route('settings.test-notification') }}" class="mt-4">
                    @csrf
                    <button class="btn-secondary w-full">Test-Erinnerung senden</button>
                </form>
            </section>

            <form method="POST" action="{{ route('settings.password') }}" class="panel space-y-4 p-5">
                @csrf @method('PUT')
                <h2 class="section-title">Passwort ändern</h2>
                <div>
                    <label for="current_password" class="label">Aktuelles Passwort</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="field">
                    <x-field-error name="current_password" />
                </div>
                <div>
                    <label for="password" class="label">Neues Passwort</label>
                    <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password" class="field">
                    <x-field-error name="password" />
                </div>
                <div>
                    <label for="password_confirmation" class="label">Wiederholen</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="field">
                </div>
                <button class="btn-secondary w-full">Passwort ändern</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
                @csrf
                <button class="btn-quiet w-full"><x-icon name="logout" /> Abmelden</button>
            </form>
        </aside>
    </div>
</x-layouts.app>
