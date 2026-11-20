{{-- VIEW: teams the user belongs to (admins: all teams). From TeamController@index. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $isAdmin ? 'All teams' : 'My teams' }}</h2>
            <a href="{{ route('teams.create') }}"><x-primary-button type="button">New team</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($teams->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-10 text-center text-gray-600 space-y-2">
                    <p class="font-medium text-gray-900">You're not in any team yet.</p>
                    <p>Create a team, or ask a team owner or manager for an invite link.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($teams as $team)
                        <a href="{{ route('teams.show', $team) }}" class="block bg-white shadow-sm sm:rounded-lg p-5 hover:shadow-md transition">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-semibold text-gray-900">{{ $team->name }}</h3>
                                @if ($team->my_role)
                                    <span class="shrink-0 inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $team->my_role->badgeClasses() }}">{{ $team->my_role->label() }}</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500 line-clamp-2 min-h-[2.5rem]">{{ $team->description ?: 'No description.' }}</p>
                            <div class="mt-4 flex gap-4 text-sm text-gray-600">
                                <span><strong>{{ $team->members_count }}</strong> {{ Str::plural('member', $team->members_count) }}</span>
                                <span><strong>{{ $team->projects_count }}</strong> {{ Str::plural('project', $team->projects_count) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
