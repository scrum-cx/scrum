{{-- Expects $projects, $showTeam (bool), $empty (message). --}}
@if ($projects->isEmpty())
    <div class="bg-white shadow-sm sm:rounded-lg p-10 text-center text-gray-500">{{ $empty }}</div>
@else
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3">Project</th>
                    @if ($showTeam)<th class="px-6 py-3">Team</th>@endif
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Lead</th>
                    <th class="px-6 py-3">Due</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($projects as $project)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <a href="{{ route('projects.show', $project) }}" class="font-medium text-indigo-700 hover:underline">{{ $project->name }}</a>
                        </td>
                        @if ($showTeam)
                            <td class="px-6 py-3 text-gray-600">
                                <a href="{{ route('teams.show', $project->team) }}" class="hover:underline">{{ $project->team->name }}</a>
                            </td>
                        @endif
                        <td class="px-6 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $project->status->badgeClasses() }}">{{ $project->status->label() }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ $project->lead?->name ?? '—' }}</td>
                        <td class="px-6 py-3 {{ $project->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                            {{ $project->due_date?->format('d M Y') ?? '—' }}
                            @if ($project->isOverdue()) <span class="text-xs">(overdue)</span> @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
