{{-- VIEW: one project. From ProjectController@show. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <a href="{{ route('teams.show', $project->team) }}" class="text-sm text-gray-500 hover:underline">← {{ $project->team->name }}</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight flex items-center gap-3">
                    {{ $project->name }}
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $project->status->badgeClasses() }}">{{ $project->status->label() }}</span>
                </h2>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($canUpdate)
                    <a href="{{ route('projects.edit', $project) }}"><x-secondary-button type="button">Edit</x-secondary-button></a>
                @endif
                @if ($canDelete)
                    <form method="POST" action="{{ route('projects.destroy', $project) }}"
                          onsubmit="return confirm('Delete the {{ addslashes($project->name) }} project?')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>Delete</x-danger-button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-900">About this project</h3>
                <p class="mt-2 text-gray-600 whitespace-pre-line">{{ $project->description ?: 'No description.' }}</p>
            </div>

            <dl class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4 text-sm">
                <div>
                    <dt class="text-gray-500">Team</dt>
                    <dd class="font-medium text-gray-900"><a href="{{ route('teams.show', $project->team) }}" class="hover:underline">{{ $project->team->name }}</a></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Project lead</dt>
                    <dd class="font-medium text-gray-900">{{ $project->lead?->name ?? 'Not assigned' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Start date</dt>
                    <dd class="font-medium text-gray-900">{{ $project->start_date?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Due date</dt>
                    <dd class="font-medium {{ $project->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $project->due_date?->format('d M Y') ?? '—' }}
                        @if ($project->isOverdue()) (overdue) @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Created by</dt>
                    <dd class="font-medium text-gray-900">{{ $project->creator?->name ?? 'Deleted user' }} · {{ $project->created_at->format('d M Y') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-app-layout>
