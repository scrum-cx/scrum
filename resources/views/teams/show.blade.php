{{-- VIEW: one team — its projects (F03), members and invite links (F02). From TeamController@show. --}}
@php($selectClass = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm')
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <a href="{{ route('teams.index') }}" class="text-sm text-gray-500 hover:underline">← Teams</a>
                <h2 class="mt-1 font-semibold text-xl text-gray-800 leading-tight flex items-center gap-3">
                    {{ $team->name }}
                    @if ($myRole)
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $myRole->badgeClasses() }}">You: {{ $myRole->label() }}</span>
                    @endif
                </h2>
                @if ($team->description)
                    <p class="mt-1 text-sm text-gray-600 max-w-3xl whitespace-pre-line">{{ $team->description }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if ($canUpdate)
                    <a href="{{ route('teams.edit', $team) }}"><x-secondary-button type="button">Edit</x-secondary-button></a>
                @endif
                @if ($canDelete)
                    <form method="POST" action="{{ route('teams.destroy', $team) }}"
                          onsubmit="return confirm('Delete the {{ addslashes($team->name) }} team?')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>Delete</x-danger-button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- ===== Projects (F03) ===== --}}
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Projects</h3>
                    @if ($canCreateProject)
                        <a href="{{ route('projects.create', ['team' => $team->id]) }}"><x-primary-button type="button">New project</x-primary-button></a>
                    @endif
                </div>
                @if ($hasProjects)
                    <form method="GET" class="flex flex-wrap gap-2">
                        <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="Search projects…" class="w-72" />
                        <select name="status" class="{{ $selectClass }}">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <x-secondary-button type="submit">Filter</x-secondary-button>
                    </form>
                @endif
                @include('projects._table', ['projects' => $projects, 'showTeam' => false,
                    'empty' => $hasProjects ? 'No projects match these filters.' : 'This team has no projects yet.'])
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                {{-- ===== Members ===== --}}
                <section class="lg:col-span-3 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">Members <span class="text-gray-400 font-normal">({{ $members->count() }})</span></h3>
                    <div class="bg-white shadow-sm sm:rounded-lg divide-y divide-gray-100">
                        @foreach ($members as $member)
                            <div class="flex items-center justify-between gap-4 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ $member['name'] }} @if ($member['isMe'])<span class="text-gray-400 font-normal">(you)</span>@endif</p>
                                    <p class="text-sm text-gray-500 truncate">{{ $member['email'] }} · joined {{ $member['joined']?->format('d M Y') }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if ($member['canChangeRole'])
                                        <form method="POST" action="{{ route('teams.members.update', [$team, $member['id']]) }}" x-data>
                                            @csrf
                                            @method('PUT')
                                            <select name="role" class="{{ $selectClass }}" aria-label="Role of {{ $member['name'] }}"
                                                    x-on:change="if ($event.target.value === 'owner' && ! confirm('Make {{ addslashes($member['name']) }} the owner? You will become a manager.')) { $event.target.value = '{{ $member['role']->value }}'; return; } $el.form.submit()">
                                                @foreach ($teamRoles as $role)
                                                    <option value="{{ $role->value }}" @selected($member['role'] === $role)>{{ $role->label() }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $member['role']->badgeClasses() }}">{{ $member['role']->label() }}</span>
                                    @endif
                                    @if ($member['canRemove'])
                                        <form method="POST" action="{{ route('teams.members.destroy', [$team, $member['id']]) }}"
                                              onsubmit="return confirm('{{ $member['isMe'] ? 'Leave this team?' : 'Remove '.addslashes($member['name']).' from the team?' }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm text-red-600 hover:underline">{{ $member['isMe'] ? 'Leave' : 'Remove' }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('role') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </section>

                {{-- ===== Invite links (owners & managers) ===== --}}
                @if ($canInvite)
                    <section class="lg:col-span-2 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">Invite links</h3>

                        <form method="POST" action="{{ route('teams.invitations.store', $team) }}" class="bg-white shadow-sm sm:rounded-lg p-5 space-y-4">
                            @csrf
                            <p class="text-sm text-gray-600">Anyone with the link can join this team after signing in.</p>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <x-input-label for="invite_role" value="Joins as" />
                                    <select id="invite_role" name="role" class="mt-1 w-full {{ $selectClass }}">
                                        @foreach ($inviteRoles as $role)
                                            <option value="{{ $role->value }}" @selected(old('role', 'member') === $role->value)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="expires_in_days" value="Expires in" />
                                    <select id="expires_in_days" name="expires_in_days" class="mt-1 w-full {{ $selectClass }}">
                                        @foreach ($expiryDays as $days)
                                            <option value="{{ $days }}" @selected((int) old('expires_in_days', 7) === $days)>{{ $days }} {{ Str::plural('day', $days) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="max_uses" value="Max uses" />
                                    <x-text-input id="max_uses" name="max_uses" type="number" min="1" max="500" placeholder="No limit" class="mt-1 w-full text-sm" :value="old('max_uses')" />
                                </div>
                            </div>
                            <x-input-error :messages="array_merge($errors->get('role'), $errors->get('expires_in_days'), $errors->get('max_uses'))" />
                            <x-primary-button>Create invite link</x-primary-button>
                        </form>

                        @forelse ($invitations as $invitation)
                            <div class="bg-white shadow-sm sm:rounded-lg p-4 space-y-2" x-data="{ copied: false }">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $invitation->role->badgeClasses() }}">{{ $invitation->role->label() }}</span>
                                    <span class="text-gray-500">{{ $invitation->usesLabel() }} · expires {{ $invitation->expires_at->diffForHumans() }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <input type="text" readonly value="{{ $invitation->url() }}" class="flex-1 min-w-0 text-xs border-gray-300 rounded-md bg-gray-50" onclick="this.select()">
                                    <x-secondary-button type="button" x-on:click="navigator.clipboard.writeText('{{ $invitation->url() }}'); copied = true; setTimeout(() => copied = false, 2000)">
                                        <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                                    </x-secondary-button>
                                </div>
                                <div class="flex items-center justify-between text-xs text-gray-400">
                                    <span>Created by {{ $invitation->creator?->name ?? 'deleted user' }}</span>
                                    <form method="POST" action="{{ route('teams.invitations.destroy', [$team, $invitation]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline">Revoke</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No active invite links.</p>
                        @endforelse
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
