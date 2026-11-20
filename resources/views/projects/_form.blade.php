{{--
    Shared by create and edit. Expects $project, $teams, $statuses, $canAssignLead,
    $leadOptions (team id => [{id, name}]) so the lead list follows the chosen team.
--}}
@php($inputClass = 'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm')

<div x-data="{ teamId: '{{ old('team_id', $project->team_id) }}', leads: @js($leadOptions), leadId: '{{ old('lead_id', $project->lead_id) }}' }" class="space-y-6">
    <div>
        <x-input-label for="team_id" value="Team" />
        @if ($project->exists)
            <p class="mt-1 text-gray-900">{{ $project->team->name }} <span class="text-sm text-gray-500">(a project stays in its team)</span></p>
        @else
            <select id="team_id" name="team_id" class="{{ $inputClass }}" required x-model="teamId">
                @foreach ($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('team_id')" class="mt-2" />
        @endif
    </div>

    <div>
        <x-input-label for="name" value="Project name" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $project->name)" required maxlength="120" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Description (optional)" />
        <textarea id="description" name="description" rows="4" maxlength="5000" class="{{ $inputClass }}">{{ old('description', $project->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="{{ $inputClass }}" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $project->status?->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="start_date" value="Start date" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', $project->start_date?->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="due_date" value="Due date" />
            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date', $project->due_date?->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
        </div>
    </div>

    @if ($canAssignLead)
        <div>
            <x-input-label for="lead_id" value="Project lead" />
            <select id="lead_id" name="lead_id" class="{{ $inputClass }}" x-model="leadId">
                <option value="">No lead yet</option>
                <template x-for="person in (leads[teamId] || [])" :key="person.id">
                    <option :value="String(person.id)" x-text="person.name" :selected="String(person.id) === leadId"></option>
                </template>
            </select>
            <p class="mt-1 text-sm text-gray-500">Any member of the team can lead the project.</p>
            <x-input-error :messages="$errors->get('lead_id')" class="mt-2" />
        </div>
    @else
        <div>
            <x-input-label value="Project lead" />
            <p class="mt-1 text-gray-900">{{ $project->lead?->name ?? 'No lead' }} <span class="text-sm text-gray-500">(only the team owner or a manager can change this)</span></p>
        </div>
    @endif
</div>
