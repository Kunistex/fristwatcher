<!DOCTYPE html>
<html lang="de-CH">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 22mm 22mm 20mm 25mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; line-height: 1.5; color: #1d2b34; }
    .sender { font-size: 9pt; color: #54646d; margin-bottom: 12mm; white-space: pre-line; }
    /* Schweizer Fensterkuvert C5/6 rechts: Adressfeld rechts */
    .recipient { margin-left: 95mm; min-height: 32mm; white-space: pre-line; }
    .registered { margin-left: 95mm; font-size: 9pt; font-weight: bold; letter-spacing: .02em; margin-bottom: 2mm; }
    .date { margin-top: 12mm; margin-bottom: 8mm; }
    .subject { font-weight: bold; margin-bottom: 8mm; }
    .body { white-space: pre-line; }
    .signature { margin-top: 18mm; }
</style>
</head>
<body>
    <div class="sender">{{ $sender }}</div>
    @if (!empty($registered))
        <div class="registered">Einschreiben</div>
    @endif
    <div class="recipient">{{ $recipient }}</div>
    <div class="date">{{ $place }}, {{ \Carbon\CarbonImmutable::parse($date)->translatedFormat('j. F Y') }}</div>
    <div class="subject">{{ $subject }}</div>
    <div class="body">{{ $body }}</div>
    <div class="signature">{{ $signature }}</div>
</body>
</html>
