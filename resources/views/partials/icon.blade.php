<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@if($name === 'home')
<path d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-8h6v8"/>
@elseif($name === 'cache')
<path d="m14 3 7 7M17 6 7 16M3 14l7 7 4-7-5-5-6 5ZM3 21h7"/>
@elseif($name === 'view')
<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
@elseif($name === 'edit')
<path d="m16 3 5 5-12 12-6 1 1-6L16 3ZM13 6l5 5"/>
@elseif($name === 'delete')
<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>
@elseif($name === 'close')
<path d="M10 3H4v18h6M14 8l5 4-5 4M8 12h11"/>
@elseif($name === 'personas')
<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
@elseif($name === 'grados')
<path d="m12 3 10 5-10 5L2 8l10-5ZM6 10v6c3 3 9 3 12 0v-6M22 8v8"/>
@elseif($name === 'eventos')
<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2M8 18h2"/>
@else
<rect x="3" y="5" width="18" height="15" rx="2"/><path d="M8 3v4M16 3v4M8 13l3 3 5-5"/>
@endif
</svg>
