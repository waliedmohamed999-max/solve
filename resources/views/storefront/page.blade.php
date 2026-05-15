@extends('storefront.layout')

@section('title', $page['seo_title'] ?? $page['title'])
@section('description', $page['seo_description'] ?? Str::limit(strip_tags($page['content'] ?? ''), 150))

@section('content')
    <section class="section">
        <div class="wrap panel" style="padding:34px">
            <span class="badge">صفحة المتجر</span>
            <h1 style="font-size:42px;margin:14px 0">{{ $page['title'] ?? 'صفحة' }}</h1>
            <div class="muted" style="font-size:16px;line-height:2;color:#334155">
                {!! nl2br(e($page['content'] ?? '')) !!}
            </div>
        </div>
    </section>
@endsection
