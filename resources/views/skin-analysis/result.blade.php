@extends('layouts.app')

@section('title', 'Your Skin Analysis Results')

@section('content')

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

@php
  $label          = $result['label']             ?? 'Unknown';
  $score          = $result['severity_score']    ?? 0;
  $confidence     = $result['confidence']        ?? 0;
  $treatment      = $result['treatment']         ?? [];
  $headline       = $treatment['headline']       ?? '';
  $desc           = $treatment['description']    ?? '';
  $recommended    = $treatment['recommended']    ?? [];
  $urgency        = $treatment['urgency']        ?? 'low';
  $condSummary    = $result['condition_summary'] ?? [];
  $meterPct       = ($score / 4) * 100;

  $urgencyMap = [
    'low'    => ['bg' => '#f0f7e0', 'text' => '#4a6e10', 'dot' => '#80a833'],
    'medium' => ['bg' => '#faeeda', 'text' => '#854f0b', 'dot' => '#ef9f27'],
    'high'   => ['bg' => '#fcebeb', 'text' => '#a32d2d', 'dot' => '#e24b4a'],
  ];
  $uc = $urgencyMap[$urgency] ?? $urgencyMap['low'];

  $severityLabels = ['Clear', 'Almost Clear', 'Mild', 'Moderate', 'Severe'];
  $severityColors = ['#80a833','#b1c233','#ef9f27','#d85a30','#e24b4a'];
  $meterColor = $severityColors[$score] ?? '#888';
@endphp

<div class="sr-page">

  {{-- Back to home --}}
  <a href="{{ url('/') }}" class="sr-back-btn">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="15 18 9 12 15 6"/>
    </svg>
    Back to Home
  </a>

  <div class="sr-header">
    <p class="kicker">AI-POWERED RESULTS</p>
    <h1 class="sr-title">Your Skin Analysis</h1>
  </div>

  <div class="sr-layout">

    {{-- LEFT: photo + severity + conditions --}}
    <div class="sr-left">

      <div class="sr-photo-card">
        @if(!empty($photoUrl))
          <img src="{{ $photoUrl }}" alt="Analyzed photo" class="sr-photo"/>
        @endif
        <div class="sr-label-row">
          <span class="sr-pill" style="background:{{ $uc['bg'] }};color:{{ $uc['text'] }}">
            <span class="sr-dot" style="background:{{ $uc['dot'] }}"></span>
            {{ $label }}
          </span>
          <span class="sr-confidence">{{ number_format($confidence, 1) }}% confidence</span>
        </div>
      </div>

      <div class="sr-card">
        <p class="sr-card-label">Severity scale</p>
        <div class="sr-meter-track">
          <div class="sr-meter-fill" style="width:{{ max(4, $meterPct) }}%;background:{{ $meterColor }}"></div>
        </div>
        <div class="sr-meter-ticks">
          @foreach($severityLabels as $i => $sl)
            <span style="{{ $i === $score ? 'color:'.$meterColor.';font-weight:700' : 'color:#b5a898' }};font-size:11px;">
              {{ $sl }}
            </span>
          @endforeach
        </div>
      </div>

      <div class="sr-card sr-summary-card">
        <h2 class="sr-headline">{{ $headline }}</h2>
        <p class="sr-desc">{{ $desc }}</p>
      </div>

      <div class="sr-card sr-conditions-card">
        <p class="sr-card-label">What we detected on your skin</p>
        @if(!empty($condSummary))
          <div class="sr-conditions-grid">
            @foreach($condSummary as $cond)
            <div class="sr-cond-item">
              <div class="sr-cond-header">
                <span class="sr-cond-icon" style="background:{{ $cond['color'] }}18; border-color:{{ $cond['color'] }}40;">
                  {{ $cond['icon'] }}
                </span>
                <strong class="sr-cond-label" style="color:{{ $cond['color'] }}">{{ $cond['label'] }}</strong>
              </div>
              <p class="sr-cond-desc">{{ $cond['description'] }}</p>
            </div>
            @endforeach
          </div>
        @else
          <div class="sr-no-conditions">
            <span style="font-size:28px">✨</span>
            <p>No specific skin concerns detected. Your skin looks healthy!</p>
          </div>
        @endif
      </div>

      <details class="sr-details">
        <summary>View full model breakdown</summary>
        <div class="sr-breakdown">
          @foreach($result['all_predictions'] ?? [] as $p)
            <div class="sr-brow">
              <span class="sr-brow-label">{{ $p['condition'] }}</span>
              <div class="sr-brow-track">
                <div class="sr-brow-fill" style="width:{{ $p['confidence'] }}%"></div>
              </div>
              <span class="sr-brow-pct">{{ number_format($p['confidence'], 1) }}%</span>
            </div>
          @endforeach
        </div>
      </details>

    </div>

    {{-- RIGHT: treatments + CTA --}}
    <div class="sr-right">

      <div class="sr-card sr-treatments-card">
        <p class="sr-card-label">Recommended treatments for you</p>
        <ul class="sr-treatments">
          @foreach($recommended as $t)
            <li class="sr-treatment">
              <span class="sr-treatment-check">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
              </span>
              {{ $t }}
            </li>
          @endforeach
        </ul>
      </div>

      {{-- CTA card --}}
      <div class="sr-cta-card">
        <h3 class="sr-cta-title">Ready to get started?</h3>
        <p class="sr-cta-sub">Book a free consultation with our skin specialists and get a personalized treatment plan tailored to your results.</p>
        <button onclick="window.location.href='/?login=true'" class="sr-book-btn" style="border:none;cursor:pointer;width:100%;">Book an Appointment</button>
        <a href="{{ route('skin-analysis.index') }}" class="sr-retake-link">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3"/>
          </svg>
          Analyze another photo
        </a>
      </div>

      <p class="sr-privacy">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        Your photo was not stored. Results are for guidance only and not a medical diagnosis.
      </p>

    </div>
  </div>
