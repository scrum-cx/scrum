{{-- VIEW: all projects across the user's teams. From ProjectController@index. --}}
@php($selectClass = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm')
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Projects</h2>
            @if ($canCreate)
                <a href="{{ route('projects.create') }}"><x-primary-button type="button">New project</x-primary-button></a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Status summary: click a box to filter --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($statuses as $status)
                    <a href="{{ route('projects.index', array_merge($filters, ['status' => $status->value])) }}"
                       class="bg-white shadow-sm sm:rounded-lg p-4 hover:shadow-md transition {{ ($filters['status'] ?? '') === $status->value ? 'ring-2 ring-indigo-500' : '' }}">
                        <p class="text-sm text-gray-500">{{ $status->label() }}</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statusCounts[$status->value] }}</p>
                    </a>
                @endforeach
            </div>

            <form method="GET" class="flex flex-wrap gap-2">
                <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="Search projects…" class="w-72" />
                <select name="team" class="{{ $selectClass }}">
                    <option value="">All my teams</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected((string) ($filters['team'] ?? '') === (string) $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="{{ $selectClass }}">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <x-secondary-button type="submit">Filter</x-secondary-button>
                @if ($hasFilters)
                    <a href="{{ route('projects.index') }}" class="self-center text-sm text-gray-500 hover:underline">Clear</a>
                @endif
            </form>

            @include('projects._table', ['projects' => $projects, 'showTeam' => true,
                'empty' => $hasFilters ? 'No projects match these filters.' : ($teams->isEmpty() ? 'Join or create a team to see its projects.' : 'Your teams have no projects yet.')])

            {{ $projects->links() }}
        </div>
    </div>
</x-app-layout>
