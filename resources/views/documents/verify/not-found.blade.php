@extends('documents.verify.layouts.master')

@section('page-title', __('messages.document_verification') . ' — ' . __('messages.document_not_found'))

@section('content')
    <div class="flex flex-col items-center justify-center py-20 text-center">

        <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-50">
            <svg class="h-10 w-10 text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-slate-800">
            {{ __('messages.not_authentic_document') }}
        </h2>

        <p class="mt-3 max-w-sm text-sm leading-relaxed text-slate-500">
            {{ __('messages.document_not_found_description') }}
        </p>

    </div>
@endsection
