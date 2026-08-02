<section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/70 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-600">Tenant feature control</p>
            <h2 class="mt-1 text-base font-black text-slate-900">SMS gateway</h2>
            <p class="mt-1 text-xs text-slate-500">Enable SMS and store gateway credentials for this school only.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-[10px] font-black uppercase tracking-wider {{ $smsConfiguration->enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
            <span class="h-2 w-2 rounded-full {{ $smsConfiguration->enabled ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
            {{ $smsConfiguration->enabled ? 'Enabled' : 'Disabled' }}
        </span>
    </div>

    <form method="POST" action="{{ route('platform.schools.sms-configuration.update', $school) }}" class="space-y-5 p-6">
        @csrf @method('PUT')
        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $smsConfiguration->enabled)) class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-400">
            <span><b class="block text-xs text-slate-900">Allow this school to send SMS</b><span class="mt-1 block text-[11px] leading-5 text-slate-500">Turning this off blocks SMS for this tenant without deleting its credentials.</span></span>
        </label>

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="block"><span class="text-xs font-bold text-slate-700">Provider</span><select name="provider" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">@foreach(['africastalking'=>'Africa’s Talking','twilio'=>'Twilio','custom'=>'Custom HTTP gateway'] as $value=>$label)<option value="{{ $value }}" @selected(old('provider', $smsConfiguration->provider)===$value)>{{ $label }}</option>@endforeach</select></label>
            <label class="block"><span class="text-xs font-bold text-slate-700">Sender ID</span><input name="sender_id" value="{{ old('sender_id', $smsConfiguration->sender_id) }}" placeholder="EDLINK" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold uppercase">@error('sender_id')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror</label>
            <label class="block"><span class="text-xs font-bold text-slate-700">API username / Account SID</span><input name="api_username" value="{{ old('api_username', $smsConfiguration->api_username) }}" autocomplete="off" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">@error('api_username')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror</label>
            <label class="block"><span class="text-xs font-bold text-slate-700">API key / Auth token</span><input type="password" name="api_key" autocomplete="new-password" placeholder="{{ $smsConfiguration->api_key ? 'Saved — leave blank to keep it' : 'Enter API key' }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">@error('api_key')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror</label>
            <label class="block sm:col-span-2"><span class="text-xs font-bold text-slate-700">Custom gateway endpoint <span class="font-normal text-slate-400">(custom provider only)</span></span><input type="url" name="endpoint" value="{{ old('endpoint', $smsConfiguration->endpoint) }}" placeholder="https://gateway.example.com/v1/messages" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">@error('endpoint')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror</label>
            <label class="block sm:col-span-2"><span class="text-xs font-bold text-slate-700">Webhook secret <span class="font-normal text-slate-400">(optional)</span></span><input type="password" name="webhook_secret" autocomplete="new-password" placeholder="{{ $smsConfiguration->webhook_secret ? 'Saved — leave blank to keep it' : 'Delivery callback secret' }}" class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold"></label>
        </div>

        <div class="flex items-center justify-between gap-4 border-t border-slate-100 pt-5">
            <p class="text-[10px] leading-4 text-slate-400">Secrets are encrypted at rest and never displayed after saving.</p>
            <button class="rounded-xl bg-slate-900 px-5 py-3 text-xs font-black text-white hover:bg-slate-800">Save SMS settings</button>
        </div>
    </form>
</section>
