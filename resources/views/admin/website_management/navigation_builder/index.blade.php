@extends('admin.layouts.app')

@section('title', 'Navigation Builder')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Page Header --}}
    <div class="bg-white rounded-md p-5 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mb-1">
                <x-heroicon-o-bars-3 class="w-8 h-8 text-blue-600"/>
                <h1 class="text-2xl font-semibold text-gray-900">Navigation Builder</h1>
            </div>
            <p class="text-gray-500 text-sm">Manage all website menus — header, footer, mobile, and more.</p>
        </div>
        <a href="{{ route('admin.website-management.navigation-builder.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-md text-sm font-medium shadow-sm transition-colors whitespace-nowrap">
            <x-heroicon-o-plus class="w-4 h-4"/>
            New Menu
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm mb-6 flex items-center gap-2">
        <x-heroicon-o-check-circle class="w-4 h-4 shrink-0"/>
        {{ session('success') }}
    </div>
    @endif

    {{-- Menus Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if($menus->isEmpty())
            <div class="px-6 py-16 text-center">
                <x-heroicon-o-bars-3 class="w-12 h-12 text-gray-300 mx-auto mb-3"/>
                <p class="text-gray-500 text-sm font-medium">No menus yet.</p>
                <p class="text-gray-400 text-xs mt-1">Create your first menu — e.g. "Primary Header" or "Footer Links".</p>
                <a href="{{ route('admin.website-management.navigation-builder.create') }}"
                   class="mt-4 inline-flex items-center gap-1.5 text-sm text-blue-600 hover:underline">
                    <x-heroicon-o-plus class="w-4 h-4"/> Create first menu
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Menu Key</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Items</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($menus as $menu)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-gray-900">{{ $menu->name }}</p>
                                @if($menu->description)
                                    <p class="text-xs text-gray-400 truncate max-w-xs">{{ $menu->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <code class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $menu->menu_key }}</code>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600">
                                <span class="inline-flex items-center gap-1">
                                    <x-heroicon-o-list-bullet class="w-3.5 h-3.5 text-gray-400"/>
                                    {{ $menu->items_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $menu->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $menu->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 text-xs font-mono">{{ $menu->display_order }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.website-management.navigation-builder.edit', $menu->unique_id) }}"
                                       class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors">
                                        <x-heroicon-o-pencil-square class="w-3.5 h-3.5"/>
                                        Build
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.website-management.navigation-builder.destroy', $menu->unique_id) }}"
                                          onsubmit="return confirm('Delete menu &quot;{{ $menu->name }}&quot; and all its items?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 border border-red-200 text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-md text-xs font-medium transition-colors">
                                            <x-heroicon-o-trash class="w-3.5 h-3.5"/>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Frontend Integration Note --}}
    <div class="mt-6 bg-blue-50 border border-blue-100 rounded-lg p-4 text-sm text-blue-800">
        <p class="font-semibold mb-1 flex items-center gap-1.5">
            <x-heroicon-o-information-circle class="w-4 h-4"/>
            How to use menus in frontend templates
        </p>
        <p class="text-blue-700 text-xs leading-relaxed">
            Inject menus via <code class="bg-blue-100 px-1 rounded">app(\App\Services\Navigation\NavigationService::class)->getTree('your_menu_key')</code>
            &mdash; returns a nested array. Results are cached for 1 hour; cache clears automatically on every edit.
        </p>
    </div>

</div>

@endsection
