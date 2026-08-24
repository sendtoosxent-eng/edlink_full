<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ ucfirst($type) }} ID Cards</title>
    <style>
        @page {
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 0;
            color: #0f172a;
            background: #f1f5f9;
            font-family: 'Inter', 'DejaVu Sans', sans-serif;
        }

        .card {
            /* Branding Color Tokens */
            --navy: #0f172a;
            --blue: #2563eb;
            --gold: #f59e0b;
            --bg-subtle: #f8fafc;
            --text-muted: #64748b;

            position: relative;
            display: inline-block;
            vertical-align: top;
            width: 54mm;
            height: 85.6mm;
            margin: 2mm 2.5mm;
            overflow: hidden;
            background: #ffffff;
            border: 0.2mm solid #e2e8f0;
            border-radius: 3.5mm;
            box-shadow: 0 1mm 3mm rgba(0, 0, 0, 0.08);
        }

        /* Lanyard Hole Punch Slot */
        .slot {
            position: absolute;
            top: 1.8mm;
            left: 50%;
            width: 10mm;
            height: 2mm;
            margin-left: -5mm;
            background: #ffffff;
            border: 0.2mm solid #cbd5e1;
            border-radius: 1mm;
            z-index: 10;
        }

        /* Header Header Section */
        .header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 23mm;
            padding: 5mm 3mm 1mm;
            background: var(--navy);
            color: #ffffff;
            text-align: center;
        }

        .badge-wrapper {
            position: absolute;
            top: 4.8mm;
            left: 3mm;
            width: 9.5mm;
            height: 9.5mm;
            background: #ffffff;
            border: 0.4mm solid var(--gold);
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .badge-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .badge-initial {
            color: var(--navy);
            font-size: 4.5mm;
            font-weight: 800;
            line-height: 9.5mm;
            text-align: center;
            width: 100%;
        }

        .branding {
            margin-left: 10.5mm;
            text-align: left;
        }

        .school-name {
            margin: 0;
            font-size: 2.8mm;
            font-weight: 800;
            line-height: 3.3mm;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.05mm;
            max-height: 6.6mm;
            overflow: hidden;
        }

        .school-type {
            margin-top: 0.4mm;
            font-size: 1.5mm;
            font-weight: 700;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 0.3mm;
        }

        .motto {
            margin-top: 0.3mm;
            font-size: 1.25mm;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Decorative Curved Accent Bar */
        .accent-bar {
            position: absolute;
            top: 22.8mm;
            left: 0;
            right: 0;
            height: 1.2mm;
            background: var(--gold);
            z-index: 2;
        }

        /* Profile & Body Layout */
        .body-content {
            position: absolute;
            top: 24mm;
            left: 0;
            right: 0;
            bottom: 4mm;
            padding: 2.5mm 3mm;
        }

        .profile-row {
            position: relative;
            height: 25mm;
            margin-bottom: 1.5mm;
        }

        .portrait-frame {
            position: absolute;
            left: 0;
            top: 0;
            width: 24mm;
            height: 24mm;
            padding: 0.6mm;
            background: #ffffff;
            border: 0.5mm solid var(--blue);
            border-radius: 2mm;
            box-shadow: 0 0.8mm 1.5mm rgba(0, 0, 0, 0.1);
        }

        .portrait {
            width: 100%;
            height: 100%;
            background: #e2e8f0;
            border-radius: 1.4mm;
            overflow: hidden;
            text-align: center;
        }

        .portrait img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .portrait .initial {
            color: #94a3b8;
            font-size: 10mm;
            font-weight: 800;
            line-height: 22mm;
        }

        .side-meta {
            position: absolute;
            right: 0;
            top: 0;
            width: 21mm;
            text-align: center;
        }

        .session-badge {
            background: var(--bg-subtle);
            border: 0.2mm solid #cbd5e1;
            border-radius: 1mm;
            padding: 0.6mm 0;
            margin-bottom: 1.2mm;
        }

        .session-label {
            font-size: 1.1mm;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.2mm;
        }

        .session-year {
            font-size: 1.6mm;
            font-weight: 800;
            color: var(--navy);
        }

        .qr-box {
            width: 13.5mm;
            height: 13.5mm;
            margin: 0 auto;
            padding: 0.5mm;
            background: #ffffff;
            border: 0.2mm solid #cbd5e1;
            border-radius: 1mm;
        }

        .qr-box img {
            width: 100%;
            height: 100%;
        }

        /* Identity Banner */
        .identity {
            text-align: center;
            margin-bottom: 2mm;
        }

        .person-name {
            font-size: 2.9mm;
            font-weight: 800;
            color: var(--navy);
            text-transform: uppercase;
            line-height: 3.3mm;
            max-height: 6.6mm;
            overflow: hidden;
            letter-spacing: 0.05mm;
        }

        .person-title {
            display: inline-block;
            margin-top: 0.8mm;
            padding: 0.4mm 2.2mm;
            background: var(--blue);
            color: #ffffff;
            font-size: 1.45mm;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2mm;
            border-radius: 0.6mm;
        }

        /* Details Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1.35mm;
            line-height: 1.7mm;
        }

        .details-table td {
            padding: 0.35mm 0;
            vertical-align: top;
        }

        .details-table .label {
            width: 14mm;
            color: var(--text-muted);
            font-weight: 600;
        }

        .details-table .sep {
            width: 1.5mm;
            color: var(--text-muted);
            font-weight: 600;
        }

        .details-table .value {
            color: #0f172a;
            font-weight: 700;
            word-break: break-word;
        }

        /* Footer & Signature */
        .bottom-section {
            position: absolute;
            bottom: 4.5mm;
            left: 3mm;
            right: 3mm;
            display: flex;
            justify-content: flex-end;
        }

        .signature-area {
            width: 16mm;
            text-align: center;
            float: right;
        }

        .signature {
            height: 3.8mm;
            font-family: 'DejaVu Sans', cursive;
            font-size: 1.6mm;
            font-style: italic;
            color: var(--navy);
            line-height: 3.8mm;
            overflow: hidden;
        }

        .signature-line {
            border-top: 0.2mm solid #94a3b8;
            padding-top: 0.3mm;
            font-size: 0.95mm;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1mm;
        }

        .footer-strip {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3.8mm;
            background: var(--navy);
            color: #ffffff;
            font-size: 1.3mm;
            font-weight: 700;
            line-height: 3.8mm;
            text-align: center;
            letter-spacing: 0.2mm;
            text-transform: uppercase;
            border-top: 0.4mm solid var(--gold);
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

@php
    $badgePath = $school->badge_path
        ? public_path('storage/'.ltrim($school->badge_path, '/'))
        : null;
    $year = $school->currentTerm()?->year ?? now()->year;
    $session = $year.'/'.substr((string) ($year + 1), -2);
@endphp

@foreach($cards as $index => $card)
    @php
        $person = $card['person'];
        $photoPath = $type === 'student'
            ? ($person->photo_path ? public_path('storage/'.ltrim($person->photo_path, '/')) : null)
            : ($person->avatar_path ? public_path('storage/'.ltrim($person->avatar_path, '/')) : null);
        $guardian = $type === 'student'
            ? $person->guardians->sortByDesc('is_primary')->first()
            : null;
        $number = $type === 'student'
            ? $person->admission_no
            : ($person->staff_number ?: 'STF-'.str_pad($person->id, 5, '0', STR_PAD_LEFT));
        $title = $type === 'student'
            ? trim(($person->schoolClass?->name ?? 'Student').' '.($person->stream?->name ?? ''))
            : ($person->designation?->name ?: str($person->role)->replace('_', ' ')->title());
    @endphp

    <article class="card">
        <div class="slot"></div>

        <header class="header">
            <div class="badge-wrapper">
                @if($badgePath && is_file($badgePath))
                    <img src="{{ $badgePath }}" alt="Badge">
                @else
                    <div class="badge-initial">{{ strtoupper(substr($school->name, 0, 1)) }}</div>
                @endif
            </div>
            <div class="branding">
                <h1 class="school-name">{{ $school->name }}</h1>
                <div class="school-type">{{ $school->school_type ?: 'School' }}</div>
                <div class="motto">{{ $school->motto ?: 'Knowledge Today, Success Tomorrow' }}</div>
            </div>
        </header>

        <div class="accent-bar"></div>

        <main class="body-content">
            <div class="profile-row">
                <div class="portrait-frame">
                    <div class="portrait">
                        @if($photoPath && is_file($photoPath))
                            <img src="{{ $photoPath }}" alt="{{ $person->name }}">
                        @else
                            <div class="initial">{{ strtoupper(substr($person->name, 0, 1)) }}</div>
                        @endif
                    </div>
                </div>

                <div class="side-meta">
                    <div class="session-badge">
                        <div class="session-label">Session</div>
                        <div class="session-year">{{ $session }}</div>
                    </div>
                    <div class="qr-box">
                        <img src="{{ $card['qr'] }}" alt="QR Code">
                    </div>
                </div>
            </div>

            <div class="identity">
                <div class="person-name">{{ $person->name }}</div>
                <div class="person-title">{{ $title }}</div>
            </div>

            <table class="details-table">
                @if($type === 'student')
                    <tr>
                        <td class="label">Guardian</td>
                        <td class="sep">:</td>
                        <td class="value">{{ $guardian?->name ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">DOB</td>
                        <td class="sep">:</td>
                        <td class="value">{{ $person->date_of_birth?->format('d-m-Y') ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Blood Group</td>
                        <td class="sep">:</td>
                        <td class="value">{{ $person->blood_group ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Address</td>
                        <td class="sep">:</td>
                        <td class="value">{{ str($person->home_address ?: $guardian?->address ?: '—')->limit(28) }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="label">Staff ID</td>
                        <td class="sep">:</td>
                        <td class="value">{{ $number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Role</td>
                        <td class="sep">:</td>
                        <td class="value">{{ str($person->role)->replace('_', ' ')->title() }}</td>
                    </tr>
                    <tr>
                        <td class="label">Phone</td>
                        <td class="sep">:</td>
                        <td class="value">{{ $person->phone ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Joined</td>
                        <td class="sep">:</td>
                        <td class="value">{{ $person->joined_at?->format('d-m-Y') ?: '—' }}</td>
                    </tr>
                @endif
            </table>

            <div class="bottom-section">
                <div class="signature-area">
                    <div class="signature">{{ $school->principal_name ?: 'Head Teacher' }}</div>
                    <div class="signature-line">Auth Sign</div>
                </div>
            </div>
        </main>

        <footer class="footer-strip">
            ID NO: {{ $number }}
        </footer>
    </article>

    @if(($index + 1) % 9 === 0 && !$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>