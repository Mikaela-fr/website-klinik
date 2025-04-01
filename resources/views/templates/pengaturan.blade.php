@extends('templates.base')

@section('body')
    <h1 class="text-4xl text-center my text-primary my-8">@yield('page_title')</h1>
    <main class="mx-auto max-w-6xl p-5">
        @yield('pengaturan_content')
    </main>
@endsection