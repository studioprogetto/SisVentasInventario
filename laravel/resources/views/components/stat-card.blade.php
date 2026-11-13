@props(['label', 'value', 'icon' => 'chart', 'tone' => 'blue', 'hint' => ''])
<section {{ $attributes->class(['stat-card', 'tone-'.$tone]) }}><div class="stat-top"><span class="stat-icon"><x-icon :name="$icon"/></span><span class="stat-label">{{ $label }}</span></div><strong class="stat-value">{{ number_format($value) }}</strong><span class="stat-hint">{{ $hint }}</span></section>
