<div class="flex flex-col">
    {{-- Step Indicator --}}
    <div class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
        @foreach (['Basics', 'Checkpoints', 'Questions', 'Rules', 'Review'] as $i => $label)
            <button
                wire:click="goToStep({{ $i + 1 }})"
                class="flex flex-col items-center gap-1 text-xs {{ $step === $i + 1 ? 'text-forest-600 dark:text-forest-400' : ($step > $i + 1 ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500') }}"
            >
                <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold
                    {{ $step === $i + 1 ? 'bg-forest-600 text-white' : ($step > $i + 1 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-700') }}">
                    @if ($step > $i + 1) ✓ @else {{ $i + 1 }} @endif
                </span>
                <span>{{ $label }}</span>
            </button>
        @endforeach
    </div>

    <div class="flex-1 overflow-y-auto p-4">
        {{-- Step 1: Basics --}}
        @if ($step === 1)
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('quests.quest') }} {{ __('general.settings') }}</h2>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quests.title') }} *</label>
                    <input type="text" wire:model="title" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('quests.title') }}" />
                    @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quests.description') }}</label>
                    <textarea wire:model="description" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('quests.description') }}"></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.category') }} *</label>
                    <select wire:model="categoryId" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('general.all_categories') }}</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('categoryId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.difficulty') }} *</label>
                    <select wire:model="difficulty" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">{{ __('general.all_difficulties') }}</option>
                        @foreach (\App\Enums\Difficulty::cases() as $diff)
                            <option value="{{ $diff->value }}">{{ ucfirst($diff->value) }}</option>
                        @endforeach
                    </select>
                    @error('difficulty') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.avatar') }}</label>
                    <input type="file" wire:model="coverImage" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-forest-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-forest-700 dark:text-gray-400" />
                    @error('coverImage') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        {{-- Step 2: Checkpoints --}}
        @if ($step === 2)
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('quests.checkpoints') }}</h2>
                    <button wire:click="addCheckpoint" class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-medium text-white">+ {{ __('general.create') }}</button>
                </div>

                @foreach ($checkpoints as $cpIndex => $checkpoint)
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700" wire:key="cp-{{ $cpIndex }}">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('quests.checkpoint') }} {{ $cpIndex + 1 }}</h3>
                            @if (count($checkpoints) > 1)
                                <button wire:click="removeCheckpoint({{ $cpIndex }})" class="text-xs text-red-500">{{ __('general.delete') }}</button>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <div>
                                <input type="text" wire:model="checkpoints.{{ $cpIndex }}.title" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('quests.title') }} *" />
                                @error("checkpoints.{$cpIndex}.title") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <textarea wire:model="checkpoints.{{ $cpIndex }}.description" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('quests.description') }}"></textarea>

                            {{-- Map Pin Drop --}}
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('general.quest_map') }} ({{ __('quests.checkpoint') }})</label>
                                <div
                                    class="h-48 w-full rounded-lg bg-gray-200 dark:bg-gray-700"
                                    x-data="{
                                        map: null,
                                        marker: null,
                                        init() {
                                            if (typeof google === 'undefined') return;
                                            const lat = {{ $checkpoint['latitude'] ?? 55.6761 }};
                                            const lng = {{ $checkpoint['longitude'] ?? 12.5683 }};
                                            this.map = new google.maps.Map(this.$el, {
                                                center: { lat, lng },
                                                zoom: 13,
                                                mapTypeControl: false,
                                                streetViewControl: false,
                                            });
                                            @if ($checkpoint['latitude'] && $checkpoint['longitude'])
                                                this.marker = new google.maps.Marker({
                                                    position: { lat, lng },
                                                    map: this.map,
                                                    draggable: true,
                                                });
                                                this.marker.addListener('dragend', (e) => {
                                                    $wire.updateCheckpointCoordinates({{ $cpIndex }}, e.latLng.lat(), e.latLng.lng());
                                                });
                                            @endif
                                            this.map.addListener('click', (e) => {
                                                if (this.marker) this.marker.setMap(null);
                                                this.marker = new google.maps.Marker({
                                                    position: e.latLng,
                                                    map: this.map,
                                                    draggable: true,
                                                });
                                                this.marker.addListener('dragend', (ev) => {
                                                    $wire.updateCheckpointCoordinates({{ $cpIndex }}, ev.latLng.lat(), ev.latLng.lng());
                                                });
                                                $wire.updateCheckpointCoordinates({{ $cpIndex }}, e.latLng.lat(), e.latLng.lng());
                                            });
                                        }
                                    }"
                                >
                                    <div class="flex h-full items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('general.loading') }}
                                    </div>
                                </div>
                                @if ($checkpoint['latitude'] && $checkpoint['longitude'])
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">📍 {{ number_format($checkpoint['latitude'], 5) }}, {{ number_format($checkpoint['longitude'], 5) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Step 3: Questions --}}
        @if ($step === 3)
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('quests.questions') }}</h2>

                @foreach ($checkpoints as $cpIndex => $checkpoint)
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700" wire:key="q-cp-{{ $cpIndex }}">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $checkpoint['title'] ?: __('quests.checkpoint') . ' ' . ($cpIndex + 1) }}</h3>
                            <button wire:click="addQuestion({{ $cpIndex }})" class="rounded-lg bg-forest-600 px-3 py-1.5 text-xs font-medium text-white">+ {{ __('quests.question') }}</button>
                        </div>

                        @foreach ($questions[$cpIndex] ?? [] as $qIndex => $question)
                            <div class="mb-4 rounded-lg border border-gray-200 p-3 dark:border-gray-600" wire:key="q-{{ $cpIndex }}-{{ $qIndex }}">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quests.question') }} {{ $qIndex + 1 }}</span>
                                    <button wire:click="removeQuestion({{ $cpIndex }}, {{ $qIndex }})" class="text-xs text-red-500">{{ __('general.delete') }}</button>
                                </div>

                                <div class="space-y-2">
                                    <input type="text" wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.body" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('quests.question') }}..." />
                                    @error("questions.{$cpIndex}.{$qIndex}.body") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

                                    <div class="grid grid-cols-2 gap-2">
                                        <select wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.type" wire:change="onQuestionTypeChanged({{ $cpIndex }}, {{ $qIndex }})" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            @foreach (\App\Enums\QuestionType::cases() as $type)
                                                <option value="{{ $type->value }}">{{ str_replace('_', ' ', ucfirst($type->value)) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.points" min="1" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('quests.points') }}" />
                                    </div>

                                    <input type="text" wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.hint" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Hint (optional)" />

                                    {{-- Answers (not for OpenEnded) --}}
                                    @if ($question['type'] !== \App\Enums\QuestionType::OpenText->value)
                                        <div class="mt-2 space-y-2">
                                            <label class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('quests.answers') }}</label>
                                            @foreach ($question['answers'] as $aIndex => $answer)
                                                <div class="flex items-center gap-2" wire:key="a-{{ $cpIndex }}-{{ $qIndex }}-{{ $aIndex }}">
                                                    <input
                                                        type="{{ $question['type'] === \App\Enums\QuestionType::TrueFalse->value ? 'radio' : 'checkbox' }}"
                                                        wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.answers.{{ $aIndex }}.is_correct"
                                                        {{ $question['type'] === \App\Enums\QuestionType::TrueFalse->value ? 'name=correct_' . $cpIndex . '_' . $qIndex : '' }}
                                                        class="h-4 w-4 text-forest-600"
                                                    />
                                                    <input type="text" wire:model="questions.{{ $cpIndex }}.{{ $qIndex }}.answers.{{ $aIndex }}.body" class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('quests.answer') }} {{ $aIndex + 1 }}" {{ $question['type'] === \App\Enums\QuestionType::TrueFalse->value ? 'disabled' : '' }} />
                                                    @if ($question['type'] !== \App\Enums\QuestionType::TrueFalse->value && count($question['answers']) > 2)
                                                        <button wire:click="removeAnswer({{ $cpIndex }}, {{ $qIndex }}, {{ $aIndex }})" class="text-xs text-red-500">✕</button>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if ($question['type'] === \App\Enums\QuestionType::MultipleChoice->value)
                                                <button wire:click="addAnswer({{ $cpIndex }}, {{ $qIndex }})" class="text-xs text-forest-600 dark:text-forest-400">+ {{ __('quests.answer') }}</button>
                                            @endif
                                        </div>
                                    @else
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('sessions.open_ended_note') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if (empty($questions[$cpIndex]))
                            <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('quests.no_questions') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Step 4: Game Rules --}}
        @if ($step === 4)
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('sessions.game_rules') }}</h2>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('sessions.play_mode') }}</label>
                    <select wire:model="playMode" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach (\App\Enums\PlayMode::cases() as $mode)
                            <option value="{{ $mode->value }}">{{ ucfirst($mode->value) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('sessions.wrong_answer_behaviour') }}</label>
                    <select wire:model="wrongAnswerBehaviour" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach (\App\Enums\WrongAnswerBehaviour::cases() as $behaviour)
                            <option value="{{ $behaviour->value }}">{{ str_replace('_', ' ', ucfirst($behaviour->value)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('quests.time_limit') }} ({{ __('sessions.seconds') }})</label>
                    <input type="number" wire:model="timeLimitPerQuestion" min="5" max="300" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="shuffleQuestions" id="shuffleQ" class="h-4 w-4 rounded text-forest-600" />
                    <label for="shuffleQ" class="text-sm text-gray-700 dark:text-gray-300">{{ __('sessions.shuffle_questions') }}</label>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" wire:model="shuffleAnswers" id="shuffleA" class="h-4 w-4 rounded text-forest-600" />
                    <label for="shuffleA" class="text-sm text-gray-700 dark:text-gray-300">{{ __('sessions.shuffle_answers') }}</label>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('sessions.max_participants') }}</label>
                    <input type="number" wire:model="maxParticipants" min="2" max="100" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="{{ __('sessions.unlimited') }}" />
                </div>
            </div>
        @endif

        {{-- Step 5: Review & Publish --}}
        @if ($step === 5)
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('sessions.review_publish') }}</h2>

                {{-- Quest Summary --}}
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                    <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                    @if ($description)
                        <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
                    @endif
                    <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 dark:bg-gray-700">{{ ucfirst($difficulty) }}</span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 dark:bg-gray-700">{{ ucfirst($playMode) }}</span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 dark:bg-gray-700">{{ count($checkpoints) }} {{ __('quests.checkpoints') }}</span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 dark:bg-gray-700">{{ collect($questions)->flatten(1)->count() }} {{ __('quests.questions') }}</span>
                    </div>
                </div>

                {{-- Checkpoints Summary --}}
                @foreach ($checkpoints as $cpIndex => $checkpoint)
                    <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $cpIndex + 1 }}. {{ $checkpoint['title'] }}</p>
                        @if ($checkpoint['latitude'] && $checkpoint['longitude'])
                            <p class="text-xs text-gray-500 dark:text-gray-400">📍 {{ number_format($checkpoint['latitude'], 5) }}, {{ number_format($checkpoint['longitude'], 5) }}</p>
                        @endif
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($questions[$cpIndex] ?? []) }} {{ __('quests.questions') }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Bottom Navigation --}}
    <div class="border-t border-cream-border bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex gap-3">
            @if ($step > 1)
                <button wire:click="previousStep" class="flex-1 rounded-xl border-[1.5px] border-cream-border px-4 py-3 text-sm font-semibold text-bark dark:border-gray-600 dark:text-gray-300">
                    {{ __('general.previous') }}
                </button>
            @endif

            @if ($step < 5)
                <button wire:click="nextStep" class="flex-1 rounded-xl bg-amber-400 px-4 py-3 font-heading text-sm font-bold text-bark hover:bg-amber-500">
                    {{ __('general.next') }}
                </button>
            @else
                <button wire:click="saveAsDraft" class="flex-1 rounded-xl border-[1.5px] border-cream-border px-4 py-3 text-sm font-semibold text-bark dark:border-gray-600 dark:text-gray-300">
                    {{ __('quests.draft') }}
                </button>
                <button wire:click="publish" class="flex-1 rounded-xl bg-amber-400 px-4 py-3 font-heading text-sm font-bold text-bark hover:bg-amber-500">
                    {{ __('quests.publish') }}
                </button>
            @endif
        </div>
    </div>
</div>
