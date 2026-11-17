@props(['nombre'])
@php($normalizado = Illuminate\Support\Str::upper(trim($nombre)))
<span @class(['grade-badge', 'grade-badge-dan' => preg_match('/\bDAN\b/u', $normalizado), 'grade-badge-kyu' => preg_match('/\bKYU\b/u', $normalizado)])>{{ $nombre }}</span>