</div>

<style>
.sr-page {
  font-family: 'Poppins', sans-serif;
  max-width: 900px;
  margin: 0 auto;
  padding: 40px 24px 60px;
}
.sr-header { margin-bottom: 28px; }
.sr-title {
  font-family: 'Playfair Display', serif;
  font-size: 42px; color: #5d595f; margin: 4px 0 0; font-weight: 700;
}
.sr-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 28px;
  align-items: start;
}
.sr-back-btn {
  display: inline-flex; align-items: center; gap: 6px;
  background: none; border: 1.5px solid #c5d98a; color: #5a7220;
  border-radius: 20px; padding: 7px 18px; font-size: 13px;
  font-weight: 500; font-family: 'Poppins', sans-serif;
  cursor: pointer; text-decoration: none; margin-bottom: 20px;
  transition: background .15s;
}
.sr-back-btn:hover { background: #f0f7e0; }
.sr-photo-card {
  border-radius: 18px; overflow: hidden;
  box-shadow: 0 6px 14px rgba(0,0,0,0.08), inset 0 0 0 2px #e0d8cf;
  margin-bottom: 16px; background: #faf9f8;
}
.sr-photo {
  display: block;
  max-width: 100%;
  max-height: 280px;
  width: auto;
  height: auto;
  margin: 0 auto;
  object-fit: contain;
}
.sr-label-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 16px;
}
.sr-pill {
  display: inline-flex; align-items: center; gap: 6px;
  border-radius: 999px; padding: 5px 12px; font-size: 13px; font-weight: 600;
}
.sr-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.sr-confidence { font-size: 12px; color: #9c8f83; }
.sr-card {
  background: #faf9f8; border-radius: 18px; padding: 18px 20px;
  margin-bottom: 16px;
  box-shadow: 0 6px 14px rgba(0,0,0,0.06), inset 0 0 0 2px #e0d8cf;
}
.sr-card-label {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .1em; color: #9c8f83; margin: 0 0 12px;
}
.sr-meter-track {
  height: 10px; background: #e8e9eb;
  border-radius: 999px; overflow: hidden; margin-bottom: 8px;
}
.sr-meter-fill {
  height: 100%; border-radius: 999px;
  transition: width .9s cubic-bezier(.4,0,.2,1);
}
.sr-meter-ticks { display: flex; justify-content: space-between; }
.sr-summary-card {
  background: #f0f7e0;
  box-shadow: 0 6px 14px rgba(0,0,0,0.06), inset 0 0 0 2px #c5d98a;
}
.sr-headline {
  font-family: 'Playfair Display', serif;
  font-size: 20px; color: #2e2420; margin: 0 0 8px; font-weight: 700;
}
.sr-desc { font-size: 13.5px; color: #5a5248; margin: 0; line-height: 1.6; }
.sr-conditions-card { padding: 20px; }
.sr-conditions-grid { display: flex; flex-direction: column; gap: 11px; }
.sr-cond-item {
  padding: 12px 14px;
  border-radius: 12px;
  background: #fff;
  border: 1.5px solid #e8e2db;
  transition: border-color .2s, box-shadow .2s;
}
.sr-cond-item:hover {
  border-color: #b5d060;
  box-shadow: 0 3px 10px rgba(128,168,51,0.1);
}
.sr-cond-header {
  display: flex; align-items: center; gap: 10px; margin-bottom: 5px;
}
.sr-cond-icon {
  width: 32px; height: 32px; border-radius: 8px;
  border: 1.5px solid;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; flex-shrink: 0; line-height: 1;
}
.sr-cond-label { font-size: 13.5px; font-weight: 600; }
.sr-cond-desc {
  font-size: 12px; color: #6b5f57; margin: 0;
  line-height: 1.55; padding-left: 42px;
}
.sr-no-conditions {
  display: flex; flex-direction: column; align-items: center;
  gap: 6px; padding: 8px 0; text-align: center;
}
.sr-no-conditions p { font-size: 13px; color: #80a833; margin: 0; }
.sr-treatments { list-style: none; margin: 0; padding: 0; }
.sr-treatment {
  display: flex; align-items: flex-start; gap: 10px;
  font-size: 14px; color: #2e2420; margin-bottom: 10px;
}
.sr-treatment-check {
  width: 22px; height: 22px; border-radius: 50%; background: #80a833;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; margin-top: 1px;
}
.sr-cta-card {
  background: linear-gradient(180deg, #f0f7e0 0%, #d4e89a 100%);
  border-radius: 18px; padding: 24px; margin-bottom: 14px;
  box-shadow: 0 10px 0 rgba(0,0,0,0.08);
}
.sr-cta-title {
  font-family: 'Playfair Display', serif;
  font-size: 22px; color: #1b140f; margin: 0 0 8px;
}
.sr-cta-sub { font-size: 13px; color: #3a4a1a; line-height: 1.6; margin: 0 0 18px; }
.sr-book-btn {
  display: block; text-align: center;
  background: #80a833; color: #fff;
  padding: 12px 28px; border-radius: 30px;
  text-decoration: none; font-weight: 600; font-size: 14px;
  transition: background .2s; margin-bottom: 10px;
}
.sr-book-btn:hover { background: #6b9228; }
.sr-retake-link {
  display: flex; align-items: center; justify-content: center; gap: 5px;
  font-size: 13px; color: #5a7220; text-decoration: none; font-weight: 500;
}
.sr-retake-link:hover { text-decoration: underline; }
.sr-details { margin-bottom: 0; }
.sr-details summary {
  cursor: pointer; font-size: 12px; color: #9c8f83;
  padding: 6px 0; list-style: none;
}
.sr-details summary::-webkit-details-marker { display: none; }
.sr-breakdown { padding: 12px 0 0; display: flex; flex-direction: column; gap: 8px; }
.sr-brow { display: flex; align-items: center; gap: 8px; }
.sr-brow-label { font-size: 12px; color: #5a5248; min-width: 72px; }
.sr-brow-track { flex: 1; height: 6px; background: #e8e9eb; border-radius: 999px; overflow: hidden; }
.sr-brow-fill { height: 100%; background: #80a833; border-radius: 999px; }
.sr-brow-pct { font-size: 11px; color: #9c8f83; min-width: 36px; text-align: right; }
.sr-privacy {
  display: flex; align-items: flex-start; gap: 6px;
  font-size: 11px; color: #a09488; line-height: 1.5;
}
@media (max-width: 820px) {
  .sr-layout { grid-template-columns: 1fr; }
  .sr-title { font-size: 28px; }
}
</style>

@endsection